<?php

declare(strict_types=1);

use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Domain\Operations\CommandRejection;
use App\Models\CommandOperation;
use App\Models\InvestorWallet;
use App\Models\LedgerEntry;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

it('requires an outer transaction for release and system expiry persistence', function (): void {
    $store = app(PrimaryReservations::class);
    expect(fn () => $store->release('campaign', 'reservation', 'party', 'operation', 1))->toThrow(CommandRejection::class, 'PRIMARY_TRANSACTION_REQUIRED')
        ->and(fn () => $store->expire('campaign', 'reservation'))->toThrow(CommandRejection::class, 'PRIMARY_TRANSACTION_REQUIRED');
});

it('retains the release or expiry locks through the real caller commit', function (bool $expired): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $investor = PrimaryReservationFixture::investor();
    $checkout = app(PrimaryCheckout::class);
    $checkout->reserve($investor['user']->id, 1, $campaign->id, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $root = PrimaryReservationRecord::query()->sole();
    if ($expired) {
        $this->travelTo($root->expires_at);
    }
    config(['database.connections.primary_release_observer' => config('database.connections.pgsql')]);
    $observer = DB::connection('primary_release_observer');
    $targets = ['business_profiles' => $campaign->business_id, 'users' => $investor['user']->id, 'parties' => $investor['party']->id,
        'business_campaigns' => $campaign->id, 'primary_reservations' => $root->id,
        'investor_wallets' => InvestorWallet::query()->where('party_id', $investor['party']->id)->value('id')];
    try {
        DB::beginTransaction();
        $result = $checkout->release($investor['user']->id, 1, $campaign->id, $root->id, 1, (string) Str::uuid());
        expect($result['code'])->toBe($expired ? 'RESERVATION_EXPIRED' : 'RESERVATION_RELEASED');
        foreach ($targets as $table => $id) {
            expect(fn () => $observer->transaction(fn () => $observer->table($table)->where('id', $id)->lock('FOR UPDATE NOWAIT')->first()))
                ->toThrow(QueryException::class, 'could not obtain lock');
        }
        expect($observer->table('ledger_entries')->where('kind', 'primary_release')->count())->toBe(0);
        DB::commit();
        foreach ($targets as $table => $id) {
            expect($observer->transaction(fn () => $observer->table($table)->where('id', $id)->lock('FOR UPDATE NOWAIT')->first())?->id)->toBe($id);
        }
        expect($observer->table('ledger_entries')->where('kind', 'primary_release')->count())->toBe(1)
            ->and(PrimaryReservationVersion::query()->orderByDesc('revision')->value('operation_id'))->toBe($result['operation_id']);
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        DB::purge('primary_release_observer');
    }
})->with([false, true]);

