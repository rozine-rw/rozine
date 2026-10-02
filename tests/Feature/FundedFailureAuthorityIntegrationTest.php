<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessExposureStore;
use App\Application\Disbursement\ClosingEvidence;
use App\Application\Disbursement\Contracts\DisbursementStore;
use App\Application\Disbursement\Contracts\FundedCampaigns;
use App\Application\Disbursement\FailedClosing;
use App\Application\Disbursement\FundedCampaign;
use App\Application\Disbursement\IssueInstruction;
use App\Application\Disbursement\RecheckResult;
use App\Application\Disbursement\RecordPayoutEvent;
use App\Application\Primary\Contracts\CampaignFundingEvidence;
use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\PostingSource;
use App\Domain\Operations\CommandRejection;
use App\Domain\Wallet\WalletMoney;
use App\Infrastructure\Business\RetainedFundedCampaignFacts;
use App\Infrastructure\Disbursement\SyntheticDisbursementSources;
use App\Infrastructure\Primary\EloquentFundedCampaigns;
use App\Infrastructure\Primary\RetainedFundedClosing;
use App\Models\Disbursement;
use App\Models\DisbursementClosing;
use App\Models\DisbursementIntent;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\Support\DisbursementFixture;
use Tests\Support\PrimaryHoldingFixture;

/**
 * Real retained purchases/funding and real closing ancestry. Only current recheck, destination,
 * staff connections and provider observations are synthetic. The callback authenticates and
 * records its instruction for inspection; it performs no Primary settlement or exposure writes.
 *
 * @param  ArrayObject<int, array{campaign: FundedCampaign, instruction: FailedClosing, evidence: ClosingEvidence}>  $seen
 */
function observedFundedFailureAuthority(ArrayObject $seen): FundedCampaigns
{
    return new class(app(EloquentFundedCampaigns::class), app(RetainedFundedClosing::class), app(SyntheticDisbursementSources::class), $seen) implements FundedCampaigns
    {
        /** @param ArrayObject<int, array{campaign: FundedCampaign, instruction: FailedClosing, evidence: ClosingEvidence}> $seen */
        public function __construct(private FundedCampaigns $retained, private RetainedFundedClosing $authority,
            private FundedCampaigns $syntheticAdmission, private ArrayObject $seen) {}

        public function funded(?string $before, int $limit): array
        {
            return $this->retained->funded($before, $limit);
        }

        public function lockBusiness(string $businessId): void
        {
            $this->retained->lockBusiness($businessId);
        }

        public function lockFunded(string $campaignId): FundedCampaign
        {
            return $this->retained->lockFunded($campaignId);
        }

        public function recheck(FundedCampaign $campaign): RecheckResult
        {
            return $this->syntheticAdmission->recheck($campaign);
        }

        public function issue(FundedCampaign $campaign, IssueInstruction $instruction): void
        {
            throw new LogicException('This fixture never issues funded principal.');
        }

        public function failClose(FundedCampaign $campaign, FailedClosing $instruction): void
        {
            $this->seen[] = ['campaign' => $campaign, 'instruction' => $instruction,
                'evidence' => $this->authority->failed($campaign, $instruction)];
        }
    };
}

/** @return ArrayObject<int, array{campaign: FundedCampaign, instruction: FailedClosing, evidence: ClosingEvidence}> */
function fundedFailureAuthorityCalls(): ArrayObject
{
    return new ArrayObject;
}

beforeEach(fn () => $this->freezeSecond());

