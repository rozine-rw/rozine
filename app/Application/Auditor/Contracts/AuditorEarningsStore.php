<?php

declare(strict_types=1);

namespace App\Application\Auditor\Contracts;

use App\Application\Business\Contracts\AuditedRaises;
use DateTimeImmutable;

/**
 * The partner's own earnings statement (BRS v2.0 FEE-R02 and the Audit Partner app's earnings
 * statement): every share of a platform fee credited to them, 40% of the 3% fee on each raise
 * whose sealed report they wrote. A plain read under the current Auditor role; it decides nothing.
 * The 25% share of the earnings fee is credited on Investor payouts, which no read serves yet.
 * What is owed is every credit not yet paid out to the partner. `deals` counts the funded raises that
 * credited the partner (one credit per raise's disbursement). Repayment is not recorded yet, so none
 * of them has closed; once servicing records a repaid note, this count must leave it out.
 * `raising` lists the raises of applications whose Flash Audit the partner sealed that have not
 * funded: live while they take commitments, failed once they closed without funding.
 *
 * @phpstan-type EarningLine array{id: string, business: string, district: string, raise: string, platform_fee: string, amount: string, credited_at: string}
 *
 * @phpstan-import-type Raise from AuditedRaises
 *
 * @phpstan-type Earnings array{month: string, this_month: string, total: string, owed: string, deals: int, lines: list<EarningLine>, managed: list<EarningLine>, raising: list<Raise>}
 */
interface AuditorEarningsStore
{
    /**
     * The current Auditor's credits: the sum credited in the Kigali month holding `$now`, the sum
     * ever credited, what is still owed to them, how many funded raises credited them, and that
     * month's credits, newest first.
     *
     * @return Earnings
     */
    public function statement(int $userId, int $contextRevision, DateTimeImmutable $now): array;
}
