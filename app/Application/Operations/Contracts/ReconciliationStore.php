<?php

declare(strict_types=1);

namespace App\Application\Operations\Contracts;

use DateTimeInterface;

/**
 * The money the core reconciles against an authoritative statement (MVP-ADMIN-SCR-04, AC-05). Reads
 * only and never locks; the caller checks the staff permission.
 *
 * Each account is a provider clearing account in the wallet ledger, side by side with independently
 * sourced statement evidence at that exact cutoff. Missing evidence is absent, never a zero:
 * - `deposits`: the `deposit_clearing` balance (every credited Investor and Business deposit)
 *   against the authoritative deposit-clearing statement balance.
 * - `withdrawals`: the `withdrawal_clearing` balance (every paid Investor and Business withdrawal,
 *   provider charge included) against the authoritative withdrawal-clearing statement balance.
 *
 * A break is a provider outcome the ledger could not follow: a success after a failure
 * (`conflict`), a failure after a credited success (`reversed`), a success for another amount,
 * currency or environment (`mismatch`), or an event id reused with other content (`key_conflict`).
 * None is ever resolved here: no command assigns or explains a break yet.
 *
 * @phpstan-type Account array{id: 'deposits'|'withdrawals', ledger: string, statement: string, statement_reference?: string}
 * @phpstan-type BreakRecord array{
 *     id: string, account_id: 'deposits'|'withdrawals', flow: 'investor'|'business'|'clearing', kind: 'conflict'|'reversed'|'mismatch'|'key_conflict'|'balance_variance',
 *     reference: string, amount: string, opened_at: string, party_id: string|null, business_id: string|null, cutoff_at?: string, statement_reference?: string
 * }
 */
interface ReconciliationStore
{
    /**
     * Covered accounts at the exact cutoff. The production adapter requires statement provenance.
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
