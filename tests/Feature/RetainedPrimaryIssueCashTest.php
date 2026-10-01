<?php

declare(strict_types=1);

use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Primary\Contracts\CampaignFundingEvidence;
use App\Application\Wallet\PostingCause;
use App\Application\Wallet\PostingSource;
use App\Domain\Wallet\WalletMoney;
use App\Domain\Wallet\WalletViolation;
use App\Infrastructure\Wallet\RetainedPrimaryIssueCash;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerLine;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\PrimaryHoldingFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    ['campaign' => $campaign, 'commitments' => $commitments] = PrimaryHoldingFixture::committed();
    $this->funding = app(CampaignFundingEvidence::class)->find($campaign->id);
    $this->purchase = $this->funding['commitments'][0];
    // Schema-only closing: real cash receipt support, never authenticated financial authority.
    $this->closingId = PrimaryHoldingFixture::issuedClosing($campaign);
    foreach ($commitments as $index => $commitment) {
        PrimaryHoldingFixture::insert($commitment->id, $this->closingId);
        $receipt = PrimaryHoldingFixture::issue($commitment->id, $this->closingId);
        if ($index === 0) {
            $this->issueId = $receipt->entryId;
        } else {
            $this->otherIssueId = $receipt->entryId;
        }
    }
    PrimaryHoldingFixture::flushDeferredChecks();
    $this->verify = function (array $overrides = []): void {
        $cash = [...$this->purchase['cash'], 'party_id' => $this->purchase['party_id'],
            'reservation_id' => $this->purchase['reservation_id'], 'source_type' => 'primary_reservation',
            'issue_id' => $this->issueId, 'closing_id' => $this->closingId, ...$overrides];
        app(RetainedPrimaryIssueCash::class)->verify($cash['party_id'], $cash['wallet_id'], WalletMoney::of($cash['amount']),
            new PostingSource($cash['source_type'], $cash['reservation_id'], $cash['origin_operation_id']),
            $cash['hold_entry_id'], $cash['commit_entry_id'], $cash['issue_id'], new PostingCause('disbursement_closing', $cash['closing_id']));
    };
});

it('authenticates historical issued cash without locks writes or current balance reads', function (): void {
    $before = [LedgerEntry::query()->count(), LedgerLine::query()->count(), $this->funding];
    $queries = [];
    DB::listen(function (QueryExecuted $query) use (&$queries): void {
        $queries[] = strtolower($query->sql);
    });
    ($this->verify)();
    expect($queries)->toHaveCount(6);
    foreach ($queries as $query) {
        expect($query)->toStartWith('select')->not->toContain('for update', 'for share', 'sum(', 'transaction_isolation');
    }
    expect([LedgerEntry::query()->count(), LedgerLine::query()->count(), app(CampaignFundingEvidence::class)->find($this->funding['campaign_id'])])->toBe($before);
});

it('keeps absent issued cash unavailable rather than an evidenced financial failure', function (): void {
    expect(fn () => ($this->verify)(['issue_id' => strtolower((string) Str::ulid())]))
        ->toThrow(WalletViolation::class, 'PRIMARY_ISSUED_CASH_REQUIRED');
});

it('refuses different caller purchase wallet principal or closing bindings', function (string $field): void {
    $other = $this->funding['commitments'][1];
    $value = match ($field) {
        'party_id' => $other['party_id'], 'wallet_id' => $other['cash']['wallet_id'],
        'hold_entry_id', 'commit_entry_id', 'closing_id', 'reservation_id', 'origin_operation_id' => strtolower((string) Str::ulid()),
        'issue_id' => $this->purchase['cash']['commit_entry_id'], 'amount' => '5000',
        'other_issue' => $this->otherIssueId,
        'source_type' => 'primary_commitment', default => throw new InvalidArgumentException('Unknown binding.'),
    };
    expect(fn () => ($this->verify)([$field === 'other_issue' ? 'issue_id' : $field => $value]))->toThrow(WalletViolation::class, 'PRIMARY_CASH_RECEIPT_INTEGRITY_FAILED');
})->with(['party_id', 'wallet_id', 'hold_entry_id', 'commit_entry_id', 'issue_id', 'other_issue', 'closing_id', 'reservation_id', 'origin_operation_id', 'amount', 'source_type']);

