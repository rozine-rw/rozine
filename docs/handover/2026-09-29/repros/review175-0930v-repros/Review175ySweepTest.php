<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Models\LedgerEntry;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

/* Review #175 range 8d500fc7..d701d879 (expiry retry fairness). PROBE = expected to hold; OBSERVE = pins a finding. */

beforeEach(function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $this->campaign = PrimaryReservationFixture::campaign();
    $this->investor = PrimaryReservationFixture::investor();
    $this->hold = function (): PrimaryReservationRecord {
        $result = app(PrimaryCheckout::class)->reserve($this->investor['user']->id, 1, $this->campaign->id, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
        $this->travel(1)->seconds();

        return PrimaryReservationRecord::query()->whereKey($result['data']['reservation_id'])->sole();
    };
    $this->state = fn (PrimaryReservationRecord $root): string => PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->orderByDesc('revision')->value('state');
    $this->broken = new ArrayObject;
    DB::listen(function (QueryExecuted $query): void {
        if (str_starts_with($query->sql, 'insert into "primary_reservation_versions"') && array_intersect($this->broken->getArrayCopy(), $query->bindings) !== []) {
            throw new RuntimeException('Injected corrupt hold.');
        }
    });
    $this->sweep = function (int $limit): string {
        try {
            return 'ok '.app(PrimaryReservations::class)->expireDue($limit);
        } catch (RuntimeException $exception) {
            return 'threw '.$exception->getMessage();
        }
    };
});

it('PROBE more failing roots than the limit never starve healthy holds', function (): void {
    $broken = [($this->hold)(), ($this->hold)(), ($this->hold)()];
    $healthy = [($this->hold)(), ($this->hold)()];
    foreach ($broken as $root) {
        $this->broken[] = $root->id;
    }
    $this->travelTo($healthy[1]->expires_at);
    $runs = [];
    foreach (range(1, 3) as $run) {
        $this->travel(1)->minutes();
        $runs[] = ($this->sweep)(2).' healthy='.implode(',', array_map($this->state, $healthy));
    }
    fwrite(STDERR, "limit=2, 3 failing + 2 healthy:\n  ".implode("\n  ", $runs)."\n");
    expect(array_map($this->state, $healthy))->toBe(['expired', 'expired'])
        ->and(array_map($this->state, $broken))->toBe(['held', 'held', 'held'])
        ->and(DB::table('primary_expiry_failures')->count())->toBe(3)
        ->and(LedgerEntry::query()->where('kind', 'primary_release')->count())->toBe(2);
});

it('PROBE the scheduled process exits non-zero after still expiring the rest of the batch', function (): void {
    $broken = ($this->hold)();
    $healthy = ($this->hold)();
    $this->broken[] = $broken->id;
    $this->travelTo($healthy->expires_at);
    $output = new BufferedOutput;
    $code = app(Kernel::class)->handle(new ArgvInput(['artisan', 'primary:expire-reservations']), $output);
    fwrite(STDERR, 'artisan exit='.$code.' output='.json_encode(mb_substr(trim($output->fetch()), 0, 120))."\n");
    expect($code)->not->toBe(0)->and(($this->state)($healthy))->toBe('expired')->and(($this->state)($broken))->toBe('held');
});

it('OBSERVE one transient failure demotes a root behind every never-failed hold and its failure row outlives the repair', function (): void {
    $victim = ($this->hold)();
    $this->broken[] = $victim->id;
    $this->travelTo($victim->expires_at);
    expect(($this->sweep)(1))->toBe('threw Injected corrupt hold.');
    $this->broken->exchangeArray([]);
    $runs = [];
    foreach (range(1, 4) as $minute) {
        $fresh = ($this->hold)();
        $this->travelTo($fresh->expires_at);
        $runs[] = ($this->sweep)(1).' victim='.($this->state)($victim);
    }
    fwrite(STDERR, "limit=1 with one new overdue hold per run, after the victim's single transient failure:\n  ".implode("\n  ", $runs)."\n");
    expect(($this->state)($victim))->toBe('held');
    while (($this->sweep)(1) !== 'ok 0') {
    }
    $row = DB::table('primary_expiry_failures')->where('primary_reservation_id', $victim->id)->first();
    fwrite(STDERR, 'after repair and expiry: victim='.($this->state)($victim).' failure row still present='.json_encode($row !== null).' exception_class='.($row->exception_class ?? '-')."\n");
    expect(($this->state)($victim))->toBe('expired')->and($row)->not->toBeNull();
});

it('OBSERVE a failure while recording the failure skips the rest of the batch and replaces the original error', function (): void {
    $broken = ($this->hold)();
    $healthy = ($this->hold)();
    $this->broken[] = $broken->id;
    $this->travelTo($healthy->expires_at);
    DB::listen(function (QueryExecuted $query): void {
        if (str_starts_with($query->sql, 'insert into "primary_expiry_failures"')) {
            throw new QueryException('pgsql', $query->sql, $query->bindings, new RuntimeException('bookkeeping write failed'));
        }
    });
    try {
        app(PrimaryReservations::class)->expireDue(2);
        $thrown = 'none';
    } catch (Throwable $exception) {
        $thrown = $exception::class.': '.mb_substr($exception->getMessage(), 0, 40);
    }
    fwrite(STDERR, 'thrown='.$thrown.' healthy='.($this->state)($healthy)."\n");
    expect($thrown)->toStartWith(QueryException::class)->and(($this->state)($healthy))->toBe('held');
});

