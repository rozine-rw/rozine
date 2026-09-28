<?php

declare(strict_types=1);

use App\Domain\Wallet\JournalEntry;
use App\Domain\Wallet\JournalLine;
use App\Domain\Wallet\WalletMoney;
use App\Domain\Wallet\WalletViolation;

/** @return list<array{string, string, string}> */
function journalLines(JournalEntry $entry): array
{
    return array_map(fn (JournalLine $line): array => [$line->account, $line->direction, $line->amount->amount()], $entry->lines);
}

it('posts a zero-fee deposit credit as one clearing debit and one available credit', function (): void {
    $entry = JournalEntry::depositCredit(WalletMoney::of('5000'), WalletMoney::zero());
    expect($entry->kind)->toBe('deposit_credit')
        ->and(journalLines($entry))->toBe([['deposit_clearing', 'debit', '5000'], ['investor_available', 'credit', '5000']]);
});

it('splits a synthetic nonzero fee so the gross clearing debit equals the net credit plus the fee credit', function (): void {
    $entry = JournalEntry::depositCredit(WalletMoney::of('5000'), WalletMoney::of('150'));
    expect(journalLines($entry))->toBe([['deposit_clearing', 'debit', '5000'], ['investor_available', 'credit', '4850'], ['deposit_fee_revenue', 'credit', '150']])
        ->and(fn () => JournalEntry::depositCredit(WalletMoney::of('150'), WalletMoney::of('150')))->toThrow(WalletViolation::class, 'JOURNAL_LINE_INVALID')
        ->and(fn () => JournalEntry::depositCredit(WalletMoney::of('100'), WalletMoney::of('150')))->toThrow(WalletViolation::class, 'WALLET_MONEY_NEGATIVE');
});

it('cannot be built unbalanced, one-sided, empty or with an invalid line', function (): void {
    $debit = new JournalLine('deposit_clearing', 'debit', WalletMoney::of('10'));
    $credit = new JournalLine('investor_available', 'credit', WalletMoney::of('10'));
    expect(JournalEntry::balanced('deposit_credit', [$debit, $credit])->lines)->toHaveCount(2)
        ->and(fn () => JournalEntry::balanced('deposit_credit', [$debit, new JournalLine('investor_available', 'credit', WalletMoney::of('9'))]))
        ->toThrow(WalletViolation::class, 'JOURNAL_ENTRY_UNBALANCED')
        ->and(fn () => JournalEntry::balanced('deposit_credit', [$credit, $credit]))->toThrow(WalletViolation::class, 'JOURNAL_ENTRY_UNBALANCED')
        ->and(fn () => JournalEntry::balanced('deposit_credit', []))->toThrow(WalletViolation::class, 'JOURNAL_ENTRY_UNBALANCED')
        ->and(fn () => new JournalLine('suspense', 'debit', WalletMoney::of('10')))->toThrow(WalletViolation::class, 'JOURNAL_LINE_INVALID')
        ->and(fn () => new JournalLine('deposit_clearing', 'across', WalletMoney::of('10')))->toThrow(WalletViolation::class, 'JOURNAL_LINE_INVALID')
        ->and(fn () => new JournalLine('deposit_clearing', 'debit', WalletMoney::zero()))->toThrow(WalletViolation::class, 'JOURNAL_LINE_INVALID');
});
