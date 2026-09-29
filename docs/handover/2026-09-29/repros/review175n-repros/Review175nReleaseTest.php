<?php

declare(strict_types=1);

/*
 * Review repros/probes for PR #175 range c8f30fcb..205a8a08 (S3-C release/expiry). Copy into tests/Feature/.
 * "PROBE" tests assert behaviour that should already hold; "OBSERVE" tests pin current behaviour the
 * review reports as a design question (they pass on 205a8a08).
 */

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Application\Wallet\GetInvestorWallet;
use App\Domain\Operations\CommandRejection;
use App\Domain\Primary\PrimaryTerms;
use App\Domain\Primary\UnitRights;
use App\Models\CommandOperation;
use App\Models\LedgerEntry;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Database\Events\TransactionRolledBack;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $this->campaign = PrimaryReservationFixture::campaign();
    $this->investor = PrimaryReservationFixture::investor();
    $this->checkout = app(PrimaryCheckout::class);
    $this->store = app(PrimaryReservations::class);
    $this->checkout->reserve($this->investor['user']->id, 1, $this->campaign->id, '3', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $this->root = PrimaryReservationRecord::query()->sole();
    $this->version = PrimaryReservationVersion::query()->sole();
    $this->release = fn (?string $key = null, int $revision = 1): array => $this->checkout->release($this->investor['user']->id, 1,
        $this->campaign->id, $this->root->id, $revision, $key ?? (string) Str::uuid());
    $this->confirm = fn (?string $key = null, int $revision = 1, ?Closure $admit = null): array => $this->checkout->confirm($this->investor['user']->id, 1,
        $this->campaign->id, $this->root->id, $revision, $this->version->payload['terms']['disclosure_version'], $this->version->payload['disclosure_sha256'],
        $key ?? (string) Str::uuid(), $admit ?? PrimaryReservationFixture::terms(...));
    $this->cash = fn (): array => [LedgerEntry::query()->where('kind', 'primary_release')->count(), LedgerEntry::query()->where('kind', 'primary_commit')->count(),
        PrimaryReservationVersion::query()->count(), PrimaryCommitment::query()->count(),
        app(GetInvestorWallet::class)->handle($this->investor['user']->id, 1)['wallet']['breakdown']['held']['amount']];
});

