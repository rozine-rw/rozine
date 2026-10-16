<?php

declare(strict_types=1);

namespace App\Infrastructure\Primary;

use App\Application\Disbursement\Contracts\DisbursementClosingEvidence;
use App\Application\Disbursement\IssueInstruction;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Primary\Contracts\CampaignFundingEvidence;
use App\Application\Primary\Contracts\HoldingSource;
use App\Application\Primary\Contracts\IssuedHoldingEvidence;
use App\Application\Wallet\PostingCause;
use App\Application\Wallet\PostingSource;
use App\Domain\Disbursement\DisbursementViolation;
use App\Domain\Wallet\WalletMoney;
use App\Infrastructure\Business\RetainedFundedCampaignFacts;
use App\Infrastructure\Wallet\RetainedPrimaryIssueCash;
use App\Models\PrimaryCampaignIssuance;
use App\Models\PrimaryHolding;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Complete historical bundle authentication. SELECT-only; no source cache survives a call. */
final readonly class RetainedIssuedHoldings implements IssuedHoldingEvidence
{
    public function __construct(private RetainedFundedCampaignFacts $facts, private CampaignFundingEvidence $fundings,
        private HoldingSource $purchases, private DisbursementClosingEvidence $closings, private RetainedFundedClosing $boundClosing,
        private RetainedPrimaryIssueCash $cash, private CanonicalJson $json) {}

    public function find(string $campaignId): ?array
    {
        if (app('db.transactions')->callbackApplicableTransactions()->isEmpty()) {
            throw new DisbursementViolation('PRIMARY_ISSUANCE_TRANSACTION_REQUIRED');
        }
        $record = PrimaryCampaignIssuance::query()->whereKey($campaignId)->first();
        $holdings = PrimaryHolding::query()->where('business_campaign_id', $campaignId)->orderBy('commitment_id')->get();
        if ($record === null) {
            if ($holdings->isNotEmpty()) {
                throw new RuntimeException('PRIMARY_ISSUANCE_SOURCE_UNAVAILABLE');
            }

            return null;
        }
        $campaign = $this->facts->find($campaignId) ?? throw new RuntimeException('PRIMARY_ISSUANCE_SOURCE_UNAVAILABLE');
        $funding = $this->fundings->find($campaignId) ?? throw new RuntimeException('PRIMARY_ISSUANCE_SOURCE_UNAVAILABLE');
        $closing = $this->closings->find($record->disbursement_closing_id);
        if ($closing->kind !== 'issued' || $closing->intentOperationId === null || $closing->effectiveAt === null
            || $closing->effectiveDate === null || $closing->dueDates === null) {
            throw new RuntimeException('PRIMARY_ISSUANCE_INTEGRITY_FAILED');
        }
        $this->boundClosing->issued($campaign, new IssueInstruction($closing->closingId, $closing->disbursementId,
            $closing->intentOperationId, $closing->effectiveAt, $closing->effectiveDate, $closing->dueDates, $closing->recordedAt));
        $facts = $this->purchases->campaignFacts($campaignId);
        if ($holdings->pluck('commitment_id')->all() !== array_keys($facts)
            || DB::table('business_campaign_closures')->where('business_campaign_id', $campaignId)->exists()) {
            throw new RuntimeException('PRIMARY_ISSUANCE_INTEGRITY_FAILED');
        }
        $purchases = array_column($funding['commitments'], null, 'commitment_id');
        $certificates = [];
        $verified = [];
        foreach ($holdings as $holding) {
            $purchase = $purchases[$holding->commitment_id];
            $payload = $holding->payload;
            $issueId = $payload['cash']['issue_entry_id'] ?? null;
            if (! is_string($issueId)) {
                throw new RuntimeException('PRIMARY_ISSUANCE_INTEGRITY_FAILED');
            }
            $expected = PrimaryIssuancePayload::holding($facts[$holding->commitment_id], $purchase, $closing,
                $funding['funding_id'], $holding->id, $holding->receipt_id, $issueId);
            $native = ['holding_id' => $holding->id, ...array_combine(array_keys($facts[$holding->commitment_id]),
                array_map(fn (string $key): mixed => $holding->getAttribute($key), array_keys($facts[$holding->commitment_id]))),
                'disbursement_closing_id' => $holding->disbursement_closing_id, 'schedule' => $holding->schedule,
                'issued_at' => $holding->issued_at->utc()->toIso8601String(), 'disbursement_effective_at' => $holding->disbursement_effective_at->utc()->toIso8601String(),
                'effective_date' => $holding->effective_date->format('Y-m-d')];
            if ($this->json->encode($payload) !== $this->json->encode($expected)
                || ! hash_equals($holding->sha256, hash('sha256', $this->json->encode($payload)))
                || $this->json->encode($native) !== $this->json->encode(array_intersect_key($expected, $native))
                || $holding->created_at->utc()->toIso8601String() !== $closing->recordedAt) {
                throw new RuntimeException('PRIMARY_ISSUANCE_INTEGRITY_FAILED');
            }
            $this->cash->verify($purchase['party_id'], $purchase['cash']['wallet_id'], WalletMoney::of($purchase['principal']),
                new PostingSource('primary_reservation', $purchase['reservation_id'], $purchase['cash']['origin_operation_id']),
                $purchase['cash']['hold_entry_id'], $purchase['cash']['commit_entry_id'], $issueId,
                new PostingCause('disbursement_closing', $closing->closingId));
            $certificates[] = ['id' => $holding->id, 'commitment_id' => $holding->commitment_id, 'sha256' => $holding->sha256];
            $verified[] = $expected;
        }
        $expected = PrimaryIssuancePayload::campaign($closing, $funding['funding_id'], $certificates);
        if ($this->json->encode($record->payload) !== $this->json->encode($expected)
            || ! hash_equals($record->sha256, hash('sha256', $this->json->encode($record->payload)))
            || ! hash_equals($record->holdings_sha256, hash('sha256', $this->json->encode(['holdings' => $certificates])))
            || [$record->getKey(), $record->business_id, $record->funding_id, $record->exposure_reservation_id, $record->principal, $record->commitment_count,
                $record->created_at->utc()->toIso8601String()]
                !== [$campaignId, $campaign->businessId, $funding['funding_id'], $campaign->exposureReservationId, $campaign->principal, count($facts), $closing->recordedAt]) {
            throw new RuntimeException('PRIMARY_ISSUANCE_INTEGRITY_FAILED');
        }

        return [...$expected, 'issued_holdings' => $verified];
    }
}
