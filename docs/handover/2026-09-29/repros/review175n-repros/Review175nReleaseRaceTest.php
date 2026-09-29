<?php

declare(strict_types=1);

/*
 * Review repros for PR #175 range c8f30fcb..205a8a08 (S3-C release/expiry). Copy into tests/Concurrency/.
 * These commit for real (DatabaseTruncation), so the checkout's withInvestor transaction is the
 * top-level transaction and the journal's rejection savepoint is a real savepoint.
 *
 * (1) Cash failure AFTER the real wallet posting (decorator delegates, then throws) must leave no
 *     receipt, no terminal version, no release entry; a same-key replay afterwards then succeeds once.
 * (2) Caller rollback around an expired confirmation (PR covers release only).
 * (3) Race matrix: the parent holds the Business row, both forked contenders are proven blocked by the
 *     parent through pg_blocking_pids/pg_locks, then the parent commits and they race for real.
 *     Each race must have exactly one terminal effect and exactly one cash movement.
 */

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\GetInvestorWallet;
use App\Application\Wallet\LockedWallet;
use App\Application\Wallet\PostingReceipt;
use App\Application\Wallet\PostingSource;
use App\Domain\Wallet\WalletMoney;
use App\Infrastructure\Wallet\EloquentWalletPostings;
use App\Models\BusinessCampaignClosure;
use App\Models\CommandOperation;
use App\Models\LedgerEntry;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

/** Delegates to the real wallet, then fails after the release entry is written. */
final class Review175nFailAfterReleaseWallet implements WalletPostings
{
    public function __construct(private WalletPostings $inner) {}

    public function lockForParty(string $partyId): LockedWallet
    {
        return $this->inner->lockForParty($partyId);
    }

    public function hold(LockedWallet $wallet, WalletMoney $amount, PostingSource $source): PostingReceipt
    {
        return $this->inner->hold($wallet, $amount, $source);
    }

    public function commit(LockedWallet $wallet, WalletMoney $amount, PostingSource $source): PostingReceipt
    {
        return $this->inner->commit($wallet, $amount, $source);
    }

    public function release(LockedWallet $wallet, WalletMoney $amount, PostingSource $source): PostingReceipt
    {
        $receipt = $this->inner->release($wallet, $amount, $source);
        expect(DB::table('ledger_entries')->where('id', $receipt->entryId)->exists())->toBeTrue();

        throw new RuntimeException('Failed after the release posting.');
    }

    public function refund(LockedWallet $wallet, WalletMoney $amount, PostingSource $source): PostingReceipt
    {
        return $this->inner->refund($wallet, $amount, $source);
    }
}

function r175nSetup(): array
{
    test()->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $investor = PrimaryReservationFixture::investor();
    app(PrimaryCheckout::class)->reserve($investor['user']->id, 1, $campaign->id, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...));

    return [$campaign, $investor, PrimaryReservationRecord::query()->sole(), PrimaryReservationVersion::query()->sole()];
}

/** @return array<string, mixed> */
function r175nAct(string $action, array $ctx, string $key): mixed
{
    [$campaign, $investor, $root, $version] = $ctx;
    $checkout = app(PrimaryCheckout::class);

    return match ($action) {
        'release' => $checkout->release($investor['user']->id, 1, $campaign->id, $root->id, 1, $key),
        'confirm' => $checkout->confirm($investor['user']->id, 1, $campaign->id, $root->id, 1,
            $version->payload['terms']['disclosure_version'], $version->payload['disclosure_sha256'], $key, PrimaryReservationFixture::terms(...)),
        'expire' => DB::transaction(fn () => app(PrimaryReservations::class)->expire($campaign->id, $root->id)) === null ? ['code' => 'EXPIRE_NULL'] : ['code' => 'EXPIRE_DONE'],
        'cancel' => app(BusinessCampaignStore::class)->cancel($campaign->actor_user_id, 1, $campaign->business_id, $campaign->id, 1, 'Race.', (string) Str::uuid()),
    };
}

function r175nHeld(array $investor): string
{
    return app(GetInvestorWallet::class)->handle($investor['user']->id, 1)['wallet']['breakdown']['held']['amount'];
}

