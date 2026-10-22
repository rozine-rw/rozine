<?php

declare(strict_types=1);

use App\Application\Disbursement\ClosingEvidence;
use App\Application\Disbursement\Contracts\DisbursementClosingEvidence;
use App\Application\Disbursement\Contracts\FundedCampaigns;
use App\Application\Disbursement\FailedClosing;
use App\Application\Disbursement\FundedCampaign;
use App\Application\Disbursement\IssueInstruction;
use App\Application\Disbursement\OpenFundedDisbursements;
use App\Application\Disbursement\RecheckResult;
use App\Infrastructure\Disbursement\SyntheticDisbursementSources;
use App\Models\CommandOperation;
use App\Models\Disbursement;
use App\Models\DisbursementClosing;
use App\Models\DisbursementEvent;
use App\Models\DisbursementIntent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\DisbursementFixture;

/*
 * The automatic closing's evidence across a real outer commit (#96 5925545895, BRS v2.0 SVC-R01).
 * A failed recheck when automatic payment runs closes the campaign with no staff authority: the
 * closing evidence read inside `failClose` names no operation, and a later read replays the same
 * evidence exactly. Legacy approve-time closings keep their deferred command authority, and a system
 * intent can never commit bound to a recorded staff command.
 */

/**
 * A funding source that reads the closing evidence inside `failClose`, noting whether a command
 * operation was recorded under its operation id at that moment.
 *
 * @param  ArrayObject<int, array{evidence: ClosingEvidence, recorded: bool}>  $seen
 */
function authorityRecordingFunding(ArrayObject $seen): FundedCampaigns
{
    return new class(app(SyntheticDisbursementSources::class), $seen) implements FundedCampaigns
    {
        /** @param ArrayObject<int, array{evidence: ClosingEvidence, recorded: bool}> $seen */
        public function __construct(private FundedCampaigns $inner, private ArrayObject $seen) {}

        public function funded(?string $before, int $limit): array
        {
            return $this->inner->funded($before, $limit);
        }

        public function lockBusiness(string $businessId): void
        {
            $this->inner->lockBusiness($businessId);
        }

        public function lockFunded(string $campaignId): FundedCampaign
        {
            return $this->inner->lockFunded($campaignId);
        }

        public function recheck(FundedCampaign $campaign): RecheckResult
        {
            return $this->inner->recheck($campaign);
        }

        public function issue(FundedCampaign $campaign, IssueInstruction $instruction): void
        {
            $this->inner->issue($campaign, $instruction);
        }

        public function failClose(FundedCampaign $campaign, FailedClosing $closing): void
        {
            $evidence = app(DisbursementClosingEvidence::class)->find($closing->closingId);
            $this->seen[] = ['evidence' => $evidence, 'recorded' => $evidence->operationId !== null && DB::table('command_operations')->where('id', $evidence->operationId)->exists()];
            $this->inner->failClose($campaign, $closing);
        }
    };
}

/** @return ArrayObject<int, array{evidence: ClosingEvidence, recorded: bool}> */
function authorityCalls(): ArrayObject
{
    return new ArrayObject;
}

/**
 * A legacy maker authorization, recorded raw as the retired authorize command left it, so a real
 * hold and release move the disbursement back to `awaiting_second_approver`.
 *
 * @return array{operation_id: string, staff: User}
 */
function legacyAwaitingWithRecordedHold(Disbursement $disbursement): array
{
    $maker = DisbursementFixture::staff(['treasury']);
    DB::table('disbursement_events')->insert(['id' => strtolower((string) Str::ulid()), 'disbursement_id' => $disbursement->id, 'revision' => 1,
        'kind' => 'authorized', 'actor_user_id' => $maker->id,
        'operation_id' => CommandOperation::factory()->create(['actor_key' => 'staff:'.$maker->id, 'actor_user_id' => $maker->id, 'command' => 'disbursement.authorize'])->id, 'request_id' => (string) Str::uuid(),
        'binding_sha256' => str_repeat('b', 64), 'destination_sha256' => str_repeat('d', 64), 'payload' => 'x', 'sha256' => str_repeat('0', 64), 'created_at' => now()]);
    $compliance = DisbursementFixture::staff(['compliance']);
    $hold = DisbursementFixture::command($compliance, $disbursement, 'hold', 1);
    DisbursementFixture::command($compliance, $disbursement, 'release_hold', 2);

    return ['operation_id' => (string) $hold['operation_id'], 'staff' => $compliance];
}

