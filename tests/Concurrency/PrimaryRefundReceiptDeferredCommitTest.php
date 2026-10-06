<?php

declare(strict_types=1);

use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Models\CommandOperation;
use App\Models\LedgerEntry;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

it('defers a receipt-first refund until actual commit without an explicit constraint override', function (string $isolation): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $investor = PrimaryReservationFixture::investor();
    $checkout = app(PrimaryCheckout::class);
    $reserved = $checkout->reserve($investor['user']->id, 1, $campaign->id, '3', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $root = PrimaryReservationRecord::query()->whereKey($reserved['data']['reservation_id'])->sole();
    $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->sole();
    $checkout->confirm($investor['user']->id, 1, $campaign->id, $root->id, 1,
        $version->payload['terms']['disclosure_version'], $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $captured = [];
    try {
        DB::transaction(function () use ($checkout, $investor, $campaign, $root, &$captured): void {
            $result = $checkout->refund($investor['user']->id, 1, $campaign->id, $root->id, 2, (string) Str::uuid());
            $captured = ['operation' => CommandOperation::query()->whereKey($result['operation_id'])->sole()->getAttributes(),
                'entry' => array_diff_key(LedgerEntry::query()->whereKey($result['data']['entry_id'])->sole()->getAttributes(), ['wallet_owner' => true]),
                'lines' => DB::table('ledger_lines')->where('entry_id', $result['data']['entry_id'])->get()->map(fn (object $line): array => (array) $line)->all()];
            throw new RuntimeException('ROLL_BACK_CAPTURED_REFUND');
        });
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toBe('ROLL_BACK_CAPTURED_REFUND');
    }
    expect(DB::transactionLevel())->toBe(0)
        ->and(CommandOperation::query()->where('command', 'primary.refund')->count())->toBe(0)
        ->and(LedgerEntry::query()->where('kind', 'primary_refund')->count())->toBe(0);
    DB::transaction(function () use ($captured, $isolation): void {
        DB::statement(match ($isolation) {
            'REPEATABLE READ' => 'SET TRANSACTION ISOLATION LEVEL REPEATABLE READ',
            'SERIALIZABLE' => 'SET TRANSACTION ISOLATION LEVEL SERIALIZABLE',
            default => 'SET TRANSACTION ISOLATION LEVEL READ COMMITTED',
        });
        DB::table('command_operations')->insert($captured['operation']);
        expect(LedgerEntry::query()->where('kind', 'primary_refund')->count())->toBe(0);
        DB::table('ledger_entries')->insert($captured['entry']);
        DB::table('ledger_lines')->insert($captured['lines']);
    });
    expect(DB::transactionLevel())->toBe(0);
    DB::purge();
    expect(CommandOperation::query()->where('command', 'primary.refund')->sole()->id)->toBe($captured['operation']['id'])
        ->and(LedgerEntry::query()->where('kind', 'primary_refund')->sole()->id)->toBe($captured['entry']['id'])
        ->and(DB::table('ledger_lines')->where('entry_id', $captured['entry']['id'])->count())->toBe(2);
})->with(['READ COMMITTED', 'REPEATABLE READ', 'SERIALIZABLE']);
