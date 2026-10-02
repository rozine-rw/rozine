<?php

declare(strict_types=1);

namespace App\Application\Disbursement\Contracts;

use App\Application\Disbursement\ClosingEvidence;

/**
 * The retained closing a funding source issues or fails closing against (#96 5924339274,
 * 5925426310). `FundedCampaigns::issue` and `::failClose` call it with their instruction's closing
 * id, compare it with that instruction and their own locked campaign facts, and refuse on any
 * difference.
 *
 * It runs only inside the caller's open transaction, which already holds Business → staff users →
 * campaign → disbursement, and refuses `DISBURSEMENT_CLOSING_TRANSACTION_REQUIRED` outside one. It
 * only SELECTs: no row locks, no writes and no current funding, destination or staff state. A
 * missing or unreadable source refuses `DISBURSEMENT_CLOSING_UNAVAILABLE`; a stored record that
 * contradicts itself or its ancestry refuses `DISBURSEMENT_CLOSING_INTEGRITY_FAILED`. Neither is
 * ever an evidenced financial failure.
 *
 * It is safe to call from inside the closing's own effect. An approve-time closing (cause
 * `approve_recheck`) is recorded before the operation journal records its approve command, so its
 * command authority (`disbursement.approve`, this disbursement, the approve's actor and request)
 * is retained in the closing itself, digested, and the deferred `disbursement_closings_authority`
 * trigger refuses the outer commit unless the recorded command matches it and its receipt names
 * the closing; `find` checks the command whenever it is visible. Reconciled closings are read from
 * the observation their reconciliation selected, never from later observations or current
 * provider state, and `recordedAt` is the closing's one recorded instant (its native `created_at`,
 * its payload and an issue's `issuedAt`). No terminal disbursement event is required.
 */
interface DisbursementClosingEvidence
{
    public function find(string $closingId): ClosingEvidence;
}
