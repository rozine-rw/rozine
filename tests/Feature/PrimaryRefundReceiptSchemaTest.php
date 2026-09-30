<?php

declare(strict_types=1);

use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Models\CommandOperation;
use App\Models\LedgerEntry;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $this->campaign = PrimaryReservationFixture::campaign();
    $this->investor = PrimaryReservationFixture::investor();
    $this->checkout = app(PrimaryCheckout::class);
    $reserved = $this->checkout->reserve($this->investor['user']->id, 1, $this->campaign->id, '3', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $this->root = PrimaryReservationRecord::query()->whereKey($reserved['data']['reservation_id'])->sole();
    $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $this->root->id)->sole();
    $this->checkout->confirm($this->investor['user']->id, 1, $this->campaign->id, $this->root->id, 1,
        $version->payload['terms']['disclosure_version'], $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    $this->refund = fn (): array => $this->checkout->refund($this->investor['user']->id, 1, $this->campaign->id, $this->root->id, 2, (string) Str::uuid());
    $this->migration = require database_path('migrations/2026_09_30_120729_bind_primary_refund_receipts_to_returned_cash.php');
});

it('requires real returned cash before accepting a successful refund receipt', function (): void {
    $id = strtolower((string) Str::ulid());
    $operation = CommandOperation::factory()->make(['id' => $id, 'actor_key' => 'party:'.$this->investor['party']->id,
        'actor_user_id' => $this->investor['user']->id, 'command' => 'primary.refund', 'target_type' => 'primary_reservation', 'target_id' => $this->root->id,
        'result' => ['operation_id' => $id, 'status' => 'completed', 'code' => 'COMMITMENT_REFUNDED', 'revision' => 2,
            'data' => ['reservation_id' => $this->root->id, 'entry_id' => strtolower((string) Str::ulid()), 'amount' => '15000', 'currency' => 'RWF', 'fee' => '0']]]);
    expect(fn () => DB::transaction(function () use ($operation): void {
        $operation->save();
        DB::statement('SET CONSTRAINTS primary_refund_receipt_bound IMMEDIATE');
    }))->toThrow(QueryException::class, 'exact retained purchase and cash return')
        ->and(CommandOperation::query()->where('command', 'primary.refund')->count())->toBe(0)
        ->and(LedgerEntry::query()->where('kind', 'primary_refund')->count())->toBe(0);
});

it('binds every successful refund receipt field to the original purchase cash and actor', function (string $field): void {
    $result = ($this->refund)();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    $operation = CommandOperation::query()->whereKey($result['operation_id'])->sole()->replicate();
    $operation->id = strtolower((string) Str::ulid());
    $operation->request_id = (string) Str::uuid();
    $receipt = $operation->result;
    $receipt['operation_id'] = $operation->id;
    if (in_array($field, ['target_type', 'target_id', 'actor_key', 'actor_user_id'], true)) {
        $operation->setAttribute($field, match ($field) {
            'target_type' => 'campaign', 'target_id' => $this->campaign->id,
            'actor_key' => 'party:'.strtolower((string) Str::ulid()), 'actor_user_id' => $this->investor['user']->id + 100000,
        });
    } elseif (in_array($field, ['status', 'code', 'revision', 'operation_id'], true)) {
        $receipt[$field] = match ($field) {
            'status' => 'rejected', 'code' => 'RESERVATION_CONFIRMED', 'revision' => 1,
            'operation_id' => $result['operation_id'],
        };
    } else {
        $receipt['data'][$field] = match ($field) {
            'amount' => '14999', 'fee' => '1', 'currency' => 'USD', default => strtolower((string) Str::ulid()),
        };
    }
    $operation->result = $receipt;
    expect(fn () => DB::transaction(function () use ($operation): void {
        $operation->save();
        DB::statement('SET CONSTRAINTS primary_refund_receipt_bound IMMEDIATE');
    }))->toThrow(QueryException::class, 'exact retained purchase and cash return')
        ->and(CommandOperation::query()->where('command', 'primary.refund')->count())->toBe(1)
        ->and(LedgerEntry::query()->where('kind', 'primary_refund')->count())->toBe(1);
})->with(['target_type', 'target_id', 'actor_key', 'actor_user_id', 'status', 'code', 'revision', 'operation_id',
    'reservation_id', 'commitment_id', 'entry_id', 'origin_operation_id', 'amount', 'currency', 'fee']);

it('accepts either insertion order and multiple receipts for the same exact refund', function (bool $receiptFirst): void {
    $captured = [];
    try {
        DB::transaction(function () use (&$captured): void {
            $result = ($this->refund)();
            $captured = ['operation' => CommandOperation::query()->whereKey($result['operation_id'])->sole()->getAttributes(),
                'entry' => LedgerEntry::query()->whereKey($result['data']['entry_id'])->sole()->getAttributes(),
                'lines' => DB::table('ledger_lines')->where('entry_id', $result['data']['entry_id'])->get()->map(fn (object $line): array => (array) $line)->all()];
            throw new RuntimeException('ROLL_BACK_CAPTURED_REFUND');
        });
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toBe('ROLL_BACK_CAPTURED_REFUND');
    }
    expect(LedgerEntry::query()->where('kind', 'primary_refund')->count())->toBe(0);
    DB::transaction(function () use ($captured, $receiptFirst): void {
        if ($receiptFirst) {
            DB::table('command_operations')->insert($captured['operation']);
        }
        DB::table('ledger_entries')->insert($captured['entry']);
        DB::table('ledger_lines')->insert($captured['lines']);
        if (! $receiptFirst) {
            DB::table('command_operations')->insert($captured['operation']);
        }
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
        DB::statement('SET CONSTRAINTS ALL DEFERRED');
    });
    expect(($this->refund)()['data']['entry_id'])->toBe($captured['entry']['id'])
        ->and(CommandOperation::query()->where('command', 'primary.refund')->count())->toBe(2)
        ->and(LedgerEntry::query()->where('kind', 'primary_refund')->count())->toBe(1);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
})->with([false, true]);

