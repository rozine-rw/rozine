<?php

declare(strict_types=1);

use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Models\InvestorWallet;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

it('requires the authorized outer transaction before either private persistence read', function (): void {
    $reads = app(PrimaryReservations::class);
    expect(fn () => $reads->reservationFacts('campaign', 'party', 'reservation'))->toThrow(CommandRejection::class, 'PRIMARY_TRANSACTION_REQUIRED')
        ->and(fn () => $reads->commitmentFacts('campaign', 'party', 'commitment'))->toThrow(CommandRejection::class, 'PRIMARY_TRANSACTION_REQUIRED');
});

it('retains current authority and coherent purchase history through outer commit or rollback without wallet gates', function (string $kind, bool $rollback): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $investor = PrimaryReservationFixture::investor();
    $checkout = app(PrimaryCheckout::class);
    $held = $checkout->reserve($investor['user']->id, 1, $campaign->id, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $root = PrimaryReservationRecord::query()->sole();
    $targets = ['business_profiles' => $campaign->business_id, 'users' => $investor['user']->id, 'parties' => $investor['party']->id,
        'primary_reservations' => $root->id];
    $target = $held['data']['reservation_id'];
    if ($kind === 'commitment') {
        $facts = $checkout->reservationFacts($investor['user']->id, 1, $campaign->id, $target);
        $confirmed = $checkout->confirm($investor['user']->id, 1, $campaign->id, $target, 1,
            $facts['terms']['disclosure_version'], $facts['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...));
        $target = $confirmed['data']['commitment_id'];
        $targets['primary_commitments'] = $target;
    }
    config(['database.connections.primary_read_observer' => config('database.connections.pgsql')]);
    $observer = DB::connection('primary_read_observer');
    try {
        DB::beginTransaction();
        $read = $kind === 'commitment'
            ? $checkout->commitmentFacts($investor['user']->id, 1, $campaign->id, $target)
            : $checkout->reservationFacts($investor['user']->id, 1, $campaign->id, $target);
        expect($read['state'])->toBe($kind === 'commitment' ? 'confirmed' : 'held');
        foreach ($targets as $table => $id) {
            expect(fn () => $observer->transaction(fn () => $observer->table($table)->where('id', $id)->lock('FOR UPDATE NOWAIT')->first()))
                ->toThrow(QueryException::class, 'could not obtain lock');
        }
        $walletId = InvestorWallet::query()->where('party_id', $investor['party']->id)->value('id');
        expect($observer->transaction(fn () => $observer->table('investor_wallets')->where('id', $walletId)->lock('FOR UPDATE NOWAIT')->first())?->id)->toBe($walletId);
        $rollback ? DB::rollBack() : DB::commit();
        foreach ($targets as $table => $id) {
            expect($observer->transaction(fn () => $observer->table($table)->where('id', $id)->lock('FOR UPDATE NOWAIT')->first())?->id)->toBe($id);
        }
        expect(PrimaryReservationRecord::query()->count())->toBe(1)->and(PrimaryCommitment::query()->count())->toBe($kind === 'commitment' ? 1 : 0);
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        DB::purge('primary_read_observer');
    }
})->with([['reservation', false], ['reservation', true], ['commitment', false], ['commitment', true]]);

it('waits on Business before acquiring current identity and observes a committed context change', function (): void {
    $campaign = PrimaryReservationFixture::campaign();
    $investor = InvestorWalletFixture::investor();
    $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    if ($channels === false) {
        throw new RuntimeException('Could not create retained read lock barrier.');
    }
    stream_set_timeout($channels[0], 8);
    stream_set_timeout($channels[1], 8);
    DB::disconnect();
    $pid = pcntl_fork();
    if ($pid === -1) {
        throw new RuntimeException('Could not fork the retained reader.');
    }
    if ($pid === 0) {
        fclose($channels[0]);
        DB::purge();
        try {
            DB::statement("SET lock_timeout = '5s'");
            fwrite($channels[1], DB::selectOne('SELECT pg_backend_pid() AS pid')->pid."\n");
            if (fgets($channels[1]) !== "go\n") {
                exit(3);
            }
            app(PrimaryCheckout::class)->reservationFacts($investor['user']->id, 1, $campaign->id, strtolower((string) Str::ulid()));
            exit(4);
        } catch (IdentityViolation $exception) {
            exit($exception->getMessage() === 'ACTIVE_ROLE_REVISION_CONFLICT' ? 0 : 5);
        } catch (Throwable) {
            exit(6);
        }
    }
    fclose($channels[1]);
    $childStatus = null;
    try {
        $backend = trim((string) fgets($channels[0]));
        expect(ctype_digit($backend))->toBeTrue();
        DB::beginTransaction();
        DB::statement("SET LOCAL lock_timeout = '1s'");
        DB::table('business_profiles')->where('id', $campaign->business_id)->lock('FOR NO KEY UPDATE')->first();
        fwrite($channels[0], "go\n");
        $deadline = hrtime(true) + 3_000_000_000;
        $blocked = false;
        while (hrtime(true) < $deadline) {
            $blocked = DB::selectOne('SELECT pg_backend_pid() = ANY(pg_blocking_pids(?)) AS blocked', [$backend])->blocked;
            if ($blocked) {
                break;
            }
            if (pcntl_waitpid($pid, $status, WNOHANG) === $pid) {
                $childStatus = $status;
                break;
            }
            usleep(10000);
        }
        expect($blocked)->toBeTrue('Retained read must acquire Business before current actor/Party authority.');
        expect(DB::table('users')->where('id', $investor['user']->id)->lock('FOR UPDATE NOWAIT')->first()?->id)->toBe($investor['user']->id)
            ->and(DB::table('parties')->where('id', $investor['party']->id)->lock('FOR UPDATE NOWAIT')->first()?->id)->toBe($investor['party']->id);
        DB::table('users')->where('id', $investor['user']->id)->update(['context_revision' => 2]);
        DB::commit();
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        if ($childStatus === null) {
            pcntl_waitpid($pid, $childStatus);
        }
        fclose($channels[0]);
    }
    expect(pcntl_wifexited($childStatus))->toBeTrue()->and(pcntl_wexitstatus($childStatus))->toBe(0)
        ->and(PrimaryReservationRecord::query()->count())->toBe(0)->and(PrimaryCommitment::query()->count())->toBe(0);
});

it('reads a complete confirmation only after the actual writer outer commit and retains held history after rollback', function (bool $rollback): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $investor = PrimaryReservationFixture::investor();
    $checkout = app(PrimaryCheckout::class);
    $held = $checkout->reserve($investor['user']->id, 1, $campaign->id, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $reservationId = $held['data']['reservation_id'];
    $facts = $checkout->reservationFacts($investor['user']->id, 1, $campaign->id, $reservationId);
    $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    if ($channels === false) {
        throw new RuntimeException('Could not create confirmation read barrier.');
    }
    stream_set_timeout($channels[0], 8);
    stream_set_timeout($channels[1], 8);
    DB::disconnect();
    $pid = pcntl_fork();
    if ($pid === -1) {
        throw new RuntimeException('Could not fork the confirmation reader.');
    }
    if ($pid === 0) {
        fclose($channels[0]);
        DB::purge();
        try {
            DB::statement("SET lock_timeout = '5s'");
            fwrite($channels[1], DB::selectOne('SELECT pg_backend_pid() AS pid')->pid."\n");
            if (fgets($channels[1]) !== "go\n") {
                exit(3);
            }
            $read = app(PrimaryCheckout::class)->reservationFacts($investor['user']->id, 1, $campaign->id, $reservationId);
            fwrite($channels[1], json_encode(['state' => $read['state'], 'revision' => $read['revision'], 'confirmed' => $read['confirmation'] !== null], JSON_THROW_ON_ERROR)."\n");
            exit(0);
        } catch (Throwable) {
            exit(6);
        }
    }
    fclose($channels[1]);
    $childStatus = null;
    try {
        $backend = trim((string) fgets($channels[0]));
        expect(ctype_digit($backend))->toBeTrue();
        DB::beginTransaction();
        $confirmed = $checkout->confirm($investor['user']->id, 1, $campaign->id, $reservationId, 1,
            $facts['terms']['disclosure_version'], $facts['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...));
        expect($confirmed['code'])->toBe('RESERVATION_CONFIRMED');
        fwrite($channels[0], "go\n");
        $deadline = hrtime(true) + 3_000_000_000;
        $blocked = false;
        while (hrtime(true) < $deadline) {
            $blocked = DB::selectOne('SELECT pg_backend_pid() = ANY(pg_blocking_pids(?)) AS blocked', [$backend])->blocked;
            if ($blocked) {
                break;
            }
            if (pcntl_waitpid($pid, $status, WNOHANG) === $pid) {
                $childStatus = $status;
                break;
            }
            usleep(10000);
        }
        expect($blocked)->toBeTrue('The reader must not disclose an uncommitted confirmation.');
        $rollback ? DB::rollBack() : DB::commit();
        $observed = json_decode(trim((string) fgets($channels[0])), true, flags: JSON_THROW_ON_ERROR);
        expect($observed)->toBe(['state' => $rollback ? 'held' : 'confirmed', 'revision' => $rollback ? 1 : 2, 'confirmed' => ! $rollback]);
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        if ($childStatus === null) {
            pcntl_waitpid($pid, $childStatus);
        }
        fclose($channels[0]);
    }
    expect(pcntl_wifexited($childStatus))->toBeTrue()->and(pcntl_wexitstatus($childStatus))->toBe(0)
        ->and(PrimaryCommitment::query()->count())->toBe($rollback ? 0 : 1);
})->with([false, true]);
