<?php

declare(strict_types=1);

use App\Application\Business\Contracts\PrimaryCampaignSource;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Infrastructure\Business\EloquentPrimaryCampaignSource;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

/* Review #175 (A). PROBE = expected to hold; OBSERVE = pins behaviour reported as a finding. */

beforeEach(function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $this->investor = PrimaryReservationFixture::investor();
    $this->hold = fn ($campaign, string $units = '1'): PrimaryReservationRecord => PrimaryReservationRecord::query()->whereKey(
        app(PrimaryCheckout::class)->reserve($this->investor['user']->id, 1, $campaign->id, $units, (string) Str::uuid(), PrimaryReservationFixture::terms(...))['data']['reservation_id'])->sole();
    $this->latest = fn (PrimaryReservationRecord $root): string => PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->orderByDesc('revision')->value('state');
    $this->second = function () {
        Cache::forget('fortify.2fa_codes.'.md5((new Google2FA)->getCurrentOtp('JBSWY3DPEHPK3PXP')));

        return PrimaryReservationFixture::campaign();
    };
});

it('OBSERVE one candidate that fails integrity at the head starves every later overdue hold on every run', function (): void {
    $broken = PrimaryReservationFixture::campaign();
    $brokenRoot = ($this->hold)($broken);
    $this->travel(1)->seconds();
    $healthy = ($this->second)();
    $healthyRoot = ($this->hold)($healthy);
    $real = app(EloquentPrimaryCampaignSource::class);
    app()->instance(PrimaryCampaignSource::class, new class($real, $broken->id) implements PrimaryCampaignSource
    {
        public function __construct(private PrimaryCampaignSource $real, private string $broken) {}

        public function lockBusiness(string $campaignId): void
        {
            $this->real->lockBusiness($campaignId);
        }

        public function lock(string $campaignId): array
        {
            return $this->real->lock($campaignId);
        }

        public function lockRetained(string $campaignId): array
        {
            if ($campaignId === $this->broken) {
                throw new RuntimeException('APPLICATION_RELEASE_INTEGRITY_FAILED');
            }

            return $this->real->lockRetained($campaignId);
        }
    });
    app()->forgetInstance(PrimaryReservations::class);
    $this->travelTo($healthyRoot->expires_at->addMinutes(30));
    $runs = [];
    foreach (range(1, 5) as $run) {
        $this->travel(1)->minutes();
        try {
            $runs[] = Artisan::call('primary:expire-reservations');
        } catch (RuntimeException $exception) {
            $runs[] = $exception->getMessage();
        }
    }
    fwrite(STDERR, 'exit codes over 5 scheduled runs: '.json_encode($runs).'; healthy hold still '.($this->latest)($healthyRoot).' 36 minutes past its deadline'."\n");
    expect(($this->latest)($healthyRoot))->toBe('held')->and(($this->latest)($brokenRoot))->toBe('held');
});

it('OBSERVE expireDue accepts an outer transaction, so one later failure rolls back earlier expiries', function (): void {
    $campaign = PrimaryReservationFixture::campaign();
    $first = ($this->hold)($campaign);
    $this->travel(1)->seconds();
    $second = ($this->hold)($campaign);
    $this->travelTo($second->expires_at);
    $level = DB::transactionLevel();
    DB::listen(function ($query) use ($second): void {
        if (str_starts_with($query->sql, 'insert into "primary_reservation_versions"') && in_array($second->id, $query->bindings, true)) {
            throw new RuntimeException('Injected.');
        }
    });
    try {
        DB::transaction(fn () => app(PrimaryReservations::class)->expireDue(2));
    } catch (RuntimeException) {
    }
    fwrite(STDERR, 'outer level before='.$level.' first root after outer rollback='.($this->latest)($first)."\n");
    expect(($this->latest)($first))->toBe('held');
});

it('PROBE limit bounds at the command boundary', function (string $limit, int $code): void {
    expect(Artisan::call('primary:expire-reservations', ['--limit' => $limit]))->toBe($code);
})->with([['1', 0], ['1000', 0], ['0', 2], ['1001', 2], [' 7', 0], ['+7', 0], ['07', 2], ['1e3', 2]]);

it('PROBE throughput and batching for many overdue holds on one Business', function (): void {
    $campaign = PrimaryReservationFixture::campaign();
    foreach (range(1, 120) as $i) {
        ($this->hold)($campaign);
    }
    $this->travelTo(PrimaryReservationRecord::query()->max('expires_at'));
    $started = microtime(true);
    $first = app(PrimaryReservations::class)->expireDue(100);
    $elapsed = microtime(true) - $started;
    $second = app(PrimaryReservations::class)->expireDue(100);
    fwrite(STDERR, sprintf("120 holds one Business: run1=%d in %.2fs (%.1f ms/candidate), run2=%d\n", $first, $elapsed, $elapsed * 10, $second));
    expect([$first, $second])->toBe([100, 20]);
});

it('OBSERVE candidate query scans every historical terminal root before finding none', function (): void {
    $campaign = PrimaryReservationFixture::campaign();
    foreach (range(1, 60) as $i) {
        ($this->hold)($campaign);
    }
    $this->travelTo(PrimaryReservationRecord::query()->max('expires_at'));
    app(PrimaryReservations::class)->expireDue(100);
    DB::statement('ANALYZE primary_reservations');
    DB::statement('ANALYZE primary_reservation_versions');
    $cutoff = now('UTC')->format('Y-m-d H:i:s.uP');
    $plan = collect(DB::select("EXPLAIN (ANALYZE, BUFFERS, FORMAT TEXT) SELECT * FROM primary_reservations WHERE expires_at <= ? AND NOT EXISTS (SELECT 1 FROM primary_reservation_versions WHERE primary_reservation_id = primary_reservations.id AND state IN ('confirmed','released','expired')) ORDER BY expires_at, id LIMIT 100", [$cutoff]))->pluck('QUERY PLAN')->implode("\n");
    fwrite(STDERR, $plan."\n");
    expect($plan)->toContain('rows=0');
});
