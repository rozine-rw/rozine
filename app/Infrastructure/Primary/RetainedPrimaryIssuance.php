<?php

declare(strict_types=1);

namespace App\Infrastructure\Primary;

use App\Application\Disbursement\FundedCampaign;
use App\Application\Disbursement\IssueInstruction;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Operations\Contracts\ChangeFeed;
use App\Application\Primary\Contracts\CampaignFundingEvidence;
use App\Application\Primary\Contracts\HoldingSource;
use App\Application\Primary\Contracts\IssuedHoldingEvidence;
use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\PostingCause;
use App\Application\Wallet\PostingSource;
use App\Domain\Disbursement\DisbursementViolation;
use App\Domain\Operations\ChangeScope;
use App\Domain\Operations\CommandRejection;
use App\Domain\Wallet\WalletMoney;
use App\Infrastructure\Business\RetainedFundedCampaignFacts;
use App\Models\PrimaryCampaignIssuance;
use App\Models\PrimaryHolding;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Unbound Primary success effect. Caller retains Business -> staff -> campaign/disbursement before
 * roots/commitments -> Party-ordered wallets -> ledger. The savepoint rolls back the complete effect
 * if the caller catches a failure. Authentic historical inputs never grant current admission.
 */
final readonly class RetainedPrimaryIssuance
{
    public function __construct(private RetainedFundedCampaignFacts $facts, private RetainedFundedClosing $closings,
        private CampaignFundingEvidence $fundings, private HoldingSource $purchases, private IssuedHoldingEvidence $issued,
        private WalletPostings $wallets, private CanonicalJson $json, private ChangeFeed $changes) {}

    public function issue(FundedCampaign $campaign, IssueInstruction $instruction): void
    {
        if (app('db.transactions')->callbackApplicableTransactions()->isEmpty()) {
            throw new DisbursementViolation('FUNDING_TRANSACTION_REQUIRED');
        }
        DB::transaction(function () use ($campaign, $instruction): void {
            $current = $this->facts->find($campaign->campaignId) ?? throw new CommandRejection('FUNDING_SOURCE_UNAVAILABLE', 409);
            if ($current != $campaign) {
                throw new RuntimeException('PRIMARY_FUNDING_INTEGRITY_FAILED');
            }
            $closing = $this->closings->issued($current, $instruction);
            $funding = $this->fundings->find($campaign->campaignId) ?? throw new CommandRejection('FUNDING_SOURCE_UNAVAILABLE', 409);
            $facts = $this->purchases->campaignFacts($campaign->campaignId);
            $roots = DB::table('primary_reservations')->where('business_campaign_id', $campaign->campaignId)->orderBy('id')->lockForUpdate()->get();
            DB::table('primary_commitments')->whereIn('primary_reservation_id', $roots->pluck('id'))->orderBy('id')->lockForUpdate()->get();
            $parties = $campaign->partyIds();
            sort($parties, SORT_STRING);
            $locked = [];
            foreach ($parties as $party) {
                $locked[$party] = $this->wallets->lockForParty($party);
            }
            if (PrimaryCampaignIssuance::query()->whereKey($campaign->campaignId)->exists()) {
                $retained = $this->issued->find($campaign->campaignId);
                if ($this->json->encode($retained['closing'] ?? null) !== $this->json->encode(get_object_vars($closing))) {
                    throw new RuntimeException('PRIMARY_ISSUANCE_INTEGRITY_FAILED');
                }

                return;
            }
            if (PrimaryHolding::query()->where('business_campaign_id', $campaign->campaignId)->exists()
                || DB::table('ledger_entries')->where('source_type', 'primary_reservation')->whereIn('source_id', $roots->pluck('id'))
                    ->whereIn('kind', ['primary_issue', 'primary_refund'])->exists()
                || DB::table('business_campaign_closures')->where('business_campaign_id', $campaign->campaignId)->exists()) {
                throw new RuntimeException('PRIMARY_ISSUANCE_INTEGRITY_FAILED');
            }
            $purchases = array_column($funding['commitments'], null, 'commitment_id');
            $certificates = [];
            foreach ($facts as $commitmentId => $purchaseFacts) {
                $purchase = $purchases[$commitmentId];
                $posting = $this->wallets->issue($locked[$purchase['party_id']], WalletMoney::of($purchase['principal']),
                    new PostingSource('primary_reservation', $purchase['reservation_id'], $purchase['cash']['origin_operation_id']),
                    new PostingCause('disbursement_closing', $closing->closingId));
                $holding = new PrimaryHolding;
                $holding->id = strtolower((string) Str::ulid());
                $receiptId = strtolower((string) Str::ulid());
                $payload = PrimaryIssuancePayload::holding($purchaseFacts, $purchase, $closing, $funding['funding_id'], $holding->id, $receiptId, $posting->entryId);
                $holding->forceFill([...$purchaseFacts, 'disbursement_closing_id' => $closing->closingId, 'schedule' => $payload['schedule'],
                    'issued_at' => $closing->recordedAt, 'disbursement_effective_at' => $closing->effectiveAt, 'effective_date' => $closing->effectiveDate,
                    'receipt_id' => $receiptId, 'payload' => $payload, 'sha256' => hash('sha256', $this->json->encode($payload)),
                    'created_at' => $closing->recordedAt])->save();
                $certificates[] = ['id' => $holding->id, 'commitment_id' => $commitmentId, 'sha256' => $holding->sha256];
            }
            $payload = PrimaryIssuancePayload::campaign($closing, $funding['funding_id'], $certificates);
            (new PrimaryCampaignIssuance)->forceFill(['business_campaign_id' => $campaign->campaignId, 'business_id' => $campaign->businessId,
                'funding_id' => $funding['funding_id'], 'exposure_reservation_id' => $campaign->exposureReservationId,
                'disbursement_closing_id' => $closing->closingId, 'principal' => $campaign->principal, 'commitment_count' => count($facts),
                'holdings_sha256' => hash('sha256', $this->json->encode(['holdings' => $certificates])), 'payload' => $payload,
                'sha256' => hash('sha256', $this->json->encode($payload)), 'created_at' => $closing->recordedAt])->save();
            $this->issued->find($campaign->campaignId);
            foreach ($locked as $wallet) {
                $this->changes->record(ChangeScope::party($wallet->partyId), 'wallet', $wallet->walletId);
            }
            foreach ($facts as $purchaseFacts) {
                $this->changes->record(ChangeScope::party($purchaseFacts['party_id']), 'purchase', $purchaseFacts['primary_reservation_id']);
            }
            $this->changes->record(ChangeScope::business($campaign->businessId), 'campaign', $campaign->campaignId);
        });
    }
}
