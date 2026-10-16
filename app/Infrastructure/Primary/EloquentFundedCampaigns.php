<?php

declare(strict_types=1);

namespace App\Infrastructure\Primary;

use App\Application\Business\Contracts\CampaignClosureEvidence;
use App\Application\Business\Contracts\PrimaryCampaignSource;
use App\Application\Disbursement\Contracts\FundedCampaigns;
use App\Application\Disbursement\FailedClosing;
use App\Application\Disbursement\FundedCampaign;
use App\Application\Disbursement\FundedCampaignRef;
use App\Application\Disbursement\IssueInstruction;
use App\Application\Disbursement\RecheckResult;
use App\Application\Identity\Contracts\PartyNotices;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Operations\Contracts\ChangeFeed;
use App\Application\Primary\Contracts\CampaignFundingEvidence;
use App\Application\Wallet\Contracts\PrimaryReturnedCash;
use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\PostingSource;
use App\Domain\Disbursement\DisbursementViolation;
use App\Domain\Identity\PartyNoticeKind;
use App\Domain\Operations\ChangeScope;
use App\Domain\Operations\CommandRejection;
use App\Domain\Wallet\WalletMoney;
use App\Infrastructure\Business\RetainedFundedCampaignFacts;
use App\Models\BusinessCampaign;
use App\Models\BusinessCampaignClosure;
use App\Models\BusinessProfile;
use App\Models\PrimaryCampaignFunding;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Staged retained-funding adapter, deliberately not bound to the production port. Discovery
 * authenticates historical funding and purchases; locking takes only Business then campaign
 * gates in the caller's transaction. The caller places staff locks between those calls and
 * retains the full financial order through commit. No verified-fact or lock cache is retained.
 * Current admission remains unavailable. Authenticated terminal failed closing refunds original
 * principal; authenticated success delegates complete Holding issuance to its Primary collaborator.
 * Historical funding never supplies current admission authority; ordinary runtime binding stays closed.
 */
final class EloquentFundedCampaigns implements FundedCampaigns
{
    public function __construct(private RetainedFundedCampaignFacts $facts, private RetainedFundedClosing $closings,
        private CampaignFundingEvidence $fundings, private WalletPostings $wallets, private PrimaryReturnedCash $returned,
        private CanonicalJson $json, private CampaignClosureEvidence $closures, private RetainedPrimaryIssuance $issuance,
        private ChangeFeed $changes, private PrimaryCampaignSource $campaigns, private PartyNotices $notices) {}

    public function funded(?string $before, int $limit): array
    {
        if ($limit < 1) {
            return [];
        }

        return array_values(PrimaryCampaignFunding::query()
            ->when($before !== null, fn ($query) => $query->where('business_campaign_id', '<', $before))
            ->orderByDesc('business_campaign_id')->limit($limit)->get(['business_campaign_id'])
            ->map(function (PrimaryCampaignFunding $funding): FundedCampaignRef {
                $campaign = $this->retained($funding->business_campaign_id);

                return new FundedCampaignRef($campaign->campaignId, $campaign->businessId, $campaign->fundedAt);
            })->all());
    }

    public function lockBusiness(string $businessId): void
    {
        $this->assertTransaction();
        if (BusinessProfile::query()->whereKey($businessId)->lockForUpdate()->first() === null) {
            throw new CommandRejection('FUNDING_SOURCE_UNAVAILABLE', 409);
        }
    }

    public function lockFunded(string $campaignId): FundedCampaign
    {
        $this->assertTransaction();
        if (BusinessCampaign::query()->whereKey($campaignId)->lockForUpdate()->first() === null) {
            throw new CommandRejection('FUNDING_SOURCE_UNAVAILABLE', 409);
        }

        return $this->retained($campaignId);
    }

    public function recheck(FundedCampaign $campaign): RecheckResult
    {
        $this->assertTransaction();

        return RecheckResult::unavailable(['policy', 'evidence', 'dscr', 'exposure', 'restriction', 'mandate', 'conditions_precedent'], 'unavailable');
    }

