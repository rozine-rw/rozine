<?php

declare(strict_types=1);

namespace App\Application\Business\Contracts;

/**
 * Pays a collected repayment on to the note's holders (BRS v3 SVC-R03/R04, SEC-R07): each lot
 * held when the repayment was collected receives its units' share of the instalments paid, less
 * the earnings fee on the return. It runs inside the collecting transaction, so the entitlement is
 * fixed at collection and a later resale cannot move it. Idempotent: a repayment already paid on
 * posts nothing again.
 */
interface ServicingPayouts
{
    /** Pays one repayment on; returns how many lots were paid now (0 when already paid or nothing is bound). */
    public function payOut(string $repaymentId): int;
}
