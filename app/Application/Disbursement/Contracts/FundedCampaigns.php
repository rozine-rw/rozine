<?php

declare(strict_types=1);

namespace App\Application\Disbursement\Contracts;

use App\Application\Disbursement\FailedClosing;
use App\Application\Disbursement\FundedCampaign;
use App\Application\Disbursement\FundedCampaignRef;
use App\Application\Disbursement\IssueInstruction;
use App\Application\Disbursement\RecheckResult;

/**
 * The S3-C funding source (#96 5871738394, 5871859618). Disbursement never writes Primary or
 * Business records itself; it calls this port. Until the concrete S3-C adapter exists the
 * `UnavailableFundedCampaigns` binding refuses `FUNDING_SOURCE_UNAVAILABLE`, so everything fails
 * closed.
 *
 * Every locking or mutating call runs inside the CALLER's open transaction and refuses outside
 * one. Lock order (confirmed in 5871859618): `lockBusiness` → staff users in ascending id →
 * `lockFunded` (campaign) → disbursement → step-up proof → reservations and commitments in stable
 * id order → wallets in Party id order → ledger.
 *
 * `issue` and `failClose` recheck the current state before writing, are idempotent by closing id,
 * and are mutually exclusive: after one, the other refuses. Both use the original commitment
 * ordinals, rights and terms and the original exposure reservation.
 *
 * TODO(S3-C): the concrete adapter issues Holdings and posts `WalletPostings::issue` (the S3-D
 * wallet extension) per commitment, converts the exposure, and for a failed closing records the
 * funded closure phase through a NEW forward migration and refunds every commitment fee-free.
 */
interface FundedCampaigns
{
    /**
     * Fully funded campaigns, newest first, read without locks.
     *
     * @return list<FundedCampaignRef>
     */
    public function funded(?string $before, int $limit): array;

    /** Locks the Business first, as every Business and Primary path does. */
    public function lockBusiness(string $businessId): void;

    /** Locks the funded campaign after its Business and returns its immutable funding facts. */
    public function lockFunded(string $campaignId): FundedCampaign;

    /** The current pre-disbursement recheck for the locked campaign. It never writes. */
    public function recheck(FundedCampaign $campaign): RecheckResult;

    /** A verified, reconciled success: issue each commitment and convert the exposure, once. */
    public function issue(FundedCampaign $campaign, IssueInstruction $instruction): void;

    /** A deterministic failed closing: cancel, refund every commitment without fee and release the exposure, once. */
    public function failClose(FundedCampaign $campaign, FailedClosing $closing): void;
}
