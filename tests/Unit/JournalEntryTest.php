<?php

declare(strict_types=1);

use App\Domain\Wallet\JournalEntry;
use App\Domain\Wallet\JournalLine;
use App\Domain\Wallet\PrimaryPosting;
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
        ->and(fn () => JournalEntry::depositCredit(WalletMoney::of('150'), WalletMoney::of('150')))->toThrow(WalletViolation::class, 'DEPOSIT_NET_NOT_POSITIVE')
        ->and(fn () => JournalEntry::depositCredit(WalletMoney::of('100'), WalletMoney::of('150')))->toThrow(WalletViolation::class, 'DEPOSIT_NET_NOT_POSITIVE')
        ->and(fn () => JournalEntry::depositCredit(WalletMoney::zero(), WalletMoney::zero()))->toThrow(WalletViolation::class, 'DEPOSIT_NET_NOT_POSITIVE');
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

it('moves primary purchase money between the Investor buckets as one balanced entry', function (string $kind, string $from, string $to): void {
    expect(journalLines(JournalEntry::primary($kind, WalletMoney::of('25000'))))->toBe([[$from, 'debit', '25000'], [$to, 'credit', '25000']]);
})->with([
    ['primary_hold', 'investor_available', 'investor_held'],
    ['primary_commit', 'investor_held', 'investor_committed'],
    ['primary_release', 'investor_held', 'investor_available'],
    ['primary_refund', 'investor_committed', 'investor_available'],
]);

it('refuses unknown or empty primary movements', function (): void {
    expect(fn () => JournalEntry::primary('primary_transfer', WalletMoney::of('1')))->toThrow(WalletViolation::class, 'WALLET_POSTING_KIND_INVALID')
        ->and(fn () => JournalEntry::primary('primary_hold', WalletMoney::zero()))->toThrow(WalletViolation::class, 'JOURNAL_LINE_INVALID');
});

it('allows each primary movement only in its lifecycle order', function (string $kind, string $recorded, bool $allowed): void {
    $check = fn () => PrimaryPosting::assertAllowed($kind, array_values(array_filter(explode(',', $recorded))));
    $allowed ? expect($check)->not->toThrow(WalletViolation::class)
        : expect($check)->toThrow(WalletViolation::class, $kind === 'primary_move' ? 'WALLET_POSTING_KIND_INVALID' : 'WALLET_POSTING_STATE_INVALID');
})->with([
    'hold opens' => ['primary_hold', '', true],
    'hold twice' => ['primary_hold', 'primary_hold', false],
    'commit a hold' => ['primary_commit', 'primary_hold', true],
    'release a hold' => ['primary_release', 'primary_hold', true],
    'commit without a hold' => ['primary_commit', '', false],
    'release without a hold' => ['primary_release', '', false],
    'commit after release' => ['primary_commit', 'primary_hold,primary_release', false],
    'release after commit' => ['primary_release', 'primary_hold,primary_commit', false],
    'commit twice' => ['primary_commit', 'primary_hold,primary_commit', false],
    'refund a commit' => ['primary_refund', 'primary_hold,primary_commit', true],
    'refund a released hold' => ['primary_refund', 'primary_hold,primary_release', false],
    'refund a hold' => ['primary_refund', 'primary_hold', false],
    'refund twice' => ['primary_refund', 'primary_hold,primary_commit,primary_refund', false],
    'unknown' => ['primary_move', '', false],
]);