it('PROBE terminal replay: release, then system expiry, then replay, confirm and a fresh release', function (): void {
    $key = (string) Str::uuid();
    $released = ($this->release)($key);
    $this->travelTo($this->campaign->expires_at->addDay());
    expect($this->store->expire($this->campaign->id, $this->root->id))->toBeNull()
        ->and(($this->release)($key))->toBe($released)
        ->and(($this->confirm)(revision: 2)['code'])->toBe('RESERVATION_NOT_HELD')
        ->and(($this->cash)())->toBe([1, 0, 2, 0, '0']);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('PROBE terminal replay: system expiry, then an actor release and confirm replay stably without cash', function (): void {
    $this->travelTo($this->root->expires_at->addSecond());
    expect($this->store->expire($this->campaign->id, $this->root->id)?->reservation->state)->toBe('expired');
    $key = (string) Str::uuid();
    $first = ($this->release)($key, 2);
    expect($first)->toMatchArray(['status' => 'rejected', 'code' => 'RESERVATION_EXPIRED', 'revision' => 2])
        ->and(($this->release)($key, 2))->toBe($first)
        ->and(($this->confirm)(revision: 2)['code'])->toBe('RESERVATION_NOT_HELD')
        ->and(PrimaryReservationVersion::query()->orderByDesc('revision')->value('operation_id'))->toBeNull()
        ->and(($this->cash)())->toBe([1, 0, 2, 0, '0']);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('PROBE terminal replay: actor expiry through confirm, then release, system expiry and confirm replay', function (): void {
    $this->travelTo($this->root->expires_at);
    $key = (string) Str::uuid();
    $expired = ($this->confirm)($key);
    expect($expired['code'])->toBe('RESERVATION_EXPIRED')
        ->and(($this->release)(revision: 2)['code'])->toBe('RESERVATION_EXPIRED')
        ->and($this->store->expire($this->campaign->id, $this->root->id))->toBeNull()
        ->and(($this->confirm)($key))->toBe($expired)
        ->and(PrimaryReservationVersion::query()->orderByDesc('revision')->value('operation_id'))->toBe($expired['operation_id'])
        ->and(($this->cash)())->toBe([1, 0, 2, 0, '0']);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('PROBE a confirm after release is NOT_HELD with no commit', function (): void {
    ($this->release)();
    expect(($this->confirm)(revision: 2)['code'])->toBe('RESERVATION_NOT_HELD')->and(($this->cash)())->toBe([1, 0, 2, 0, '0']);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('OBSERVE a fresh release key on an already released hold gets a completed receipt naming the earlier entry', function (): void {
    $first = ($this->release)();
    $second = ($this->release)(revision: 2);
    $bound = PrimaryReservationVersion::query()->orderByDesc('revision')->value('operation_id');
    fwrite(STDERR, 'second release: '.json_encode(['code' => $second['code'], 'status' => $second['status'], 'revision' => $second['revision'],
        'entry' => $second['data']['entry_id'] === $first['data']['entry_id'] ? 'same as first' : 'different', 'version_bound_to_second' => $bound === $second['operation_id']])."\n");
    expect($second)->toMatchArray(['status' => 'completed', 'code' => 'RESERVATION_RELEASED', 'revision' => 2])
        ->and($second['data']['entry_id'])->toBe($first['data']['entry_id'])
        ->and($bound)->toBe($first['operation_id'])
        ->and(CommandOperation::query()->where('command', 'primary.release')->where('result->status', 'completed')->count())->toBe(2);
});

it('OBSERVE confirm expired receipt revision', function (): void {
    $this->travelTo($this->root->expires_at);
    $confirm = ($this->confirm)();
    fwrite(STDERR, 'confirm expired receipt revision='.json_encode($confirm['revision']).' data='.json_encode($confirm['data'])."\n");
    expect($confirm['code'])->toBe('RESERVATION_EXPIRED');
});

it('OBSERVE release expired receipt revision', function (): void {
    $this->travelTo($this->root->expires_at);
    $release = ($this->release)();
    fwrite(STDERR, 'release expired receipt revision='.json_encode($release['revision']).' data='.json_encode($release['data'])."\n");
    expect($release['code'])->toBe('RESERVATION_EXPIRED');
});

it('PROBE lockRetained never opens a new purchase or a requote after cancellation or publication expiry', function (string $closure): void {
    if ($closure === 'cancelled') {
        expect(app(BusinessCampaignStore::class)->cancel($this->campaign->actor_user_id, 1, $this->campaign->business_id,
            $this->campaign->id, 1, 'Cancelled.', (string) Str::uuid())['code'])->toBe('CAMPAIGN_CANCELLED');
    } else {
        $this->travelTo($this->campaign->expires_at);
        app(BusinessCampaignStore::class)->expireDue(10);
    }
    $other = PrimaryReservationFixture::investor();
    expect($this->checkout->reserve($other['user']->id, 1, $this->campaign->id, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...))['code'])
        ->toBe('CAMPAIGN_CLOSED');
    $requote = function (UnitRights $rights, array $campaign): PrimaryTerms {
        $base = PrimaryReservationFixture::terms($rights, $campaign)->toArray();

        return PrimaryTerms::disclosed($campaign['rate_pct'], $campaign['term_months'], $campaign['policy_version'], 'synthetic-disclosure-2',
            $base['earnings_fee'], '1', $rights);
    };
    $result = ($this->confirm)(admit: $requote);
    expect($result['code'])->toBe($closure === 'cancelled' ? 'CAMPAIGN_CLOSED' : 'RESERVATION_EXPIRED')
        ->and(PrimaryReservationVersion::query()->where('state', 'held')->count())->toBe(1)
        ->and(PrimaryCommitment::query()->count())->toBe(0)
        ->and(PrimaryReservationRecord::query()->count())->toBe(1);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
})->with(['cancelled', 'publication_expired']);

it('PROBE release after the scheduled campaign expiry closure returns the hold through the retained closure', function (): void {
    $this->travelTo($this->campaign->expires_at);
    expect(app(BusinessCampaignStore::class)->expireDue(10))->toBe(1);
    $result = ($this->release)();
    expect($result['code'])->toBe('RESERVATION_EXPIRED')->and(($this->cash)())->toBe([1, 0, 2, 0, '0']);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('PROBE an actor expiry cannot bind a completed receipt of the same reservation', function (): void {
    $this->travelTo($this->root->expires_at);
    expect(fn () => DB::transaction(function (): void {
        $this->store->expire($this->campaign->id, $this->root->id, $this->root->origin_operation_id);
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    }))->toThrow(QueryException::class);
});

it('PROBE capacity: expired and released holds keep their ordinals and still count toward the Party cap', function (): void {
    $this->travelTo($this->root->expires_at);
    expect(($this->release)()['code'])->toBe('RESERVATION_EXPIRED');
    $next = $this->checkout->reserve($this->investor['user']->id, 1, $this->campaign->id, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    expect(PrimaryReservationRecord::query()->whereKey($next['data']['reservation_id'])->sole()->ordinal_ranges)->toBe('{[4,5)}');
    $units = (int) $this->campaign->units ?: intdiv((int) $this->campaign->principal, 5000);
    $half = intdiv($units, 2) - 4;
    $capped = $this->checkout->reserve($this->investor['user']->id, 1, $this->campaign->id, (string) ($half + 1), (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    fwrite(STDERR, "units={$units} cap probe (3 expired + 1 held + ".($half + 1).'): '.$capped['code']."\n");
    expect($capped['code'])->toBe('INVESTOR_CAMPAIGN_CAP_EXCEEDED');
});

it('PROBE a same-key release on another reservation of the same Party conflicts', function (): void {
    $key = (string) Str::uuid();
    ($this->release)($key);
    $other = $this->checkout->reserve($this->investor['user']->id, 1, $this->campaign->id, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    expect(fn () => $this->checkout->release($this->investor['user']->id, 1, $this->campaign->id, $other['data']['reservation_id'], 1, $key))
        ->toThrow(CommandRejection::class, 'IDEMPOTENCY_CONFLICT');
});

/** @return list<string> */
function r175nTrace(Closure $action): array
{
    $trace = [];
    DB::listen(function ($query) use (&$trace): void {
        $sql = strtolower($query->sql);
        if (str_contains($sql, 'pg_advisory_xact_lock')) {
            $trace[] = 'A';
        } elseif (str_contains($sql, 'for update') && preg_match('/from "([a-z_]+)"/', $sql, $m)) {
            $trace[] = match ($m[1]) {
                'business_profiles' => 'B', 'users' => 'U', 'parties' => 'P', 'business_campaigns' => 'C',
                'primary_reservations' => 'R', 'investor_wallets' => 'W', default => 'X:'.$m[1],
            };
        } elseif (preg_match('/^insert into "(command_operations|primary_reservation_versions|ledger_entries)"/', $sql, $m)) {
            $trace[] = ['command_operations' => 'j', 'primary_reservation_versions' => 'v', 'ledger_entries' => 'l'][$m[1]];
        }
    });
    Event::listen(TransactionBeginning::class, function () use (&$trace): void {
        $trace[] = '(';
    });
    Event::listen(TransactionCommitted::class, function () use (&$trace): void {
        $trace[] = ')';
    });
    Event::listen(TransactionRolledBack::class, function () use (&$trace): void {
        $trace[] = '!';
    });
    $action();

    return $trace;
}

it('PROBE lock order on every release, expiry, refusal, replay and lookup path', function (string $path): void {
    $key = (string) Str::uuid();
    $setup = match ($path) {
        'release replay', 'find release', 'release idempotent new key' => fn () => ($this->release)($key),
        'release on confirmed' => fn () => ($this->confirm)(),
        'expired confirm replay' => function () use ($key): void {
            $this->travelTo($this->root->expires_at);
            ($this->confirm)($key);
        },
        'expired release', 'expired confirm', 'system expire' => fn () => $this->travelTo($this->root->expires_at),
        default => fn () => null,
    };
    $setup();
    $trace = r175nTrace(fn () => match ($path) {
        'release', 'release replay' => ($this->release)($key),
        'release conflict' => ($this->release)(revision: 5),
        'release idempotent new key', 'release on confirmed' => ($this->release)(revision: 2),
        'find release' => $this->checkout->findRelease($this->investor['user']->id, 1, $this->campaign->id, $this->root->id, $key),
        'expired release' => ($this->release)(),
        'expired confirm', 'expired confirm replay' => ($this->confirm)($key),
        'system expire' => DB::transaction(fn () => $this->store->expire($this->campaign->id, $this->root->id)),
    });
    $compact = implode('', $trace);
    fwrite(STDERR, str_pad($path, 28).' '.$compact."\n");
    $rank = ['B' => 1, 'U' => 2, 'P' => 3, 'A' => 4, 'C' => 5, 'R' => 6, 'W' => 7];
    $locks = array_values(array_filter($trace, fn (string $t): bool => isset($rank[$t]) || str_starts_with($t, 'X:')));
    expect(array_filter($locks, fn (string $t): bool => str_starts_with($t, 'X:')))->toBe([]);
    // The first lock is the Business; every first acquisition of a later lock follows the documented order.
    expect($locks[0] ?? null)->toBe('B');
    $firsts = array_values(array_unique($locks));
    $ranks = array_map(fn (string $t): int => $rank[$t], $firsts);
    $sorted = $ranks;
    sort($sorted);
    expect($ranks)->toBe($sorted);
    // Within the whole trace a lock never follows a higher-ranked NEW lock: re-acquisitions after the
    // rejection savepoint are allowed only while the Business row is still held (it is taken outside it).
    $max = 0;
    foreach ($locks as $lock) {
        if ($rank[$lock] < $max && ! in_array($lock, ['B', 'C', 'R'], true)) {
            throw new RuntimeException("lock {$lock} after rank {$max} in {$compact}");
        }
        $max = max($max, $rank[$lock]);
    }
})->with(['release', 'release replay', 'release conflict', 'release idempotent new key', 'release on confirmed', 'find release',
    'expired release', 'expired confirm', 'expired confirm replay', 'system expire']);

