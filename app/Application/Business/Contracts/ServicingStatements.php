<?php

declare(strict_types=1);

namespace App\Application\Business\Contracts;

/**
 * Where the Business's repaying note stands, for its Repayments sheet (business-servicing-v1): the
 * schedule with what each instalment was paid, what is due and payable now, its progress and its
 * recent repayments. Null while no note of the Business is servicing. Read only; it never locks.
 *
 * @phpstan-type Money array{currency: 'RWF', amount: string}
 * @phpstan-type Components array{principal: Money, return: Money, late_fees: Money, total: Money}
 * @phpstan-type Instalment array{index: int, due_on: string, scheduled: Components, paid: Components, outstanding: Components,
 *     status: 'upcoming'|'due'|'overdue'|'processing'|'paid', dpd: int|null, paid_on: string|null, late_fees: list<never>}
 * @phpstan-type Option array{key: 'due_now'|'next_instalment', amounts: Components, instalment_indexes: list<int>}
 * @phpstan-type Recent array{id: string, request_id: string, revision: int, note_title: string, amount: Money, applied: Components, unapplied: Money,
 *     allocation: 'allocating'|'allocated', investors_paid: int|null, receipt: array<string, mixed>}
 * @phpstan-type Statement array{note: array{id: string, title: string, disbursement_effective_date: string, due_day: int},
 *     servicing: array{note_id: string, revision: int, state: 'current'|'due_today'|'overdue'|'repaid', dpd: int|null,
 *         progress: array{repaid: Money, total: Money, remaining: Money, repaid_pct: string, payments_made: int, payments_total: int, remaining_instalments: int},
 *         next: array{index: int, due_on: string, amounts: Components}|null, due_now: Components|null, next_late_fee: null, restriction: null, autocollect: null},
 *     schedule: list<Instalment>, options: list<Option>, recent: list<Recent>}
 */
interface ServicingStatements
{
    /** @return Statement|null */
    public function current(string $businessId): ?array;
}