it('serializes release expiry and confirmation races to one terminal cash effect', function (string $race): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $investor = PrimaryReservationFixture::investor();
    app(PrimaryCheckout::class)->reserve($investor['user']->id, 1, $campaign->id, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $root = PrimaryReservationRecord::query()->sole();
    $version = PrimaryReservationVersion::query()->sole();
    if (str_contains($race, 'expire')) {
        $this->travelTo($root->expires_at);
    }
    $request = (string) Str::uuid();
    $second = match ($race) {
        'same_release', 'two_release' => 'release',
        'release_confirm' => 'confirm',
        'expire_release', 'expire_confirm', 'two_expire' => 'expire',
        'sweep_expire_release', 'sweep_expire_confirm', 'two_sweep_expire' => 'sweep',
        default => throw new InvalidArgumentException('Unknown Primary race.'),
    };
    $first = match ($race) {
        'expire_confirm', 'sweep_expire_confirm' => 'confirm', 'two_expire' => 'expire', 'two_sweep_expire' => 'sweep', default => 'release',
    };
    $children = [];
    DB::disconnect();
    foreach ([$first, $second] as $index => $action) {
        $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        if ($channels === false) {
            throw new RuntimeException('Could not create Primary release barrier.');
        }
        stream_set_timeout($channels[0], 8);
        stream_set_timeout($channels[1], 8);
        $pid = pcntl_fork();
        if ($pid === -1) {
            throw new RuntimeException('Could not fork Primary release contender.');
        }
        if ($pid === 0) {
            fclose($channels[0]);
            DB::purge();
            try {
                DB::statement("SET lock_timeout = '5s'");
                fwrite($channels[1], "ready\n");
                if (fgets($channels[1]) !== "go\n") {
                    exit(3);
                }
                $checkout = app(PrimaryCheckout::class);
                $key = $race === 'same_release' ? $request : (string) Str::uuid();
                $result = match ($action) {
                    'release' => $checkout->release($investor['user']->id, 1, $campaign->id, $root->id, 1, $key),
                    'confirm' => $checkout->confirm($investor['user']->id, 1, $campaign->id, $root->id, 1,
                        $version->payload['terms']['disclosure_version'], $version->payload['disclosure_sha256'], $key, PrimaryReservationFixture::terms(...)),
                    'sweep' => app(PrimaryReservations::class)->expireDue(1),
                    'expire' => DB::transaction(fn () => app(PrimaryReservations::class)->expire($campaign->id, $root->id)),
                };
                if (is_array($result) && ! in_array($result['code'], ['RESERVATION_RELEASED', 'RESERVATION_CONFIRMED', 'RESERVATION_EXPIRED', 'VERSION_CONFLICT'], true)) {
                    exit(2);
                }
                exit(0);
            } catch (Throwable $exception) {
                fwrite(STDERR, $exception::class.': '.$exception->getCode().' '.$exception->getMessage()."\n");
                exit(4);
            }
        }
        fclose($channels[1]);
        $children[$pid] = $channels[0];
    }
    try {
        foreach ($children as $channel) {
            expect(fgets($channel))->toBe("ready\n");
        }
        foreach ($children as $channel) {
            fwrite($channel, "go\n");
        }
        $statuses = [];
        foreach ($children as $pid => $channel) {
            pcntl_waitpid($pid, $status);
            $statuses[] = pcntl_wifexited($status) ? pcntl_wexitstatus($status) : -1;
        }
        $terminal = PrimaryReservationVersion::query()->orderByDesc('revision')->firstOrFail();
        $confirmed = $terminal->state === 'confirmed';
        expect($statuses)->toBe([0, 0])->and(PrimaryReservationVersion::query()->count())->toBe(2)
            ->and(LedgerEntry::query()->whereIn('kind', ['primary_release', 'primary_commit'])->count())->toBe(1)
            ->and(LedgerEntry::query()->where('kind', $confirmed ? 'primary_commit' : 'primary_release')->count())->toBe(1)
            ->and(PrimaryCommitment::query()->count())->toBe($confirmed ? 1 : 0);
        if ($race === 'same_release') {
            expect(CommandOperation::query()->where('command', 'primary.release')->count())->toBe(1);
        }
        if (str_contains($race, 'expire')) {
            expect($terminal->state)->toBe('expired');
        }
        if (in_array($race, ['two_expire', 'two_sweep_expire'], true)) {
            expect($terminal->operation_id)->toBeNull();
        }
    } finally {
        foreach ($children as $pid => $channel) {
            fclose($channel);
            pcntl_waitpid($pid, $status);
        }
    }
})->with(['same_release', 'two_release', 'release_confirm', 'expire_release', 'expire_confirm', 'two_expire', 'sweep_expire_release', 'sweep_expire_confirm', 'two_sweep_expire']);

it('rolls release or expiry and its receipt back when the caller aborts', function (bool $expired): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $investor = PrimaryReservationFixture::investor();
    $checkout = app(PrimaryCheckout::class);
    $checkout->reserve($investor['user']->id, 1, $campaign->id, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $root = PrimaryReservationRecord::query()->sole();
    if ($expired) {
        $this->travelTo($root->expires_at);
    }
    $release = fn (): array => $checkout->release($investor['user']->id, 1, $campaign->id, $root->id, 1, (string) Str::uuid());
    expect(fn () => DB::transaction(function () use ($release): void {
        $release();
        throw new RuntimeException('Caller aborted.');
    }))->toThrow(RuntimeException::class, 'Caller aborted.')
        ->and(PrimaryReservationVersion::query()->count())->toBe(1)
        ->and(LedgerEntry::query()->where('kind', 'primary_release')->count())->toBe(0)
        ->and(CommandOperation::query()->where('command', 'primary.release')->count())->toBe(0);
    expect($release()['code'])->toBe($expired ? 'RESERVATION_EXPIRED' : 'RESERVATION_RELEASED');
})->with([false, true]);