it('commits an automatic closing read inside failClose with no staff authority, then replays the same evidence exactly', function (): void {
    $seen = authorityCalls();
    app()->instance(FundedCampaigns::class, authorityRecordingFunding($seen));
    ['campaign' => $campaign, 'disbursement' => $disbursement] = DisbursementFixture::funded();
    DisbursementFixture::sources()->scriptRecheck($campaign->campaignId, 'failed', ['restriction']);
    DisbursementFixture::sources()->setDestination($campaign->businessId, 'rotated');
    expect(app(OpenFundedDisbursements::class)->handle())->toBe(['opened' => 0, 'known' => 1, 'queued' => 0, 'failed' => 1]);
    $closing = DisbursementClosing::query()->where('disbursement_id', $disbursement->id)->sole();
    $read = fn (): ClosingEvidence => DB::transaction(fn (): ClosingEvidence => app(DisbursementClosingEvidence::class)->find($closing->id));

    expect([$closing->cause, $closing->causes])->toBe(['auto_recheck', ['restriction']])
        ->and($seen)->toHaveCount(1)->and($seen[0]['recorded'])->toBeFalse()
        ->and([$closing->intent_id, $closing->operation_id, $closing->actor_user_id, $closing->request_id])->toBe([null, null, null, null])
        ->and([$seen[0]['evidence']->cause, $seen[0]['evidence']->operationId, $seen[0]['evidence']->intentId])->toBe(['auto_recheck', null, null])
        ->and($read())->toEqual($seen[0]['evidence'])
        ->and($read())->toEqual($seen[0]['evidence'])
        ->and($seen[0]['evidence']->recordedAt)->toBe($closing->created_at->utc()->toIso8601String())
        ->and(DisbursementEvent::query()->where('disbursement_id', $disbursement->id)->sole()->only(['kind', 'actor_user_id', 'operation_id']))
        ->toBe(['kind' => 'failed_closing', 'actor_user_id' => null, 'operation_id' => null]);
});

it('refuses to commit a legacy approve-time closing bound to a command other than its own approve', function (): void {
    ['disbursement' => $disbursement] = DisbursementFixture::funded();
    ['operation_id' => $operationId, 'staff' => $staff] = legacyAwaitingWithRecordedHold($disbursement);

    // The recorded hold exists, so only the authority binding can refuse the commit; a deferred
    // refusal surfaces from COMMIT itself.
    expect(fn () => DB::transaction(fn () => DB::table('disbursement_closings')->insert(['id' => strtolower((string) Str::ulid()),
        'disbursement_id' => $disbursement->id, 'kind' => 'failed_closing', 'cause' => 'approve_recheck', 'causes' => '["mandate"]',
        'operation_id' => $operationId, 'actor_user_id' => $staff->id, 'request_id' => (string) Str::uuid(), 'payload' => 'x',
        'sha256' => str_repeat('0', 64), 'created_at' => now()])))
        ->toThrow(PDOException::class, 'commits only with its own recorded approve command')
        ->and(DisbursementClosing::query()->count())->toBe(0);
});

it('refuses to commit a system intent whose operation is a recorded staff command', function (): void {
    ['disbursement' => $disbursement] = DisbursementFixture::funded();
    $hold = DisbursementFixture::command(DisbursementFixture::staff(['compliance']), $disbursement, 'hold', 0);
    DisbursementFixture::command(DisbursementFixture::staff(['compliance']), $disbursement, 'release_hold', 1);
    $id = strtolower((string) Str::ulid());

    expect(fn () => DB::transaction(fn () => DB::table('disbursement_intents')->insert(['id' => $id, 'disbursement_id' => $disbursement->id,
        'operation_id' => $hold['operation_id'], 'request_id' => (string) Str::uuid(), 'revision' => 2,
        'amount' => (string) ((int) $disbursement->amount - (int) $disbursement->platform_fee), 'currency' => 'RWF', 'destination_id' => 'dest-1',
        'destination_revision' => 1, 'destination_sha256' => str_repeat('d', 64), 'commitments_digest' => $disbursement->commitments_digest,
        'binding_sha256' => str_repeat('b', 64), 'intent_digest' => str_repeat('i', 64), 'provider' => 'synthetic', 'provider_reference' => 'x',
        'provider_reference_sha256' => hash('sha256', $id), 'environment' => $disbursement->environment, 'idempotent_sends' => false,
        'maker_user_id' => null, 'checker_user_id' => null, 'rule_version' => 'brs-2.0-svc-r01', 'payload' => 'x', 'sha256' => str_repeat('0', 64),
        'created_at' => now()])))
        ->toThrow(PDOException::class, 'An automatic disbursement intent is never a staff command')
        ->and(DisbursementIntent::query()->count())->toBe(0);
});