    public function issue(FundedCampaign $campaign, IssueInstruction $instruction): void
    {
        $this->assertTransaction();
        $this->issuance->issue($campaign, $instruction);
        $this->noticeIssued($campaign->campaignId);
    }

    /**
     * Tells each Investor, once per Holding, that the disbursement issued it (BRS v2.0 NTF-R01):
     * the units, the amount, the expected return and maturity value net of the payout fee, and the
     * last due date. A replayed issue finds the same Holdings and records nothing new.
     */
    private function noticeIssued(string $campaignId): void
    {
        $business = $this->campaigns->presentation($campaignId)['business'];
        foreach (DB::table('primary_holdings')->where('business_campaign_id', $campaignId)->orderBy('id')->get() as $holding) {
            /** @var array{total_return: array{amount: string}} $rights */
            $rights = json_decode((string) $holding->rights, true, flags: JSON_THROW_ON_ERROR);
            /** @var array{payout_fee: array{amount: string}} $terms */
            $terms = json_decode((string) $holding->terms, true, flags: JSON_THROW_ON_ERROR);
            /** @var non-empty-list<array{due_date: string}> $schedule */
            $schedule = json_decode((string) $holding->schedule, true, flags: JSON_THROW_ON_ERROR);
            $return = (int) $rights['total_return']['amount'] - (int) $terms['payout_fee']['amount'];
            $this->notices->record((string) $holding->party_id, PartyNoticeKind::HoldingIssued, ['business' => $business, 'units' => (string) $holding->units,
                'amount' => (string) $holding->principal, 'expected_return' => (string) $return, 'maturity_value' => (string) ((int) $holding->principal + $return),
                'maturity_date' => (string) end($schedule)['due_date'], 'currency' => 'RWF', 'reference' => (string) $holding->receipt_id,
                'holding_id' => (string) $holding->id, 'campaign_id' => $campaignId], 'holding-issued:'.$holding->id);
        }
    }

