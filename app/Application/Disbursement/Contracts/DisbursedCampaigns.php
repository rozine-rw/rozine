<?php

declare(strict_types=1);

namespace App\Application\Disbursement\Contracts;

/**
 * A campaign's settled payout to its own Business, once its disbursement closed as issued: the
 * principal raised, the platform fee taken, what reached the Business's account (masked), when the
 * payout took effect, the schedule date, and the closing's receipt. Null until then. Only the
 * Business's own pages read it; it never names an Investor.
 *
 * @phpstan-type Money array{currency: string, amount: string}
 * @phpstan-type Disbursed array{gross: Money, platform_fee: Money, amount: Money, destination: string, disbursement_effective_at: string,
 *     effective_date: string, receipt: array{receipt_id: string, operation_id: string, request_id: string, code: string, recorded_at: string, amount: Money,
 *     units: null, reference: string, revision: int, policy_version: string, disclosure_version: null}}
 */
interface DisbursedCampaigns
{
    /** @return Disbursed|null */
    public function find(string $campaignId): ?array;
}