it('PROBE raw SQL on the failure table cannot move cash, reopen terminal roots or alter progress', function (): void {
    $confirmed = ($this->hold)();
    $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $confirmed->id)->sole();
    expect(app(PrimaryCheckout::class)->confirm($this->investor['user']->id, 1, $this->campaign->id, $confirmed->id, 1, $version->payload['terms']['disclosure_version'],
        $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...))['code'])->toBe('RESERVATION_CONFIRMED');
    $expired = ($this->hold)();
    $live = ($this->hold)();
    $this->travelTo($expired->expires_at);
    expect(app(PrimaryReservations::class)->expireDue(1))->toBe(1);
    $page = fn (): array => app(BusinessCampaignStore::class)->campaign($this->campaign->actor_user_id, 1, $this->campaign->business_id, $this->campaign->id)['progress'];
    $before = $page();
    $ledger = LedgerEntry::query()->count();
    $versions = PrimaryReservationVersion::query()->count();
    foreach ([$confirmed, $expired, $live] as $root) {
        DB::statement("INSERT INTO primary_expiry_failures (primary_reservation_id, last_attempted_at, exception_class) VALUES (?, '1970-01-01', 'Forged')", [$root->id]);
    }
    DB::statement("UPDATE primary_expiry_failures SET last_attempted_at = '2999-01-01', exception_class = 'Forged2'");
    $sweeps = app(PrimaryReservations::class)->expireDue(1000);
    $fk = null;
    try {
        DB::transaction(fn () => DB::statement("INSERT INTO primary_expiry_failures VALUES ('01ZZZZZZZZZZZZZZZZZZZZZZZZ', now(), 'Forged')"));
    } catch (QueryException $exception) {
        $fk = $exception->getCode();
    }
    $delete = null;
    try {
        DB::transaction(fn () => DB::statement('DELETE FROM primary_reservations WHERE id = ?', [$live->id]));
    } catch (QueryException $exception) {
        $delete = $exception->getCode();
    }
    $triggers = DB::selectOne("SELECT count(*) AS n FROM pg_trigger WHERE tgrelid = 'primary_expiry_failures'::regclass AND NOT tgisinternal")->n;
    fwrite(STDERR, "forged rows on confirmed/expired/live: sweep expired={$sweeps}; unknown-root insert sqlstate={$fk}; root delete sqlstate={$delete}; user triggers on table={$triggers}\n");
    expect($sweeps)->toBe(0)->and(LedgerEntry::query()->count())->toBe($ledger)->and(PrimaryReservationVersion::query()->count())->toBe($versions)
        ->and($page())->toBe($before)->and(($this->state)($live))->toBe('held')->and($fk)->toBe('23503')->and($delete)->not->toBeNull();
    DB::table('primary_expiry_failures')->delete();
    $this->travelTo($live->expires_at);
    expect(app(PrimaryReservations::class)->expireDue(10))->toBe(1)->and(LedgerEntry::query()->count())->toBe($ledger + 1);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('OBSERVE inside an outer transaction the failure bookkeeping and earlier expiries roll back together', function (): void {
    $healthy = ($this->hold)();
    $broken = ($this->hold)();
    $this->broken[] = $broken->id;
    $this->travelTo($broken->expires_at);
    try {
        DB::transaction(fn () => app(PrimaryReservations::class)->expireDue(2));
    } catch (RuntimeException) {
    }
    fwrite(STDERR, 'outer tx: healthy='.($this->state)($healthy).' failure rows='.DB::table('primary_expiry_failures')->count()."\n");
    expect(($this->state)($healthy))->toBe('held')->and(DB::table('primary_expiry_failures')->count())->toBe(0);
});

it('PROBE failed roots are retried oldest attempt first, so a repaired one expires even at limit 1', function (): void {
    $first = ($this->hold)();
    $second = ($this->hold)();
    $this->broken->exchangeArray([$first->id, $second->id]);
    $this->travelTo($second->expires_at);
    $runs = [($this->sweep)(1)];
    $this->travel(1)->minutes();
    $runs[] = ($this->sweep)(1);
    $this->broken->exchangeArray([$second->id]);
    foreach (range(1, 3) as $run) {
        $this->travel(1)->minutes();
        $runs[] = ($this->sweep)(1).' first='.($this->state)($first);
    }
    fwrite(STDERR, "two failing roots, limit=1, first repaired after two runs:\n  ".implode("\n  ", $runs)."\n");
    expect(($this->state)($first))->toBe('expired');
});

it('PROBE the first failure of a batch is the one rethrown', function (): void {
    $first = ($this->hold)();
    $second = ($this->hold)();
    $this->travelTo($second->expires_at);
    DB::listen(function (QueryExecuted $query) use ($first, $second): void {
        foreach (['first' => $first, 'second' => $second] as $label => $root) {
            if (str_starts_with($query->sql, 'insert into "primary_reservation_versions"') && in_array($root->id, $query->bindings, true)) {
                throw new RuntimeException('Injected '.$label.'.');
            }
        }
    });
    expect(fn () => app(PrimaryReservations::class)->expireDue(2))->toThrow(RuntimeException::class, 'Injected first.')
        ->and(DB::table('primary_expiry_failures')->count())->toBe(2);
});
