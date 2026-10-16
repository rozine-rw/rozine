<?php

declare(strict_types=1);

namespace App\Infrastructure\Primary;

use App\Application\Disbursement\ClosingEvidence;
use App\Application\Disbursement\Contracts\DisbursementClosingEvidence;
use App\Application\Disbursement\FailedClosing;
use App\Application\Disbursement\FundedCampaign;
use App\Application\Disbursement\IssueInstruction;
use RuntimeException;

/**
 * Authenticates the driver's instruction against Disbursement-owned closing authority and the
 * caller's already authenticated, locked retained funding facts. No locks or writes: the caller
 * retains Business -> staff -> campaign/disbursement -> roots/commitments -> wallets through
 * commit. This supplies closing authority only, never current admission or permission to refund,
 * issue, convert exposure or recycle inventory. Missing evidence propagates as unavailable;
 * a mismatch is integrity failure, never an evidenced failed condition.
 */
final class RetainedFundedClosing
{
    public function __construct(private DisbursementClosingEvidence $closings) {}

    public function issued(FundedCampaign $campaign, IssueInstruction $instruction): ClosingEvidence
    {
        $closing = $this->bound($campaign, $instruction->closingId, $instruction->disbursementId);
        if ([$closing->kind, $closing->cause, $closing->causes, $closing->intentOperationId, $closing->effectiveAt,
            $closing->effectiveDate, $closing->dueDates, $closing->recordedAt]
            !== ['issued', 'reconciled_success', [], $instruction->intentOperationId, $instruction->effectiveAt,
                $instruction->effectiveDate, $instruction->dueDates, $instruction->issuedAt]) {
            throw new RuntimeException('PRIMARY_CLOSING_MISMATCH');
        }

        return $closing;
    }

    public function failed(FundedCampaign $campaign, FailedClosing $instruction): ClosingEvidence
    {
        $closing = $this->bound($campaign, $instruction->closingId, $instruction->disbursementId);
        if ([$closing->kind, $closing->cause, $closing->causes]
            !== ['failed_closing', $instruction->cause, $instruction->causes]) {
            throw new RuntimeException('PRIMARY_CLOSING_MISMATCH');
        }

        return $closing;
    }

    private function bound(FundedCampaign $campaign, string $closingId, string $disbursementId): ClosingEvidence
    {
        $closing = $this->closings->find($closingId);
        if ([$closing->closingId, $closing->disbursementId, $closing->campaignId, $closing->businessId,
            $closing->exposureReservationId, $closing->amount, $closing->currency, $closing->commitmentsDigest,
            $closing->commitmentCount, $closing->termMonths]
            !== [$closingId, $disbursementId, $campaign->campaignId, $campaign->businessId,
                $campaign->exposureReservationId, $campaign->principal, 'RWF', $campaign->commitmentsDigest(),
                count($campaign->commitments), $campaign->termMonths]) {
            throw new RuntimeException('PRIMARY_CLOSING_MISMATCH');
        }

        return $closing;
    }
}
