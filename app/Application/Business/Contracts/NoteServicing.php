<?php

declare(strict_types=1);

namespace App\Application\Business\Contracts;

use App\Application\Business\ServicingQuote;
use App\Domain\Wallet\WalletMoney;

/**
 * The servicing domain's port for `repayment.pay` (S4-B owns the implementation: frozen instalments,
 * the §11.4 order, entitlements). The caller holds the Business lock and its transaction; the port
 * locks the note's servicing state next, before any repayment or wallet row.
 */
interface NoteServicing
{
    /**
     * The note's current payable options, its servicing note locked for the rest of the transaction.
     * Null when the note is not a servicing note of this Business.
     */
    public function lockForPayment(string $businessId, string $noteId): ?ServicingQuote;

    /**
     * Applies a recorded repayment of exactly the quoted option to the note and returns the new
     * servicing revision. Allocation to instalments and entitlements is the servicing domain's.
     */
    public function applyRepayment(string $noteId, string $repaymentId, string $option, WalletMoney $amount, int $expectedRevision): int;
}