it('permits truthful rejected refund outcomes without inventing cash', function (): void {
    $result = $this->checkout->refund($this->investor['user']->id, 1, $this->campaign->id, $this->root->id, 1, (string) Str::uuid());
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    expect($result['code'])->toBe('VERSION_CONFLICT')->and($result['status'])->toBe('rejected')
        ->and(LedgerEntry::query()->where('kind', 'primary_refund')->count())->toBe(0);
});

it('restores the empty receipt guard and audits valid retained receipts without rewriting evidence', function (): void {
    $this->migration->down();
    ($this->refund)();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    $before = CommandOperation::query()->where('command', 'primary.refund')->get()->toJson();
    $this->migration->up();
    expect(CommandOperation::query()->where('command', 'primary.refund')->get()->toJson())->toBe($before)
        ->and(fn () => $this->migration->down())->toThrow(QueryException::class, 'forward migration');
});

it('refuses an install over a historical receipt with no returned cash', function (): void {
    $this->migration->down();
    CommandOperation::factory()->create(['actor_key' => 'party:'.$this->investor['party']->id, 'actor_user_id' => $this->investor['user']->id,
        'command' => 'primary.refund', 'target_type' => 'primary_reservation', 'target_id' => $this->root->id,
        'result' => ['status' => 'completed', 'code' => 'COMMITMENT_REFUNDED']]);
    expect(fn () => $this->migration->up())->toThrow(QueryException::class, 'exact retained purchase and cash return')
        ->and(DB::scalar("SELECT EXISTS (SELECT 1 FROM pg_trigger WHERE tgname = 'primary_refund_receipt_bound')"))->toBeFalse()
        ->and(CommandOperation::query()->where('command', 'primary.refund')->count())->toBe(1);
});

it('verifies actual retained ledger facts instead of accepting a matching receipt alone', function (string $damage): void {
    $result = ($this->refund)();
    $other = PrimaryReservationFixture::investor();
    $otherWallet = DB::table('investor_wallets')->where('party_id', $other['party']->id)->value('id');
    $otherAvailable = DB::table('ledger_accounts')->where('wallet_id', $otherWallet)->where('kind', 'investor_available')->value('id');
    $ownHeld = DB::table('ledger_accounts')->where('wallet_id', LedgerEntry::query()->whereKey($result['data']['entry_id'])->value('wallet_id'))
        ->where('kind', 'investor_held')->value('id');
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('ALTER TABLE ledger_entries DISABLE TRIGGER USER');
    DB::statement('ALTER TABLE ledger_lines DISABLE TRIGGER USER');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    $credit = DB::table('ledger_lines')->where('entry_id', $result['data']['entry_id'])->where('direction', 'credit');
    $extra = LedgerEntry::query()->whereKey($result['data']['entry_id'])->sole()->getAttributes();
    $extra['id'] = strtolower((string) Str::ulid());
    $extra['kind'] = 'primary_release';
    match ($damage) {
        'amount' => $credit->update(['amount' => '14999']),
        'foreign_account' => $credit->update(['account_id' => $otherAvailable]),
        'wrong_bucket' => $credit->update(['account_id' => $ownHeld]),
        'missing_line' => $credit->delete(),
        'wallet' => DB::table('ledger_entries')->where('id', $result['data']['entry_id'])->update(['wallet_id' => $otherWallet]),
        'origin' => DB::table('ledger_entries')->where('id', $result['data']['entry_id'])->update(['origin_operation_id' => $result['operation_id']]),
        'extra_entry' => DB::table('ledger_entries')->insert($extra),
        default => throw new InvalidArgumentException('Unknown refund cash damage.'),
    };
    $operation = CommandOperation::query()->whereKey($result['operation_id'])->sole()->replicate();
    $operation->id = strtolower((string) Str::ulid());
    $operation->request_id = (string) Str::uuid();
    $receipt = $operation->result;
    $receipt['operation_id'] = $operation->id;
    $operation->result = $receipt;
    expect(fn () => DB::transaction(function () use ($operation): void {
        $operation->save();
        DB::statement('SET CONSTRAINTS primary_refund_receipt_bound IMMEDIATE');
    }))->toThrow(QueryException::class, 'Primary refund receipt')
        ->and(CommandOperation::query()->where('command', 'primary.refund')->count())->toBe(1);
})->with(['amount', 'foreign_account', 'wrong_bucket', 'missing_line', 'wallet', 'origin', 'extra_entry']);

it('refuses an install over historical refunded principal with damaged credit evidence', function (): void {
    $this->migration->down();
    $result = ($this->refund)();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('ALTER TABLE ledger_lines DISABLE TRIGGER USER');
    DB::table('ledger_lines')->where('entry_id', $result['data']['entry_id'])->where('direction', 'credit')->update(['amount' => '14999']);
    expect(fn () => $this->migration->up())->toThrow(QueryException::class, 'exact original principal')
        ->and(DB::scalar("SELECT EXISTS (SELECT 1 FROM pg_trigger WHERE tgname = 'primary_refund_receipt_bound')"))->toBeFalse();
});
