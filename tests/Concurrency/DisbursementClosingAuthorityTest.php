<?php

declare(strict_types=1);

use App\Application\Disbursement\ClosingEvidence;
use App\Application\Disbursement\Contracts\DisbursementClosingEvidence;
use App\Application\Disbursement\Contracts\FundedCampaigns;
use App\Application\Disbursement\FailedClosing;
use App\Application\Disbursement\FundedCampaign;
use App\Application\Disbursement\IssueInstruction;
use App\Application\Disbursement\RecheckResult;
use App\Infrastructure\Disbursement\SyntheticDisbursementSources;
use App\Models\DisbursementClosing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\DisbursementFixture;

/*
 * The approve-time closing's command authority across a real outer commit (#96 5925545895). The
 * operation journal records the approve only after its effect returns, so the closing evidence is
 * read inside `failClose` before the command row exists; the deferred authority trigger then binds
 * the closing to that recorded command when the approve commits, and a later read replays the same
 * evidence exactly.
 */

/**
 * A funding source that reads the closing evidence inside `failClose`, noting whether the approve
 * command was already recorded at that moment.
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
            $this->seen[] = ['evidence' => $evidence, 'recorded' => DB::table('command_operations')->where('id', $evidence->operationId)->exists()];
            $this->inner->failClose($campaign, $closing);
        }
    };
}

/** @return ArrayObject<int, array{evidence: ClosingEvidence, recorded: bool}> */
function authorityCalls(): ArrayObject
{
    return new ArrayObject;
}

it('commits an immediate approve-time closing read inside failClose, then replays the same evidence exactly', function (): void {
    $seen = authorityCalls();
    app()->instance(FundedCampaigns::class, authorityRecordingFunding($seen));
    ['campaign' => $campaign, 'disbursement' => $disbursement] = DisbursementFixture::funded();
    $maker = DisbursementFixture::staff(['treasury']);
    $checker = DisbursementFixture::staff(['approver']);
    DisbursementFixture::command($maker, $disbursement, 'authorize', 0);
    DisbursementFixture::sources()->scriptRecheck($campaign->campaignId, 'failed', ['restriction']);
    $requestId = (string) Str::uuid();
    $result = DisbursementFixture::command($checker, $disbursement, 'approve', 1, DisbursementFixture::stepUp($checker, $disbursement)['proof'], $requestId);
    $closing = DisbursementClosing::query()->where('disbursement_id', $disbursement->id)->sole();
    $read = fn (): ClosingEvidence => DB::transaction(fn (): ClosingEvidence => app(DisbursementClosingEvidence::class)->find($closing->id));

    expect([$result['code'], $result['status']])->toBe(['CAMPAIGN_FAILED_CLOSING', 'completed'])
        ->and($seen)->toHaveCount(1)->and($seen[0]['recorded'])->toBeFalse()
        ->and([$closing->operation_id, $closing->actor_user_id, $closing->request_id])->toBe([$result['operation_id'], $checker->id, $requestId])
        ->and($read())->toEqual($seen[0]['evidence'])
        ->and($read())->toEqual($seen[0]['evidence'])
        ->and($seen[0]['evidence']->recordedAt)->toBe($closing->created_at->utc()->toIso8601String());
});

it('refuses to commit an approve-time closing bound to a command other than its own approve', function (): void {
    ['disbursement' => $disbursement] = DisbursementFixture::funded();
    $maker = DisbursementFixture::staff(['treasury']);
    $requestId = (string) Str::uuid();
    $authorize = DisbursementFixture::command($maker, $disbursement, 'authorize', 0, null, $requestId);

    // The recorded authorize exists, so only the authority binding can refuse the commit; a deferred
    // refusal surfaces from COMMIT itself.
    expect(fn () => DB::transaction(fn () => DB::table('disbursement_closings')->insert(['id' => strtolower((string) Str::ulid()),
        'disbursement_id' => $disbursement->id, 'kind' => 'failed_closing', 'cause' => 'approve_recheck', 'causes' => '["mandate"]',
        'operation_id' => $authorize['operation_id'], 'actor_user_id' => $maker->id, 'request_id' => $requestId, 'payload' => 'x',
        'sha256' => str_repeat('0', 64), 'created_at' => now()])))
        ->toThrow(PDOException::class, 'commits only with its own recorded approve command')
        ->and(DisbursementClosing::query()->count())->toBe(0);
});