it('refuses issue digest or self-consistently redigested envelope corruption', function (string $damage): void {
    $entry = LedgerEntry::query()->whereKey($this->issueId)->sole();
    DB::statement('ALTER TABLE ledger_entries DISABLE TRIGGER ledger_entries_protected');
    try {
        $payload = $entry->payload;
        if ($damage === 'sha256') {
            $entry->forceFill(['sha256' => str_repeat('0', 64)])->save();
        } else {
            if ($damage === 'cause') {
                $payload['cause']['id'] = strtolower((string) Str::ulid());
            } elseif ($damage === 'lines') {
                $payload['lines'][0]['amount'] = '5000';
            } else {
                $payload[$damage] = $damage === 'recorded_at' ? now()->addSecond()->toIso8601String() : 'forged';
            }
            $entry->forceFill(['payload' => $payload, 'sha256' => hash('sha256', app(CanonicalJson::class)->encode($payload))])->save();
        }
    } finally {
        DB::statement('ALTER TABLE ledger_entries ENABLE TRIGGER ledger_entries_protected');
    }
    expect(fn () => ($this->verify)())->toThrow(WalletViolation::class, 'PRIMARY_CASH_RECEIPT_INTEGRITY_FAILED');
})->with(['sha256', 'entry_id', 'wallet_id', 'kind', 'source_type', 'source_id', 'origin_operation_id', 'recorded_at', 'cause', 'lines']);

it('refuses native issue cause or wallet mismatches despite an unchanged valid digest', function (string $damage): void {
    $entry = LedgerEntry::query()->whereKey($this->issueId)->sole();
    $value = match ($damage) {
        'wallet_id' => $this->funding['commitments'][1]['cash']['wallet_id'],
        'cause_id' => strtolower((string) Str::ulid()),
        'origin_operation_id' => $this->funding['commitments'][1]['cash']['origin_operation_id'],
        default => throw new InvalidArgumentException('Unknown native field.'),
    };
    DB::statement('ALTER TABLE ledger_entries DISABLE TRIGGER ledger_entries_protected');
    try {
        $entry->forceFill([$damage => $value])->save();
    } finally {
        DB::statement('ALTER TABLE ledger_entries ENABLE TRIGGER ledger_entries_protected');
    }
    expect(fn () => ($this->verify)())->toThrow(WalletViolation::class, 'PRIMARY_CASH_RECEIPT_INTEGRITY_FAILED');
})->with(['wallet_id', 'cause_id', 'origin_operation_id']);

it('refuses relational settlement movements that disagree with original full principal', function (string $damage): void {
    DB::statement('ALTER TABLE ledger_lines DISABLE TRIGGER ledger_lines_protected');
    try {
        $lines = LedgerLine::query()->where('entry_id', $this->issueId);
        if ($damage === 'missing_credit') {
            $lines->where('direction', 'credit')->delete();
        } elseif ($damage === 'amount') {
            $lines->update(['amount' => '5000']);
        } else {
            $account = LedgerAccount::query()->where('wallet_id', $this->funding['commitments'][1]['cash']['wallet_id'])
                ->where('kind', 'investor_committed')->sole();
            $lines->where('direction', 'credit')->update(['account_id' => $account->id]);
        }
    } finally {
        DB::statement('ALTER TABLE ledger_lines ENABLE TRIGGER ledger_lines_protected');
    }
    expect(fn () => ($this->verify)())->toThrow(WalletViolation::class, 'PRIMARY_CASH_RECEIPT_INTEGRITY_FAILED');
})->with(['missing_credit', 'amount', 'credit_owner']);

it('rechecks original hold and commit rather than trusting an issue alone', function (string $kind): void {
    DB::statement('ALTER TABLE ledger_entries DISABLE TRIGGER ledger_entries_protected');
    try {
        LedgerEntry::query()->whereKey($this->purchase['cash'][$kind.'_entry_id'])->update(['sha256' => str_repeat('0', 64)]);
    } finally {
        DB::statement('ALTER TABLE ledger_entries ENABLE TRIGGER ledger_entries_protected');
    }
    expect(fn () => ($this->verify)())->toThrow(WalletViolation::class, 'PRIMARY_CASH_RECEIPT_INTEGRITY_FAILED');
})->with(['hold', 'commit']);
