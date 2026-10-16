<?php

declare(strict_types=1);

use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Application\Wallet\Contracts\WalletPostings;
use App\Domain\Operations\CommandRejection;
use App\Domain\Wallet\WalletViolation;
use App\Models\LedgerEntry;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $this->campaign = PrimaryReservationFixture::campaign();
    $this->investor = PrimaryReservationFixture::investor();
    app(PrimaryCheckout::class)->reserve($this->investor['user']->id, 1, $this->campaign->id, '1',
        (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $this->root = PrimaryReservationRecord::query()->sole();
    $this->travelTo($this->root->expires_at);
});

it('records only a closed expiry reason and safe log context while propagating the original failure', function (string $type, string $reason, string $expected): void {
    $exception = match ($type) {
        'wallet' => new WalletViolation($reason),
        'command' => new CommandRejection($reason, data: ['private' => 'must never be logged']),
        default => new RuntimeException($reason),
    };
    $wallet = $this->createMock(WalletPostings::class);
    $wallet->expects($this->once())->method('lockForParty')->willThrowException($exception);
    app()->instance(WalletPostings::class, $wallet);
    $log = Log::spy();
    try {
        app(PrimaryReservations::class)->expireDue(1);
        $this->fail('The sweep must propagate its original failure.');
    } catch (Throwable $caught) {
        expect($caught)->toBe($exception);
    }
    $failure = DB::table('primary_expiry_failures')->sole();
    expect($failure->reason_code)->toBe($expected)
        ->and($failure->exception_class)->toBe($exception::class)
        ->and($failure->primary_reservation_id)->toBe($this->root->id)
        ->and(PrimaryReservationVersion::query()->count())->toBe(1)
        ->and(LedgerEntry::query()->where('kind', 'primary_release')->count())->toBe(0);
    $log->shouldHaveReceived('error', ['Primary reservation expiry failed.', [
        'reservation_id' => $this->root->id, 'campaign_id' => $this->campaign->id,
        'exception_class' => $exception::class, 'reason_code' => $expected,
    ]]);
})->with([
    'reservation integrity' => ['runtime', 'RESERVATION_INTEGRITY_FAILED', 'RESERVATION_INTEGRITY_FAILED'],
    'publication integrity' => ['runtime', 'CAMPAIGN_INTEGRITY_FAILED', 'CAMPAIGN_INTEGRITY_FAILED'],
    'cash isolation' => ['wallet', 'PRIMARY_CASH_ISOLATION_REQUIRED', 'PRIMARY_CASH_ISOLATION_REQUIRED'],
    'missing return' => ['wallet', 'PRIMARY_RETURNED_CASH_REQUIRED', 'PRIMARY_RETURNED_CASH_REQUIRED'],
    'cash transaction' => ['wallet', 'WALLET_POSTING_TRANSACTION_REQUIRED', 'WALLET_POSTING_TRANSACTION_REQUIRED'],
    'cash source' => ['wallet', 'WALLET_POSTING_SOURCE_INVALID', 'WALLET_POSTING_SOURCE_INVALID'],
    'wallet identity' => ['wallet', 'WALLET_POSTING_WALLET_INVALID', 'WALLET_POSTING_WALLET_INVALID'],
    'forged cash' => ['wallet', 'WALLET_POSTING_CONFLICT', 'WALLET_POSTING_CONFLICT'],
    'missing original cash' => ['wallet', 'WALLET_POSTING_STATE_INVALID', 'WALLET_POSTING_STATE_INVALID'],
    'negative bucket' => ['wallet', 'WALLET_BUCKET_NEGATIVE', 'WALLET_BUCKET_NEGATIVE'],
    'missing root' => ['command', 'RESERVATION_NOT_FOUND', 'RESERVATION_NOT_FOUND'],
    'missing primary transaction' => ['command', 'PRIMARY_TRANSACTION_REQUIRED', 'PRIMARY_TRANSACTION_REQUIRED'],
    'arbitrary exception message' => ['runtime', 'private source detail must never be logged', 'UNCLASSIFIED_EXPIRY_FAILURE'],
    'unknown wallet reason' => ['wallet', 'private wallet detail must never be logged', 'UNCLASSIFIED_EXPIRY_FAILURE'],
    'unknown refusal reason' => ['command', 'private refusal detail must never be logged', 'UNCLASSIFIED_EXPIRY_FAILURE'],
]);

it('preserves historical unknown reasons without inventing a classification during migration', function (): void {
    $migration = require database_path('migrations/2026_09_30_111709_add_reason_code_to_primary_expiry_failures.php');
    $migration->down();
    DB::table('primary_expiry_failures')->insert(['primary_reservation_id' => $this->root->id,
        'last_attempted_at' => now('UTC'), 'exception_class' => RuntimeException::class]);
    $migration->up();
    expect(DB::table('primary_expiry_failures')->sole()->reason_code)->toBeNull();
    $migration->down();
    expect(Schema::hasColumn('primary_expiry_failures', 'reason_code'))->toBeFalse();
    $migration->up();
    DB::table('primary_expiry_failures')->where('primary_reservation_id', $this->root->id)
        ->update(['reason_code' => 'WALLET_POSTING_CONFLICT']);
    expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'forward migration')
        ->and(DB::table('primary_expiry_failures')->sole()->reason_code)->toBe('WALLET_POSTING_CONFLICT');
});
