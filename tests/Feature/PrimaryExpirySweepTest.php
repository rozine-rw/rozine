<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Operations\Contracts\ChangeFeed;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Application\Primary\ExpireReservations;
use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\GetInvestorWallet;
use App\Domain\Operations\ChangeScope;
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
    $before = (int) DB::table('change_feed')->max('id');
    $this->travelTo($this->root->expires_at->subMicrosecond());
    expect(app(ExpireReservations::class)->handle(100))->toBe(0)
        ->and(DB::table('change_feed')->where('id', '>', $before)->count())->toBe(0);
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
    $rows = DB::table('change_feed')->where('id', '>', $before)->orderBy('id')->get();
    expect($rows->pluck('topic')->all())->toBe(['purchase', 'campaign'])
        ->and($rows->pluck('subject')->all())->toBe([$this->root->id, $this->campaign->id])
        ->and($rows[0]->party_id)->toBe($this->investor['party']->id)
        ->and($rows[0]->business_id)->toBeNull()->and($rows[0]->staff_queue)->toBeNull()
        ->and($rows[1]->business_id)->toBe($this->campaign->business_id)
        ->and($rows[1]->party_id)->toBeNull()->and($rows[1]->staff_queue)->toBeNull()
        ->and($rows->pluck('revision')->all())->toBe([2, 2]);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('returns cash before the sweeper feed and rolls back both when recording fails', function (string $failedTopic): void {
    $this->travelTo($this->root->expires_at);
    $tables = ['primary_reservations', 'primary_reservation_versions', 'primary_commitments', 'ledger_entries', 'ledger_lines', 'command_operations', 'investor_wallets', 'change_feed'];
    $snapshot = array_map(fn (string $table): string => DB::table($table)->orderBy('id')->get()->toJson(), $tables);
    $real = app(ChangeFeed::class);
    $calls = [];
    $feed = $this->createMock(ChangeFeed::class);
    $feed->expects($this->exactly($failedTopic === 'purchase' ? 1 : 2))->method('record')
        ->willReturnCallback(function (ChangeScope $scope, string $topic, string $subject, ?int $revision = null) use ($real, $failedTopic, &$calls): void {
            expect($revision)->toBeNull()
                ->and(PrimaryReservationVersion::query()->where('state', 'expired')->count())->toBe(1)
                ->and(LedgerEntry::query()->where('kind', 'primary_release')->count())->toBe(1)
                ->and(app(GetInvestorWallet::class)->handle($this->investor['user']->id, 1)['wallet']['breakdown']['held']['amount'])->toBe('0');
            $real->record($scope, $topic, $subject, $revision);
            $calls[] = $topic;
            if ($topic === $failedTopic) {
                throw new RuntimeException('sweeper feed failure');
            }
        });
    app()->instance(ChangeFeed::class, $feed);
    expect(fn () => app(ExpireReservations::class)->handle(1))->toThrow(RuntimeException::class, 'sweeper feed failure')
        ->and($calls)->toBe($failedTopic === 'purchase' ? ['purchase'] : ['purchase', 'campaign'])
        ->and(array_map(fn (string $table): string => DB::table($table)->orderBy('id')->get()->toJson(), $tables))->toBe($snapshot);
})->with(['purchase', 'campaign']);

it('finishes a caller wrapped sweep before sorted observations and rolls the batch back on feed failure', function (?string $failedTopic): void {
    $second = PrimaryReservationFixture::investor();
    $third = PrimaryReservationFixture::investor();
    foreach ([$third, $second] as $investor) {
        $this->checkout->reserve($investor['user']->id, 1, $this->campaign->id, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    }
    $roots = PrimaryReservationRecord::query()->orderBy('party_id')->get();
    expect($roots->pluck('id')->all())->not->toBe(PrimaryReservationRecord::query()->orderBy('id')->pluck('id')->all());
    $this->travelTo($roots->max('expires_at'));
    $tables = ['primary_reservation_versions', 'ledger_entries', 'ledger_lines', 'change_feed'];
    $snapshot = array_map(fn (string $table): string => DB::table($table)->orderBy('id')->get()->toJson(), $tables);
    $before = (int) DB::table('change_feed')->max('id');
    $real = app(ChangeFeed::class);
    $feed = $this->createMock(ChangeFeed::class);
    $feed->method('record')->willReturnCallback(function (ChangeScope $scope, string $topic, string $subject, ?int $revision = null) use ($real, $failedTopic): void {
        expect(LedgerEntry::query()->where('kind', 'primary_release')->count())->toBe(3)
            ->and(PrimaryReservationVersion::query()->where('state', 'expired')->count())->toBe(3)
            ->and($revision)->toBeNull();
        $real->record($scope, $topic, $subject, $revision);
        if ($topic === $failedTopic) {
            throw new RuntimeException('batch feed failure');
        }
    });
    app()->instance(ChangeFeed::class, $feed);
    if ($failedTopic !== null) {
        expect(fn () => app(ExpireReservations::class)->handle(3))->toThrow(RuntimeException::class, 'batch feed failure')
            ->and(array_map(fn (string $table): string => DB::table($table)->orderBy('id')->get()->toJson(), $tables))->toBe($snapshot);
    } else {
        expect(app(ExpireReservations::class)->handle(3))->toBe(3)
            ->and(DB::table('change_feed')->where('id', '>', $before)->orderBy('id')->pluck('subject')->all())->toBe([...$roots->pluck('id')->all(), $this->campaign->id])
            ->and(DB::table('change_feed')->where('id', '>', $before)->orderBy('id')->pluck('topic')->all())->toBe(['purchase', 'purchase', 'purchase', 'campaign'])
            ->and(app(ExpireReservations::class)->handle(3))->toBe(0)
            ->and(DB::table('change_feed')->where('id', '>', $before)->count())->toBe(4);
    }
})->with(['success' => null, 'purchase failure' => 'purchase', 'campaign failure' => 'campaign']);

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
        $this->campaign->id, 1, null, (string) Str::uuid())['code'])->toBe('CAMPAIGN_CANCELLED')
        ->and(DB::table('change_feed')->where('topic', 'campaign')->where('subject', $this->campaign->id)->orderBy('revision')->pluck('revision')->all())
        ->toBe([1, 2, 3, 4, 5]);
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
    $before = DB::table('change_feed')->count();
    $intervened = false;
    DB::listen(function (QueryExecuted $query) use (&$intervened): void {
        if (! $intervened && str_contains($query->sql, 'from "primary_reservations"') && str_contains($query->sql, 'not exists')) {
            $intervened = true;
            app(PrimaryReservations::class)->expire($this->campaign->id, $this->root->id);
        }
    });
    expect(app(ExpireReservations::class)->handle(1))->toBe(0)->and($intervened)->toBeTrue()
        ->and(DB::table('change_feed')->count())->toBe($before)
        ->and(PrimaryReservationVersion::query()->count())->toBe(2)
        ->and(LedgerEntry::query()->where('kind', 'primary_release')->count())->toBe(1);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});
