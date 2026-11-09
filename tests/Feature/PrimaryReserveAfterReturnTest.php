<?php

declare(strict_types=1);

use App\Application\Wallet\GetInvestorWallet;
use App\Domain\Primary\RiskStatements;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

/*
 * A deal stays open to new reserves after a hold is released or expires. Ledger entries keep whole
 * seconds while reservations keep microseconds, so the clock here deliberately sits mid-second: a
 * frozen whole second hid the retirement check refusing every returned hold with a 500.
 */

beforeEach(function (): void {
    $this->travelTo(now()->startOfSecond()->addMicroseconds(654321));
    InvestorWalletFixture::policy(maximum: null);
    $this->campaign = PrimaryReservationFixture::campaign();
    $this->investor = PrimaryReservationFixture::investor();
    $this->travel(250)->milliseconds();

    $this->ready = function (User $user): void {
        $this->actingAs($user)->postJson(route('investor.risks.acknowledge'), ['request_id' => (string) Str::uuid(), 'identity_context_revision' => 1,
            'version' => RiskStatements::VERSION, 'statements_sha256' => RiskStatements::sha256(), 'acknowledged' => true])->assertOk();
    };
    $this->reserve = fn (User $user) => $this->actingAs($user)->postJson(route('investor.primary.reserve', ['campaign' => $this->campaign->id]), [
        'request_id' => (string) Str::uuid(), 'identity_context_revision' => 1, 'campaign_id' => $this->campaign->id, 'units' => '2',
        'expected_campaign_revision' => 1, 'quote_revision' => 1]);
    $this->release = function (User $user, string $reservation): void {
        $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $reservation)->orderByDesc('revision')->firstOrFail();
        $this->travel(333)->milliseconds();
        $this->actingAs($user)->postJson(route('investor.primary.release', ['reservation' => $reservation]), ['request_id' => (string) Str::uuid(),
            'identity_context_revision' => 1, 'reservation_id' => $reservation, 'expected_reservation_revision' => $version->revision])
            ->assertOk()->assertJsonPath('code', 'RESERVATION_RELEASED');
    };
    $this->assertFreshHold = function (User $user, string $previous, string $next): void {
        $root = PrimaryReservationRecord::query()->whereKey($next)->sole();
        $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $next)->sole();
        expect($next)->not->toBe($previous)
            ->and($version->state)->toBe('held')->and($version->revision)->toBe(1)
            ->and(DB::table('primary_held_claim_releases')->where('primary_reservation_id', $previous)->exists())->toBeTrue()
            ->and(app(GetInvestorWallet::class)->handle($user->id, 1)['wallet']['breakdown'])->toMatchArray([
                'held' => ['currency' => 'RWF', 'amount' => $root->principal],
                'available' => ['currency' => 'RWF', 'amount' => (string) (10000000 - (int) $root->principal)]]);
    };
});

it('reserves the same deal again after the earlier hold is released', function (): void {
    ($this->ready)($this->investor['user']);
    $first = ($this->reserve)($this->investor['user'])->assertOk()->json('data.receipt.reservation_id');
    ($this->release)($this->investor['user'], $first);

    $second = ($this->reserve)($this->investor['user'])->assertOk()->assertJsonPath('code', 'RESERVATION_HELD')->json('data.receipt.reservation_id');

    ($this->assertFreshHold)($this->investor['user'], $first, $second);
});

it('reserves the same deal again after the earlier hold expires and is swept', function (): void {
    ($this->ready)($this->investor['user']);
    $first = ($this->reserve)($this->investor['user'])->assertOk()->json('data.receipt.reservation_id');
    $this->travelTo(PrimaryReservationRecord::query()->whereKey($first)->sole()->expires_at->addMilliseconds(420));
    expect(Artisan::call('primary:expire-reservations'))->toBe(0);
    $this->travel(333)->milliseconds();

    $second = ($this->reserve)($this->investor['user'])->assertOk()->assertJsonPath('code', 'RESERVATION_HELD')->json('data.receipt.reservation_id');

    ($this->assertFreshHold)($this->investor['user'], $first, $second);
});

it('lets another Investor reserve the deal after one hold is released', function (): void {
    $other = PrimaryReservationFixture::investor();
    ($this->ready)($this->investor['user']);
    ($this->ready)($other['user']);
    $first = ($this->reserve)($this->investor['user'])->assertOk()->json('data.receipt.reservation_id');
    ($this->release)($this->investor['user'], $first);

    $second = ($this->reserve)($other['user'])->assertOk()->assertJsonPath('code', 'RESERVATION_HELD')->json('data.receipt.reservation_id');

    ($this->assertFreshHold)($other['user'], $first, $second);
});

it('rolls the precision back only while no hold has been retired', function (): void {
    $migration = require database_path('migrations/2026_10_30_190000_compare_held_release_cash_at_ledger_precision.php');
    $definition = fn (): string => (string) DB::scalar("SELECT pg_get_functiondef('check_primary_held_release(char)'::regprocedure)");
    $installed = $definition();
    $migration->down();
    expect($definition())->toContain('OR h.created_at < r.created_at OR e.created_at < v.created_at')->not->toContain('date_trunc');
    $migration->up();
    expect($definition())->toBe($installed);

    ($this->ready)($this->investor['user']);
    $first = ($this->reserve)($this->investor['user'])->assertOk()->json('data.receipt.reservation_id');
    ($this->release)($this->investor['user'], $first);
    ($this->reserve)($this->investor['user'])->assertOk();
    $refusal = null;
    try {
        DB::transaction(fn () => $migration->down());
    } catch (QueryException $exception) {
        $refusal = $exception;
    }

    expect($refusal?->getCode())->toBe('23514')->and($refusal?->getMessage())->toContain('Retained held claim releases require a forward migration')
        ->and($definition())->toBe($installed);
});