it('keeps authenticated funded failure authority distinct from permission to refund or release exposure', function (string $cause): void {
    ['campaign' => $publication] = PrimaryHoldingFixture::committed(requoted: [0]);
    $funding = app(CampaignFundingEvidence::class)->find($publication->id);
    $facts = app(RetainedFundedCampaignFacts::class)->find($publication->id);
    if ($funding === null || ! $facts instanceof FundedCampaign) {
        throw new LogicException('Real retained funding was not available.');
    }
    $before = [DB::table('ledger_entries')->orderBy('id')->get()->toJson(),
        DB::table('primary_holdings')->count(), DB::table('business_campaign_closures')->count(),
        app(BusinessExposureStore::class)->current($facts->businessId)];
    $seen = fundedFailureAuthorityCalls();
    app()->instance(FundedCampaigns::class, observedFundedFailureAuthority($seen));
    DisbursementFixture::sources()->setDestination($facts->businessId, 'verified');
    expect(app(DisbursementStore::class)->openFunded(1))->toBe(['opened' => 1, 'known' => 0]);
    $disbursement = Disbursement::query()->where('business_campaign_id', $facts->campaignId)->sole();
    $maker = DisbursementFixture::staff(['treasury']);
    $checker = DisbursementFixture::staff(['approver']);
    DisbursementFixture::command($maker, $disbursement, 'authorize', 0);
    $proof = DisbursementFixture::stepUp($checker, $disbursement)['proof'];
    if ($cause === 'approve_recheck') {
        DisbursementFixture::sources()->scriptRecheck($facts->campaignId, 'failed', ['restriction']);
        DisbursementFixture::command($checker, $disbursement, 'approve', 1, $proof);
    } elseif ($cause === 'worker_recheck') {
        DB::transaction(function () use ($checker, $disbursement, $proof, $facts): void {
            DisbursementFixture::command($checker, $disbursement, 'approve', 1, $proof);
            DisbursementFixture::sources()->scriptRecheck($facts->campaignId, 'failed', ['exposure']);
        });
    } else {
        DisbursementFixture::command($checker, $disbursement, 'approve', 1, $proof);
        $intent = DisbursementIntent::query()->where('disbursement_id', $disbursement->id)->sole();
        app(RecordPayoutEvent::class)->handle(DisbursementFixture::provider()->callback($intent->id, 'failed'));
    }
    expect($seen)->toHaveCount(1);
    $call = $seen->getArrayCopy()[0] ?? throw new LogicException('Closing callback was not observed.');
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    $evidence = DB::transaction(fn (): ClosingEvidence => app(RetainedFundedClosing::class)->failed($facts, $call['instruction']));
    expect($call['campaign'])->toEqual($facts)->and($evidence)->toEqual($call['evidence'])
        ->and($evidence->kind)->toBe('failed_closing')->and($evidence->cause)->toBe($cause)
        ->and($evidence->commitmentsDigest)->toBe($facts->commitmentsDigest())
        ->and($evidence->amount)->toBe($funding['principal'])
        ->and(DisbursementClosing::query()->whereKey($evidence->closingId)->sole()->kind)->toBe('failed_closing');

    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    foreach ($funding['commitments'] as $purchase) {
        expect(fn () => DB::transaction(function () use ($facts, $purchase): void {
            $staged = app(EloquentFundedCampaigns::class);
            $staged->lockBusiness($facts->businessId);
            $staged->lockFunded($facts->campaignId);
            DB::table('primary_reservations')->where('business_campaign_id', $facts->campaignId)->orderBy('id')->lockForUpdate()->get();
            DB::table('primary_commitments')->whereIn('id', array_map(fn ($commitment): string => $commitment->id, $facts->commitments))->orderBy('id')->lockForUpdate()->get();
            $wallets = app(WalletPostings::class);
            $locked = [];
            $parties = array_unique(array_map(fn ($commitment): string => $commitment->partyId, $facts->commitments));
            sort($parties, SORT_STRING);
            foreach ($parties as $party) {
                $locked[$party] = $wallets->lockForParty($party);
            }
            $wallets->refund($locked[$purchase['party_id']], WalletMoney::of($purchase['principal']),
                new PostingSource('primary_reservation', $purchase['reservation_id'], $purchase['cash']['origin_operation_id']));
        }))->toThrow(QueryException::class, 'Funded principal requires authoritative failed closing');
    }
    expect(fn () => DB::transaction(fn () => app(EloquentFundedCampaigns::class)->failClose($facts, $call['instruction'])))
        ->toThrow(CommandRejection::class, 'FUNDING_SOURCE_UNAVAILABLE');
    expect([DB::table('ledger_entries')->orderBy('id')->get()->toJson(),
        DB::table('primary_holdings')->count(), DB::table('business_campaign_closures')->count(),
        app(BusinessExposureStore::class)->current($facts->businessId)])->toBe($before)
        ->and(app(CampaignFundingEvidence::class)->find($facts->campaignId))->toBe($funding)
        ->and(app(RetainedFundedCampaignFacts::class)->find($facts->campaignId))->toEqual($facts);
})->with(['approve_recheck', 'worker_recheck', 'reconciled_failure']);
