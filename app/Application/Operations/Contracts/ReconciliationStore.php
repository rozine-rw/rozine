<?php

declare(strict_types=1);

namespace App\Application\Operations\Contracts;

use DateTimeInterface;

/**
 * The money the core reconciles against its payment provider (MVP-ADMIN-SCR-04, AC-05). Reads
 * only and never locks; the caller checks the staff permission.
 *
 * Each account is a provider clearing account in the wallet ledger, side by side with what the
 * provider's signed outcomes say moved through it:
 * - `deposits`: the `deposit_clearing` balance (every credited Investor and Business deposit)
 *   against every successful deposit the provider reported, whether or not it was credited.
 * - `withdrawals`: the `withdrawal_clearing` balance (every paid Investor and Business withdrawal,
 *   provider charge included) against every successful payout the provider reported plus its
 *   quoted charge, less any payout it later reported failed.
 *
 * A break is a provider outcome the ledger could not follow: a success after a failure
 * (`conflict`), a failure after a credited success (`reversed`), a success for another amount,
 * currency or environment (`mismatch`), or an event id reused with other content (`key_conflict`).
 * None is ever resolved here: no command assigns or explains a break yet.
 *
 * @phpstan-type Account array{id: 'deposits'|'withdrawals', ledger: string, statement: string}
 * @phpstan-type BreakRecord array{
 *     id: string, account_id: 'deposits'|'withdrawals', flow: 'investor'|'business', kind: 'conflict'|'reversed'|'mismatch'|'key_conflict',
 *     reference: string, amount: string, opened_at: string, party_id: string|null, business_id: string|null
 * }
 */
interface ReconciliationStore
{
    /**
     * Both accounts as of the given instant, counting only postings and outcomes recorded by then.
     *
     * @return list<Account>
     */
    public function accounts(DateTimeInterface $asOf): array;

    /**
     * Every break recorded by the given instant, oldest first.
     *
     * @return list<BreakRecord>
     */
    public function breaks(DateTimeInterface $asOf): array;
}
