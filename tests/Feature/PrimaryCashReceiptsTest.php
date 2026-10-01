<?php

declare(strict_types=1);

use App\Application\Primary\Contracts\CampaignFundingEvidence;
use App\Application\Wallet\Contracts\PrimaryCashReceipts;
use App\Application\Wallet\PostingSource;
use App\Domain\Wallet\WalletMoney;
use App\Domain\Wallet\WalletViolation;
use App\Models\LedgerEntry;
use App\Models\LedgerLine;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\PrimaryHoldingFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    ['campaign' => $campaign] = PrimaryHoldingFixture::committed();
    $this->funding = app(CampaignFundingEvidence::class)->find($campaign->id);
    $this->purchase = $this->funding['commitments'][0];
    $this->cash = $this->purchase['cash'];
    $this->verify = function (array $overrides = []): void {
        $values = [...$this->cash, 'party_id' => $this->purchase['party_id'], 'reservation_id' => $this->purchase['reservation_id'],
            'source_type' => 'primary_reservation', ...$overrides];
        app(PrimaryCashReceipts::class)->verify($values['party_id'], $values['wallet_id'], WalletMoney::of($values['amount']),
            new PostingSource($values['source_type'], $values['reservation_id'], $values['origin_operation_id']), $values['hold_entry_id'], $values['commit_entry_id']);
    };
});

it('reads original receipts without taking locks writing or inspecting current balances', function (): void {
    $queries = [];
    DB::listen(function (QueryExecuted $query) use (&$queries): void {
        $queries[] = strtolower($query->sql);
    });
    ($this->verify)();
    expect($queries)->toHaveCount(4);
    foreach ($queries as $query) {
        expect($query)->toStartWith('select')->not->toContain('for update', 'for share', 'sum(', 'transaction_isolation');
    }
});

it('refuses the wrong original cash ownership or references', function (string $field): void {
    $other = $this->funding['commitments'][1];
    $value = match ($field) {
        'party_id' => $other['party_id'], 'wallet_id' => $other['cash']['wallet_id'],
        'hold_entry_id', 'commit_entry_id' => strtolower((string) Str::ulid()),
        'source_type' => 'primary_commitment', 'amount' => '0',
        default => throw new InvalidArgumentException('Unknown cash field.'),
    };
    expect(fn () => ($this->verify)([$field => $value]))->toThrow(WalletViolation::class, 'PRIMARY_CASH_RECEIPT_INTEGRITY_FAILED');
})->with(['party_id', 'wallet_id', 'hold_entry_id', 'commit_entry_id', 'source_type', 'amount']);

it('refuses relational cash rows that disagree with the authenticated receipts', function (string $damage): void {
    $entry = LedgerEntry::query()->whereKey($this->cash['hold_entry_id'])->sole();
    DB::statement('ALTER TABLE ledger_entries DISABLE TRIGGER ledger_entries_protected');
    DB::statement('ALTER TABLE ledger_lines DISABLE TRIGGER ledger_lines_protected');
    try {
        if ($damage === 'missing_line') {
            LedgerLine::query()->where('entry_id', $entry->id)->where('direction', 'credit')->delete();
        } elseif ($damage === 'line_amount') {
            LedgerLine::query()->where('entry_id', $entry->id)->update(['amount' => '5000']);
        } else {
            $entry->forceFill([$damage => $damage === 'kind' ? 'primary_release' : $this->funding['commitments'][1]['cash']['wallet_id']])->save();
        }
    } finally {
        DB::statement('ALTER TABLE ledger_entries ENABLE TRIGGER ledger_entries_protected');
        DB::statement('ALTER TABLE ledger_lines ENABLE TRIGGER ledger_lines_protected');
    }
    expect(fn () => ($this->verify)())->toThrow(WalletViolation::class, 'PRIMARY_CASH_RECEIPT_INTEGRITY_FAILED');
})->with(['missing_line', 'line_amount', 'kind', 'wallet_id']);
