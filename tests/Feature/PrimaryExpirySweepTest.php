<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Application\Primary\ExpireReservations;
use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\GetInvestorWallet;
use App\Domain\Operations\CommandRejection;
use App\Models\CommandOperation;
use App\Models\LedgerEntry;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Artisan;
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
    $this->checkout->reserve($this->investor['user']->id, 1, $this->campaign->id, '3', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $this->root = PrimaryReservationRecord::query()->sole();
    $this->version = PrimaryReservationVersion::query()->sole();
});

it('expires due holds once with the original cash source and no new receipt', function (): void {
    $operations = CommandOperation::query()->count();
    $this->travelTo($this->root->expires_at->subMicrosecond());
    expect(app(ExpireReservations::class)->handle(100))->toBe(0);
    $this->travelTo($this->root->expires_at);
    expect(Artisan::call('primary:expire-reservations'))->toBe(0)
        ->and(Artisan::output())->toContain('Expired 1 reservations.');
    $version = PrimaryReservationVersion::query()->orderByDesc('revision')->firstOrFail();
    expect(app(ExpireReservations::class)->handle(100))->toBe(0)
        ->and($version->state)->toBe('expired')->and($version->operation_id)->toBeNull()
        ->and(CommandOperation::query()->count())->toBe($operations)
        ->and(LedgerEntry::query()->where('kind', 'primary_release')->sole()->source_id)->toBe($this->root->id)
        ->and(app(GetInvestorWallet::class)->handle($this->investor['user']->id, 1)['wallet']['breakdown'])
        ->toMatchArray(['held' => ['currency' => 'RWF', 'amount' => '0'], 'available' => ['currency' => 'RWF', 'amount' => '10000000']]);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('excludes confirmed and released roots from the bounded candidate batch', function (string $terminal): void {
    if ($terminal === 'confirmed') {
        $this->checkout->confirm($this->investor['user']->id, 1, $this->campaign->id, $this->root->id, 1,
            $this->version->payload['terms']['disclosure_version'], $this->version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    } else {
        $this->checkout->release($this->investor['user']->id, 1, $this->campaign->id, $this->root->id, 1, (string) Str::uuid());
    }
    $this->travel(1)->seconds();
    $reserved = $this->checkout->reserve($this->investor['user']->id, 1, $this->campaign->id, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $next = PrimaryReservationRecord::query()->whereKey($reserved['data']['reservation_id'])->sole();
    $this->travelTo($next->expires_at);
    expect(app(ExpireReservations::class)->handle(1))->toBe(1)
        ->and(PrimaryReservationVersion::query()->where('primary_reservation_id', $next->id)->orderByDesc('revision')->value('state'))->toBe('expired')
        ->and(PrimaryCommitment::query()->count())->toBe($terminal === 'confirmed' ? 1 : 0)
        ->and(LedgerEntry::query()->where('kind', 'primary_refund')->count())->toBe(0);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
})->with(['confirmed', 'released']);

it('drains oldest deadlines in bounded batches before cancelling a cash-returned campaign', function (): void {
    $this->travel(1)->seconds();
    $reserved = $this->checkout->reserve($this->investor['user']->id, 1, $this->campaign->id, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $next = PrimaryReservationRecord::query()->whereKey($reserved['data']['reservation_id'])->sole();
    expect(app(BusinessCampaignStore::class)->cancel($this->campaign->actor_user_id, 1, $this->campaign->business_id,
        $this->campaign->id, 1, null, (string) Str::uuid())['code'])->toBe('CAMPAIGN_SETTLEMENT_REQUIRED');
    $this->travelTo($next->expires_at);
    expect(app(ExpireReservations::class)->handle(1))->toBe(1)
        ->and(LedgerEntry::query()->where('kind', 'primary_release')->sole()->source_id)->toBe($this->root->id)
        ->and(PrimaryReservationVersion::query()->where('primary_reservation_id', $next->id)->count())->toBe(1)
        ->and(app(ExpireReservations::class)->handle(1))->toBe(1)
        ->and(app(ExpireReservations::class)->handle(1))->toBe(0);
    expect(app(BusinessCampaignStore::class)->cancel($this->campaign->actor_user_id, 1, $this->campaign->business_id,
        $this->campaign->id, 1, null, (string) Str::uuid())['code'])->toBe('CAMPAIGN_CANCELLED');
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('rolls back expiry evidence when cash return fails and propagates the failure', function (): void {
    $this->travelTo($this->root->expires_at);
    $wallet = $this->createMock(WalletPostings::class);
    $wallet->expects($this->once())->method('lockForParty')->willThrowException(new RuntimeException('Ledger unavailable.'));
    app()->instance(WalletPostings::class, $wallet);
    expect(fn () => app(ExpireReservations::class)->handle(1))->toThrow(RuntimeException::class, 'Ledger unavailable.')
        ->and(PrimaryReservationVersion::query()->count())->toBe(1)
        ->and(LedgerEntry::query()->where('kind', 'primary_release')->count())->toBe(0);
});

it('rejects an invalid internal sweep limit', function (int $limit): void {
    expect(fn () => app(PrimaryReservations::class)->expireDue($limit))->toThrow(CommandRejection::class, 'INVALID_SWEEP_LIMIT');
})->with([0, -1, 1001]);

it('rejects malformed command limits before performing any expiry', function (string $limit): void {
    $this->travelTo($this->root->expires_at);
    expect(Artisan::call('primary:expire-reservations', ['--limit' => $limit]))->toBe(2)
        ->and(Artisan::output())->toContain('The limit must be an integer from 1 to 1000.');
    expect(PrimaryReservationVersion::query()->count())->toBe(1);
})->with(['0', '-1', '1001', '1.5', 'abc']);

it('schedules the bounded expiry command every minute with overlap protection', function (): void {
    $events = array_values(array_filter(app(Schedule::class)->events(), fn ($event): bool => str_contains($event->command ?? '', 'primary:expire-reservations')));
    expect($events)->toHaveCount(1)->and($events[0]->expression)->toBe('* * * * *')
        ->and($events[0]->withoutOverlapping)->toBeTrue()->and($events[0]->expiresAt)->toBe(5);
});

it('rechecks a candidate that another worker expired after selection', function (): void {
    $this->travelTo($this->root->expires_at);
    $intervened = false;
    DB::listen(function (QueryExecuted $query) use (&$intervened): void {
        if (! $intervened && str_contains($query->sql, 'from "primary_reservations"') && str_contains($query->sql, 'not exists')) {
            $intervened = true;
            app(PrimaryReservations::class)->expire($this->campaign->id, $this->root->id);
        }
    });
    expect(app(ExpireReservations::class)->handle(1))->toBe(0)->and($intervened)->toBeTrue()
        ->and(PrimaryReservationVersion::query()->count())->toBe(2)
        ->and(LedgerEntry::query()->where('kind', 'primary_release')->count())->toBe(1);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});