    public function failClose(FundedCampaign $campaign, FailedClosing $closing): void
    {
        $this->assertTransaction();
        DB::transaction(function () use ($campaign, $closing): void {
            $current = $this->retained($campaign->campaignId);
            if ($current != $campaign) {
                throw new RuntimeException('PRIMARY_FUNDING_INTEGRITY_FAILED');
            }
            $evidence = $this->closings->failed($current, $closing);
            $funding = $this->fundings->find($current->campaignId) ?? throw new CommandRejection('FUNDING_SOURCE_UNAVAILABLE', 409);
            $roots = DB::table('primary_reservations')->where('business_campaign_id', $current->campaignId)->orderBy('id')->lockForUpdate()->get();
            DB::table('primary_commitments')->whereIn('primary_reservation_id', $roots->pluck('id'))->orderBy('id')->lockForUpdate()->get();
            $parties = $current->partyIds();
            sort($parties, SORT_STRING);
            $locked = [];
            foreach ($parties as $party) {
                $locked[$party] = $this->wallets->lockForParty($party);
            }
            $previous = BusinessCampaignClosure::query()->where('business_campaign_id', $current->campaignId)->first();
            if ($previous !== null) {
                if ($previous->id !== $closing->closingId || $previous->phase !== 'failed_closing') {
                    throw new RuntimeException('PRIMARY_CLOSING_MISMATCH');
                }
                $retained = $this->closures->find($current->campaignId);
                if ($this->json->encode($retained['closing'] ?? []) !== $this->json->encode(get_object_vars($evidence))) {
                    throw new RuntimeException('CAMPAIGN_CLOSURE_INTEGRITY_FAILED');
                }

                return;
            }
            $publication = BusinessCampaign::query()->whereKey($current->campaignId)->sole();
            $payload = ['scope' => 'funded-failed-closing-v1', 'closure_id' => $closing->closingId, 'campaign_id' => $current->campaignId,
                'campaign_sha256' => $publication->sha256, 'business_id' => $current->businessId,
                'exposure_reservation_id' => $current->exposureReservationId, 'principal_released' => $current->principal,
                'phase' => 'failed_closing', 'actor_user_id' => null, 'revision' => 2, 'closed_at' => $evidence->recordedAt,
                'recorded_at' => now('UTC')->toIso8601String(), 'closing' => get_object_vars($evidence),
                'funding_id' => $funding['funding_id'], 'cash_returns' => [], 'released_held' => ['currency' => 'RWF', 'amount' => '0'],
                'committed_refunded' => ['currency' => 'RWF', 'amount' => $current->principal], 'investors' => count($current->partyIds())];
            DB::table('primary_failed_closings')->insert(['disbursement_closing_id' => $closing->closingId,
                'business_campaign_closure_id' => $closing->closingId, 'funding_id' => $funding['funding_id'],
                'closing_sha256' => $evidence->closingSha256, 'disbursement_sha256' => $evidence->disbursementSha256]);
            $record = new BusinessCampaignClosure;
            $record->id = $closing->closingId;
            $record->forceFill(['business_campaign_id' => $current->campaignId, 'business_id' => $current->businessId,
                'exposure_reservation_id' => $current->exposureReservationId, 'principal' => $current->principal, 'phase' => 'failed_closing',
                'actor_user_id' => null, 'closed_at' => $evidence->recordedAt, 'created_at' => now('UTC'),
                'payload' => $payload, 'sha256' => hash('sha256', $this->json->encode($payload))]);
            // Assemble the immutable closure after cash returns, using its deferred relational parent.
            foreach ($funding['commitments'] as $purchase) {
                $amount = WalletMoney::of($purchase['principal']);
                $source = new PostingSource('primary_reservation', $purchase['reservation_id'], $purchase['cash']['origin_operation_id']);
                $posting = $this->wallets->refund($locked[$purchase['party_id']], $amount, $source);
                $cash = $this->returned->requireReturned($locked[$purchase['party_id']], $amount, $source);
                if ($cash->returnKind !== 'primary_refund' || $cash->returnEntryId !== $posting->entryId) {
                    throw new RuntimeException('PRIMARY_CLOSING_MISMATCH');
                }
                $payload['cash_returns'][] = ['primary_reservation_id' => $purchase['reservation_id'],
                    'primary_reservation_version_id' => $purchase['confirmation_version_id'],
                    'version_sha256' => DB::table('primary_reservation_versions')->where('id', $purchase['confirmation_version_id'])->value('sha256'),
                    'primary_commitment_id' => $purchase['commitment_id'], 'party_id' => $purchase['party_id'],
                    'wallet_id' => $cash->walletId, 'principal' => $purchase['principal'], 'origin_operation_id' => $source->originOperationId,
                    'hold_entry_id' => $cash->holdEntryId, 'commit_entry_id' => $cash->commitEntryId,
                    'return_entry_id' => $cash->returnEntryId, 'return_kind' => $cash->returnKind];
            }
            $record->forceFill(['payload' => $payload, 'sha256' => hash('sha256', $this->json->encode($payload))])->save();
            foreach ($payload['cash_returns'] as $binding) {
                DB::table('primary_campaign_closure_returns')->insert(['business_campaign_closure_id' => $record->id, ...$binding]);
            }
            DB::select('SELECT check_primary_campaign_closure_returns(?, false)', [$record->id]);
            foreach ($payload['cash_returns'] as $binding) {
                $this->changes->record(ChangeScope::party($binding['party_id']), 'wallet', $binding['wallet_id']);
                $this->changes->record(ChangeScope::party($binding['party_id']), 'purchase', $binding['primary_reservation_id']);
            }
        });
    }

    private function retained(string $campaignId): FundedCampaign
    {
        return $this->facts->find($campaignId) ?? throw new CommandRejection('FUNDING_SOURCE_UNAVAILABLE', 409);
    }

    private function assertTransaction(): void
    {
        if (app('db.transactions')->callbackApplicableTransactions()->isEmpty()) {
            throw new DisbursementViolation('FUNDING_TRANSACTION_REQUIRED');
        }
    }
}
