<?php

declare(strict_types=1);

namespace App\Infrastructure\Business;

use App\Application\Business\Contracts\NoteServicing;
use App\Application\Business\Contracts\ServicingStatements;
use App\Application\Business\ServicingQuote;
use App\Domain\Operations\CommandRejection;
use App\Domain\Wallet\WalletMoney;

/**
 * No servicing domain is bound yet (S4-B). No note is a servicing note, so `repayment.pay` is refused
 * `NOTE_NOT_SERVICING` and nothing is debited; no schedule or amount is fabricated, and no Business has
 * a servicing statement.
 */
final class UnavailableNoteServicing implements NoteServicing, ServicingStatements
{
    public function lockForPayment(string $businessId, string $noteId): ?ServicingQuote
    {
        return null;
    }

    public function applyRepayment(string $noteId, string $repaymentId, string $option, WalletMoney $amount, int $expectedRevision): int
    {
        throw new CommandRejection('NOTE_NOT_SERVICING', 404);
    }

    public function current(string $businessId): ?array
    {
        return null;
    }
}
