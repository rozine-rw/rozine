<?php

declare(strict_types=1);

namespace App\Application\Primary\Contracts;

/**
 * The Holdings issued to an account's Party, as the primary issue recorded them: units, ordinals,
 * rights, terms, the anchored schedule and the issue receipt. Takes no lock and grants nothing: the
 * caller has already checked current Investor authority. Another Party's Holding is never
 * returned, so an unknown id and a foreign one read the same.
 *
 * @phpstan-type Money array{currency: string, amount: string}
 * @phpstan-type IssuedHoldingRecord array{
 *     id: string, campaign_id: string, commitment_id: string, units: string, principal: Money,
 *     ordinals: list<array{first: string, last: string}>,
 *     rights: array{total_return: Money, instalments: list<array{index: int, principal: Money, return: Money}>},
 *     terms: array{rate_pct: string, term_months: int, payout_fee: Money, earnings_fee: array{tier: string, rate_bps: int, basis: string, policy_version: string},
 *         policy_version: string, disclosure_version: string},
 *     schedule: list<array{index: int, due_on: string, principal: Money, return: Money}>,
 *     issued_at: string, disbursement_effective_at: string, effective_date: string,
 *     receipt: array{receipt_id: string, operation_id: string, request_id: string}
 * }
 */
interface InvestorHoldings
{
    /**
     * Every Holding of the account's Party, newest issue first.
     *
     * @return list<IssuedHoldingRecord>
     */
    public function forInvestor(int $userId): array;

    /** @return IssuedHoldingRecord|null */
    public function find(int $userId, string $holdingId): ?array;

    /**
     * The Party's Holding issued from one of its commitments, or null while none is.
     *
     * @return IssuedHoldingRecord|null
     */
    public function ofCommitment(int $userId, string $commitmentId): ?array;
}
