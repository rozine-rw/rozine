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
 */
interface DisbursementClosingEvidence
{
    public function find(string $closingId): ClosingEvidence;
}
