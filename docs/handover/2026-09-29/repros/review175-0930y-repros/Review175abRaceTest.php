<?php

declare(strict_types=1);

/*
 * Review #175 range d753c794..494e12d1 (R2). Real forks; every wait is proven with pg_blocking_pids, never sleeps.
 * The child writes one JSON line describing its outcome, then exits 0.
 */

use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Application\Wallet\Contracts\PrimaryReturnedCash;
use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\LockedWallet;
use App\Application\Wallet\PostingSource;
use App\Domain\Wallet\WalletMoney;
use App\Models\CommandOperation;
use App\Models\InvestorWallet;
use App\Models\LedgerEntry;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

/** @return array{int, resource, int} */
function r175abFork(Closure $work): array
{
    $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    stream_set_timeout($channels[0], 40);
    stream_set_timeout($channels[1], 40);
    $pid = pcntl_fork();
    if ($pid === 0) {
        fclose($channels[0]);
        DB::purge();
        try {
            DB::statement("SET lock_timeout = '30s'");
            fwrite($channels[1], DB::selectOne('SELECT pg_backend_pid() AS pid')->pid."\n");
            if (fgets($channels[1]) !== "go\n") {
                exit(3);
            }
            $result = $work();
            fwrite($channels[1], json_encode(['returned' => is_array($result) ? ['code' => $result['code'], 'status' => $result['status'], 'revision' => $result['revision'], 'entry_id' => $result['data']['entry_id'] ?? null]
                : (is_object($result) ? $result::class : $result)])."\n");
        } catch (Throwable $exception) {
            fwrite($channels[1], json_encode(['threw' => $exception::class.' '.mb_substr($exception->getMessage(), 0, 200)])."\n");
        }
        exit(0);
    }
    fclose($channels[1]);

    return [$pid, $channels[0], (int) trim((string) fgets($channels[0]))];
}