it('retains earlier sweep commits when a later candidate fails atomically', function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $investor = PrimaryReservationFixture::investor();
    $checkout = app(PrimaryCheckout::class);
    $checkout->reserve($investor['user']->id, 1, $campaign->id, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $first = PrimaryReservationRecord::query()->sole();
    $this->travel(1)->seconds();
    $reserved = $checkout->reserve($investor['user']->id, 1, $campaign->id, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $second = PrimaryReservationRecord::query()->whereKey($reserved['data']['reservation_id'])->sole();
    $this->travelTo($second->expires_at);
    DB::listen(function (QueryExecuted $query) use ($second): void {
        if (str_starts_with($query->sql, 'insert into "primary_reservation_versions"') && in_array($second->id, $query->bindings, true)) {
            throw new RuntimeException('Injected expiry persistence failure.');
        }
    });
    expect(fn () => app(PrimaryReservations::class)->expireDue(2))->toThrow(RuntimeException::class, 'Injected expiry persistence failure.')
        ->and(DB::transactionLevel())->toBe(0)
        ->and(PrimaryReservationVersion::query()->where('primary_reservation_id', $first->id)->orderByDesc('revision')->value('state'))->toBe('expired')
        ->and(PrimaryReservationVersion::query()->where('primary_reservation_id', $second->id)->count())->toBe(1)
        ->and(LedgerEntry::query()->where('kind', 'primary_release')->sole()->source_id)->toBe($first->id);
});

it('moves a failed expiry behind healthy holds across bounded runs and retries it after repair', function (int $limit): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $investor = PrimaryReservationFixture::investor();
    $checkout = app(PrimaryCheckout::class);
    $checkout->reserve($investor['user']->id, 1, $campaign->id, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $broken = PrimaryReservationRecord::query()->sole();
    $this->travel(1)->seconds();
    Cache::forget('fortify.2fa_codes.'.md5((new Google2FA)->getCurrentOtp('JBSWY3DPEHPK3PXP')));
    $other = PrimaryReservationFixture::campaign();
    $reserved = $checkout->reserve($investor['user']->id, 1, $other->id, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $healthy = PrimaryReservationRecord::query()->whereKey($reserved['data']['reservation_id'])->sole();
    $this->travelTo($healthy->expires_at);
    $failure = new class
    {
        public bool $enabled = true;
    };
    DB::listen(function (QueryExecuted $query) use ($broken, $failure): void {
        if ($failure->enabled && str_starts_with($query->sql, 'insert into "primary_reservation_versions"') && in_array($broken->id, $query->bindings, true)) {
            throw new RuntimeException('Injected corrupt hold.');
        }
    });
    expect(fn () => app(PrimaryReservations::class)->expireDue($limit))->toThrow(RuntimeException::class, 'Injected corrupt hold.')
        ->and(DB::transactionLevel())->toBe(0)
        ->and(PrimaryReservationVersion::query()->where('primary_reservation_id', $broken->id)->count())->toBe(1)
        ->and(DB::table('primary_expiry_failures')->where('primary_reservation_id', $broken->id)->value('exception_class'))->toBe(RuntimeException::class);
    if ($limit === 1) {
        expect(app(PrimaryReservations::class)->expireDue(1))->toBe(1);
    }
    expect(PrimaryReservationVersion::query()->where('primary_reservation_id', $healthy->id)->orderByDesc('revision')->value('state'))->toBe('expired')
        ->and(LedgerEntry::query()->where('kind', 'primary_release')->sole()->source_id)->toBe($healthy->id);
    $firstAttempt = DB::table('primary_expiry_failures')->sole()->last_attempted_at;
    $this->travel(1)->seconds();
    expect(fn () => app(PrimaryReservations::class)->expireDue(1))->toThrow(RuntimeException::class, 'Injected corrupt hold.')
        ->and(DB::table('primary_expiry_failures')->count())->toBe(1)
        ->and(DB::table('primary_expiry_failures')->sole()->last_attempted_at)->not->toBe($firstAttempt);
    $failure->enabled = false;
    expect(app(PrimaryReservations::class)->expireDue(1))->toBe(1)
        ->and(app(PrimaryReservations::class)->expireDue(1))->toBe(0)
        ->and(LedgerEntry::query()->where('kind', 'primary_release')->count())->toBe(2);
})->with([1, 2]);