it('rolls back the rejected receipt, expiry and release entry when the wallet fails after posting, then replays once', function (string $action, bool $expired): void {
    $ctx = r175nSetup();
    [$campaign, $investor, $root] = $ctx;
    if ($expired) {
        test()->travelTo($root->expires_at);
    }
    app()->instance(WalletPostings::class, new Review175nFailAfterReleaseWallet(app(EloquentWalletPostings::class)));
    app()->forgetInstance(PrimaryCheckout::class);
    app()->forgetInstance(PrimaryReservations::class);
    $key = (string) Str::uuid();
    expect(fn () => r175nAct($action, $ctx, $key))->toThrow(RuntimeException::class, 'Failed after the release posting.');
    expect(DB::transactionLevel())->toBe(0)
        ->and(CommandOperation::query()->where('command', 'primary.'.$action)->count())->toBe(0)
        ->and(PrimaryReservationVersion::query()->count())->toBe(1)
        ->and(LedgerEntry::query()->where('kind', 'primary_release')->count())->toBe(0)
        ->and(r175nHeld($investor))->toBe('5000');
    app()->forgetInstance(WalletPostings::class);
    app()->forgetInstance(PrimaryCheckout::class);
    app()->forgetInstance(PrimaryReservations::class);
    $first = r175nAct($action, $ctx, $key);
    $again = r175nAct($action, $ctx, $key);
    expect($first['code'])->toBe($expired ? 'RESERVATION_EXPIRED' : 'RESERVATION_RELEASED')->and($again)->toBe($first)
        ->and(CommandOperation::query()->where('command', 'primary.'.$action)->count())->toBe(1)
        ->and(PrimaryReservationVersion::query()->count())->toBe(2)
        ->and(PrimaryReservationVersion::query()->orderByDesc('revision')->value('operation_id'))->toBe($first['operation_id'])
        ->and(LedgerEntry::query()->where('kind', 'primary_release')->count())->toBe(1)
        ->and(r175nHeld($investor))->toBe('0');
})->with([['release', true], ['confirm', true], ['release', false]]);

it('rolls an expired confirmation receipt and its expiry back when the caller aborts', function (): void {
    $ctx = r175nSetup();
    test()->travelTo($ctx[2]->expires_at->addSecond());
    $key = (string) Str::uuid();
    expect(fn () => DB::transaction(function () use ($ctx, $key): void {
        expect(r175nAct('confirm', $ctx, $key)['code'])->toBe('RESERVATION_EXPIRED');
        throw new RuntimeException('Caller aborted.');
    }))->toThrow(RuntimeException::class, 'Caller aborted.');
    expect(CommandOperation::query()->where('command', 'primary.confirm')->count())->toBe(0)
        ->and(PrimaryReservationVersion::query()->count())->toBe(1)
        ->and(LedgerEntry::query()->where('kind', 'primary_release')->count())->toBe(0);
});