/** @return array{blocked: bool, query: string, waiting_on: list<string>} */
function r175abBlocked(int $backend, int $blocker, float $seconds = 20.0): array
{
    $probe = DB::connection('r175ab_probe');
    $deadline = microtime(true) + $seconds;
    while (microtime(true) < $deadline) {
        if ($probe->selectOne('SELECT ? = ANY(pg_blocking_pids(?)) AS ok', [$blocker, $backend])->ok) {
            $locks = $probe->select("SELECT l.locktype, l.mode, COALESCE(c.relname, x.relname) AS rel FROM pg_locks l
                LEFT JOIN pg_class c ON c.oid = l.relation
                LEFT JOIN pg_locks h ON h.locktype = 'transactionid' AND h.transactionid = l.transactionid AND h.granted
                LEFT JOIN LATERAL (SELECT c2.relname FROM pg_locks t JOIN pg_class c2 ON c2.oid = t.relation WHERE t.pid = l.pid AND t.locktype = 'tuple' LIMIT 1) x ON true
                WHERE l.pid = ? AND NOT l.granted", [$backend]);

            return ['blocked' => true, 'query' => mb_substr((string) $probe->selectOne('SELECT query FROM pg_stat_activity WHERE pid = ?', [$backend])->query, 0, 90),
                'waiting_on' => array_map(fn (object $lock): string => $lock->locktype.':'.$lock->mode.':'.($lock->rel ?? '?'), $locks)];
        }
        usleep(10_000);
    }

    return ['blocked' => false, 'query' => mb_substr((string) ($probe->selectOne('SELECT query FROM pg_stat_activity WHERE pid = ?', [$backend])?->query ?? 'backend gone'), 0, 90), 'waiting_on' => []];
}

/** @return array<string, mixed> */
function r175abReap(int $pid, $channel): array
{
    $line = fgets($channel);
    pcntl_waitpid($pid, $status);

    return is_string($line) ? (array) json_decode($line, true) : ['lost' => true];
}

beforeEach(function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    config(['database.connections.r175ab_holder' => config('database.connections.pgsql'), 'database.connections.r175ab_probe' => config('database.connections.pgsql')]);
    $this->campaign = PrimaryReservationFixture::campaign();
    $this->investor = PrimaryReservationFixture::investor();
    $this->checkout = app(PrimaryCheckout::class);
    $result = $this->checkout->reserve($this->investor['user']->id, 1, $this->campaign->id, '3', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $this->root = PrimaryReservationRecord::query()->whereKey($result['data']['reservation_id'])->sole();
    $this->walletId = InvestorWallet::query()->where('party_id', $this->root->party_id)->value('id');
    $this->wallet = new LockedWallet($this->walletId, $this->root->party_id);
    $this->source = new PostingSource('primary_reservation', $this->root->id, $this->root->origin_operation_id);
    $this->facts = fn (): array => ['states' => PrimaryReservationVersion::query()->where('primary_reservation_id', $this->root->id)->orderBy('revision')->pluck('state')->all(),
        'kinds' => LedgerEntry::query()->where('source_id', $this->root->id)->orderBy('kind')->pluck('kind')->all(),
        'release_ops' => CommandOperation::query()->where('command', 'primary.release')->count(), 'failures' => DB::table('primary_expiry_failures')->count()];
    $this->release = fn (int $revision = 1): array => $this->checkout->release($this->investor['user']->id, 1, $this->campaign->id, $this->root->id, $revision, (string) Str::uuid());
});

afterEach(function (): void {
    if (DB::transactionLevel() > 0) {
        DB::rollBack();
    }
    DB::purge('r175ab_holder');
    DB::purge('r175ab_probe');
});

it('AC-1 expiry sweep vs actor release on the same overdue hold, either winner: one terminal version, one cash return', function (string $winner): void {
    $this->travelTo($this->root->expires_at);
    DB::disconnect();
    [$pid, $channel, $backend] = r175abFork($winner === 'actor' ? fn () => app(PrimaryReservations::class)->expireDue(10) : fn () => ($this->release)());
    DB::beginTransaction();
    $parent = DB::selectOne('SELECT pg_backend_pid() AS pid')->pid;
    $first = $winner === 'actor' ? ($this->release)()['code'] : app(PrimaryReservations::class)->expire($this->campaign->id, $this->root->id)?->posting->kind;
    fwrite($channel, "go\n");
    $wait = r175abBlocked($backend, $parent);
    $seenWhileBlocked = DB::connection('r175ab_probe')->table('ledger_entries')->where('kind', 'primary_release')->count();
    DB::commit();
    $child = r175abReap($pid, $channel);
    $facts = ($this->facts)();
    $verified = DB::transaction(fn () => app(PrimaryReturnedCash::class)->requireReturned($this->wallet, WalletMoney::of($this->root->principal), $this->source)->returnKind);
    fwrite(STDERR, "\nAC-1 [".$winner.' wins] parent='.json_encode($first).' child wait='.json_encode($wait).' releases visible while blocked='.$seenWhileBlocked
        .' child='.json_encode($child).' facts='.json_encode($facts).' verifier='.$verified."\n");
    expect($wait['blocked'])->toBeTrue()->and($facts['states'])->toBe(['held', 'expired'])->and($facts['kinds'])->toBe(['primary_hold', 'primary_release'])
        ->and($facts['failures'])->toBe(0)->and($verified)->toBe('primary_release');
})->with(['actor', 'sweep']);

it('AC-2 actor release of a live hold vs a concurrent wallet refund attempt: the refund waits on the wallet and is then refused', function (): void {
    DB::disconnect();
    [$pid, $channel, $backend] = r175abFork(fn () => DB::transaction(function (): string {
        $port = app(WalletPostings::class);

        return $port->refund($port->lockForParty($this->root->party_id), WalletMoney::of($this->root->principal), $this->source)->kind;
    }));
    DB::beginTransaction();
    $parent = DB::selectOne('SELECT pg_backend_pid() AS pid')->pid;
    $first = ($this->release)()['code'];
    fwrite($channel, "go\n");
    $wait = r175abBlocked($backend, $parent);
    DB::commit();
    $child = r175abReap($pid, $channel);
    $facts = ($this->facts)();
    fwrite(STDERR, "\nAC-2 parent=".$first.' child wait='.json_encode($wait).' child='.json_encode($child).' facts='.json_encode($facts)."\n");
    expect($wait['blocked'])->toBeTrue()->and($child['threw'] ?? '')->toContain('WALLET_POSTING_STATE_INVALID')
        ->and($facts['states'])->toBe(['held', 'released'])->and($facts['kinds'])->toBe(['primary_hold', 'primary_release']);
});

it('AC-3 an in-flight refund of a committed purchase vs a concurrent actor release and system expiry: no release, no wait on the wallet', function (): void {
    $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $this->root->id)->sole();
    $this->checkout->confirm($this->investor['user']->id, 1, $this->campaign->id, $this->root->id, 1, $version->payload['terms']['disclosure_version'],
        $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $this->travelTo($this->root->expires_at);
    DB::disconnect();
    [$pid, $channel, $backend] = r175abFork(fn () => implode(', ', [($this->release)(2)['code'], 'sweep: '.app(PrimaryReservations::class)->expireDue(10),
        DB::transaction(fn () => app(PrimaryReservations::class)->expire($this->campaign->id, $this->root->id)) === null ? 'expire: null' : 'expire: released']));
    DB::beginTransaction();
    $parent = DB::selectOne('SELECT pg_backend_pid() AS pid')->pid;
    $port = app(WalletPostings::class);
    $port->refund($port->lockForParty($this->root->party_id), WalletMoney::of($this->root->principal), $this->source);
    fwrite($channel, "go\n");
    $wait = r175abBlocked($backend, $parent, 3.0);
    $child = r175abReap($pid, $channel);
    DB::commit();
    $facts = ($this->facts)();
    $verified = DB::transaction(fn () => app(PrimaryReturnedCash::class)->requireReturned($this->wallet, WalletMoney::of($this->root->principal), $this->source)->returnKind);
    fwrite(STDERR, "\nAC-3 child blocked by the refund=".json_encode($wait['blocked']).' child (finished before the refund committed)='.json_encode($child).' facts='.json_encode($facts).' verifier='.$verified."\n");
    expect($wait['blocked'])->toBeFalse()->and($facts['states'])->toBe(['held', 'confirmed'])->and($facts['kinds'])->toBe(['primary_commit', 'primary_hold', 'primary_refund']);
});

it('AC-4 lock order under contention: the wallet is taken last and never before Business, campaign or root', function (string $held): void {
    DB::disconnect();
    $holder = DB::connection('r175ab_holder');
    $holder->beginTransaction();
    $holderPid = $holder->selectOne('SELECT pg_backend_pid() AS pid')->pid;
    $targets = ['business_profiles' => $this->campaign->business_id, 'business_campaigns' => $this->campaign->id, 'primary_reservations' => $this->root->id, 'investor_wallets' => $this->walletId];
    $holder->select('SELECT id FROM '.$held.' WHERE id = ? FOR UPDATE', [$targets[$held]]);
    [$pid, $channel, $backend] = r175abFork(fn () => ($this->release)());
    fwrite($channel, "go\n");
    $wait = r175abBlocked($backend, $holderPid);
    $probe = DB::connection('r175ab_probe');
    $free = [];
    foreach ($targets as $table => $id) {
        if ($table === $held) {
            continue;
        }
        try {
            $probe->transaction(fn () => $probe->select('SELECT id FROM '.$table.' WHERE id = ? FOR UPDATE NOWAIT', [$id]));
            $free[$table] = 'free';
        } catch (Throwable) {
            $free[$table] = 'LOCKED by the waiting release';
        }
    }
    $holder->rollBack();
    $child = r175abReap($pid, $channel);
    fwrite(STDERR, "\nAC-4 [holder has ".$held.'] release wait='.json_encode($wait).' other rows='.json_encode($free).' child='.json_encode($child)."\n");
    expect($wait['blocked'])->toBeTrue()->and($child['returned']['code'] ?? null)->toBe('RESERVATION_RELEASED')
        ->and($free)->toBe($held === 'investor_wallets'
            ? ['business_profiles' => 'LOCKED by the waiting release', 'business_campaigns' => 'LOCKED by the waiting release', 'primary_reservations' => 'LOCKED by the waiting release']
            : ['business_campaigns' => 'free', 'primary_reservations' => 'free', 'investor_wallets' => 'free']);
})->with(['business_profiles', 'investor_wallets']);

it('AC-5 a release, system expiry or sweep inside REPEATABLE READ or SERIALIZABLE is refused and leaves nothing behind', function (string $isolation, string $path): void {
    if ($path !== 'release') {
        $this->travelTo($this->root->expires_at);
    }
    DB::disconnect();
    $outcome = null;
    try {
        if ($path === 'sweep') {
            DB::statement('SET SESSION CHARACTERISTICS AS TRANSACTION ISOLATION LEVEL '.$isolation);
            $outcome = 'returned '.app(PrimaryReservations::class)->expireDue(10);
        } else {
            DB::beginTransaction();
            DB::statement('SET TRANSACTION ISOLATION LEVEL '.$isolation);
            $result = $path === 'release' ? ($this->release)() : app(PrimaryReservations::class)->expire($this->campaign->id, $this->root->id);
            $outcome = 'NOT REFUSED '.json_encode(is_array($result) ? $result['code'] : $result?->posting->kind);
            DB::commit();
        }
    } catch (Throwable $exception) {
        $outcome = 'threw '.$exception::class.' '.mb_substr($exception->getMessage(), 0, 80);
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
    }
    DB::disconnect();
    $facts = ($this->facts)();
    fwrite(STDERR, "\nAC-5 [".$isolation.' '.$path.'] '.$outcome.' facts='.json_encode($facts)
        .' failure='.json_encode(DB::table('primary_expiry_failures')->value('exception_class'))."\n");
    expect($outcome)->toContain('PRIMARY_CASH_ISOLATION_REQUIRED')->and($facts['states'])->toBe(['held'])->and($facts['kinds'])->toBe(['primary_hold'])->and($facts['release_ops'])->toBe(0);
})->with(['REPEATABLE READ', 'SERIALIZABLE'])->with(['release', 'expire', 'sweep']);

it('AC-6 the verifier reads movements only after the wallet gate: a reader that starts first still sees the concurrent release', function (): void {
    DB::disconnect();
    [$pid, $channel, $backend] = r175abFork(fn () => DB::transaction(fn () => app(PrimaryReturnedCash::class)->requireReturned($this->wallet, WalletMoney::of($this->root->principal), $this->source)->returnKind));
    DB::beginTransaction();
    $parent = DB::selectOne('SELECT pg_backend_pid() AS pid')->pid;
    ($this->release)();
    fwrite($channel, "go\n");
    $wait = r175abBlocked($backend, $parent);
    DB::commit();
    $child = r175abReap($pid, $channel);
    fwrite(STDERR, "\nAC-6 verifier wait=".json_encode($wait).' child='.json_encode($child)."\n");
    expect($wait['blocked'])->toBeTrue()->and($child['returned'] ?? null)->toBe('primary_release');
});
