<?php

declare(strict_types=1);

namespace App\Application\Business\Contracts;

/**
 * Pays a collected repayment on to the note's holders (BRS v3 SVC-R03/R04, SEC-R07): each lot
 * held when the repayment was collected receives its units' share of the instalments paid, less
 * the earnings fee on the return. Each call runs its own transaction outside any other and is
 * idempotent: a repayment already paid on posts nothing again.
 */
interface ServicingPayouts
{
    /** Pays one repayment on; returns how many lots were paid now (0 when already paid or nothing is bound). */
    public function payOut(string $repaymentId): int;

    /**
     * Repayments recorded but not yet paid on, oldest first.
     *
     * @return list<string>
     */
    public function unpaid(int $limit): array;
}