it('serializes each terminal race on the Business lock to exactly one terminal effect and one cash movement', function (string $a, string $b, bool $expired, bool $sameKey): void {
    $ctx = r175nSetup();
    [$campaign, $investor, $root] = $ctx;
    if ($expired) {
        test()->travelTo($root->expires_at);
    }
    $shared = (string) Str::uuid();
    $children = [];
    DB::disconnect();
    foreach ([$a, $b] as $action) {
        $pair = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        stream_set_timeout($pair[0], 15);
        stream_set_timeout($pair[1], 15);
        $pid = pcntl_fork();
        if ($pid === 0) {
            fclose($pair[0]);
            DB::purge();
            try {
                DB::statement("SET lock_timeout = '8s'");
                fwrite($pair[1], DB::selectOne('SELECT pg_backend_pid() AS pid')->pid."\n");
                if (fgets($pair[1]) !== "go\n") {
                    exit(3);
                }
                $result = r175nAct($action, $ctx, $sameKey ? $shared : (string) Str::uuid());
                fwrite($pair[1], json_encode(['action' => $action, 'code' => $result['code'], 'status' => $result['status'] ?? null, 'op' => $result['operation_id'] ?? null])."\n");
                exit(0);
            } catch (Throwable $exception) {
                fwrite($pair[1], json_encode(['action' => $action, 'exception' => $exception::class.': '.$exception->getMessage()])."\n");
                exit(4);
            }
        }
        fclose($pair[1]);
        $children[$pid] = $pair[0];
    }
    $results = [];
    try {
        $backends = [];
        foreach ($children as $pid => $channel) {
            $backends[$pid] = (int) trim((string) fgets($channel));
        }
        DB::beginTransaction();
        DB::table('business_profiles')->where('id', $campaign->business_id)->lock('FOR NO KEY UPDATE')->first();
        $me = (int) DB::selectOne('SELECT pg_backend_pid() AS pid')->pid;
        foreach ($children as $channel) {
            fwrite($channel, "go\n");
        }
        $deadline = hrtime(true) + 6_000_000_000;
        $blocked = [];
        $byMe = 0;
        while (hrtime(true) < $deadline && (count($blocked) < 2 || $byMe < 1)) {
            $blocked = [];
            $byMe = 0;
            foreach ($backends as $backend) {
                $row = DB::selectOne("SELECT a.wait_event_type, pg_blocking_pids(a.pid)::text AS blockers,
                    (SELECT string_agg(DISTINCT l.relation::regclass::text, ',') FROM pg_locks l WHERE l.pid = a.pid AND l.locktype IN ('tuple', 'relation') AND NOT l.granted) AS waiting_on,
                    (SELECT string_agg(DISTINCT l.relation::regclass::text, ',') FROM pg_locks l WHERE l.pid = a.pid AND l.locktype = 'tuple') AS tuple
                    FROM pg_stat_activity a WHERE a.pid = ?", [$backend]);
                $blockers = array_map('intval', array_filter(explode(',', trim((string) $row?->blockers, '{}'))));
                // Tuple-lock queueing: the first waiter is blocked by the parent; the second by the first waiter.
                if ($row?->wait_event_type === 'Lock' && $blockers !== [] && array_diff($blockers, [$me, ...array_values($backends)]) === []) {
                    $blocked[$backend] = ['blockers' => $blockers, 'tuple' => $row->tuple];
                    $byMe += in_array($me, $blockers, true) ? 1 : 0;
                }
            }
            usleep(5000);
        }
        expect(count($blocked))->toBe(2, json_encode($blocked))->and($byMe)->toBeGreaterThanOrEqual(1);
        foreach ($blocked as $wait) {
            expect($wait['tuple'])->toBe('business_profiles');
        }
        // Nothing past the Business row is held by either waiter.
        foreach (['business_campaigns', 'primary_reservations', 'investor_wallets'] as $table) {
            expect(DB::selectOne("SELECT count(*) AS n FROM pg_locks l WHERE l.pid = ANY(?::int[]) AND l.relation = ?::regclass AND l.mode IN ('RowShareLock') AND l.locktype = 'relation'",
                ['{'.implode(',', $backends).'}', $table])->n)->toBe(0, $table);
        }
        DB::commit();
        foreach ($children as $pid => $channel) {
            $results[] = json_decode((string) fgets($channel), true);
            pcntl_waitpid($pid, $status);
            expect(pcntl_wifexited($status) ? pcntl_wexitstatus($status) : -1)->toBe(0, json_encode($results));
        }
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        foreach ($children as $pid => $channel) {
            @fclose($channel);
            if (pcntl_waitpid($pid, $status, WNOHANG) === 0) {
                posix_kill($pid, SIGKILL);
                pcntl_waitpid($pid, $status);
            }
        }
    }
    $versions = PrimaryReservationVersion::query()->orderBy('revision')->get();
    $terminal = $versions->last();
    $cash = LedgerEntry::query()->whereIn('kind', ['primary_release', 'primary_commit'])->get();
    fwrite(STDERR, "[{$a}/{$b} expired=".json_encode($expired).' same='.json_encode($sameKey).'] '.json_encode($results).' terminal='.$terminal->state."\n");
    expect($versions)->toHaveCount(2)->and($cash)->toHaveCount(1)
        ->and($cash->sole()->kind)->toBe($terminal->state === 'confirmed' ? 'primary_commit' : 'primary_release')
        ->and(PrimaryCommitment::query()->count())->toBe($terminal->state === 'confirmed' ? 1 : 0)
        ->and(r175nHeld($investor))->toBe('0');
    if ($expired) {
        expect($terminal->state)->toBe('expired');
        $bound = $terminal->operation_id;
        if ($bound !== null) {
            expect(CommandOperation::query()->whereKey($bound)->sole()->result)->toMatchArray(['status' => 'rejected', 'code' => 'RESERVATION_EXPIRED']);
        }
    }
    $codes = array_column($results, 'code');
    if (! $expired && ! $sameKey && in_array($a, ['release', 'confirm'], true) && in_array($b, ['release', 'confirm'], true)) {
        sort($codes);
        expect($codes)->toContain('VERSION_CONFLICT');
    }
    if ($sameKey) {
        expect(CommandOperation::query()->where('command', 'primary.'.$a)->count())->toBe(1)->and($results[0]['op'])->toBe($results[1]['op']);
    }
    if (in_array('cancel', [$a, $b], true)) {
        expect(BusinessCampaignClosure::query()->count())->toBe(1)->and($codes)->toContain('CAMPAIGN_CANCELLED');
    }
})->with([
    'release vs release' => ['release', 'release', false, false],
    'release vs release same key' => ['release', 'release', false, true],
    'release vs confirm' => ['release', 'confirm', false, false],
    'release vs cancel' => ['release', 'cancel', false, false],
    'cancel vs release' => ['cancel', 'release', false, false],
    'expired release vs system expire' => ['release', 'expire', true, false],
    'system expire vs expired release' => ['expire', 'release', true, false],
    'expired confirm vs expired release' => ['confirm', 'release', true, false],
    'expired release vs release same key' => ['release', 'release', true, true],
    'expired confirm vs system expire' => ['confirm', 'expire', true, false],
    'expired release vs cancel' => ['release', 'cancel', true, false],
]);
