<?php

declare(strict_types=1);

use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Domain\Operations\CommandRejection;
use App\Domain\Wallet\WalletViolation;
use App\Models\BusinessCampaign;
use App\Models\CommandOperation;
use App\Models\LedgerEntry;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

it('requires an outer transaction before acquiring any campaign return locks', function (): void {
    expect(fn () => app(PrimaryReservations::class)->lockReturnedCampaign('campaign'))
        ->toThrow(CommandRejection::class, 'PRIMARY_TRANSACTION_REQUIRED');
});

it('requires READ COMMITTED before taking Business or wallet locks even on empty inventory', function (string $isolation): void {
    DB::beginTransaction();
    try {
        DB::statement('SET TRANSACTION ISOLATION LEVEL '.$isolation);
        expect(fn () => app(PrimaryReservations::class)->lockReturnedCampaign('campaign'))
            ->toThrow(WalletViolation::class, 'PRIMARY_CASH_ISOLATION_REQUIRED');
    } finally {
        DB::rollBack();
    }
})->with(['REPEATABLE READ', 'SERIALIZABLE']);

it('retains every returned campaign lock until the callers real transaction ends', function (bool $afterDeadline): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $checkout = app(PrimaryCheckout::class);
    foreach (['refunded', 'released'] as $state) {
        $investor = PrimaryReservationFixture::investor();
        $result = $checkout->reserve($investor['user']->id, 1, $campaign->id, '3', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
        $root = PrimaryReservationRecord::query()->whereKey($result['data']['reservation_id'])->sole();
        $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->sole();
        if ($state === 'refunded') {
            expect($checkout->confirm($investor['user']->id, 1, $campaign->id, $root->id, 1,
                $version->payload['terms']['disclosure_version'], $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...))['code'])->toBe('RESERVATION_CONFIRMED');
            expect($checkout->refund($investor['user']->id, 1, $campaign->id, $root->id, 2, (string) Str::uuid())['code'])->toBe('COMMITMENT_REFUNDED');
        } else {
            expect($checkout->release($investor['user']->id, 1, $campaign->id, $root->id, 1, (string) Str::uuid())['code'])->toBe('RESERVATION_RELEASED');
        }
    }
    if ($afterDeadline) {
        $this->travelTo($campaign->expires_at->addDay());
    }
    config(['database.connections.returns_observer' => config('database.connections.pgsql')]);
    $observer = DB::connection('returns_observer');
    $targets = [['business_profiles', $campaign->business_id], ['business_campaigns', $campaign->id]];
    foreach (['primary_reservations', 'primary_commitments', 'investor_wallets'] as $table) {
        foreach (DB::table($table)->pluck('id') as $id) {
            $targets[] = [$table, $id];
        }
    }
    try {
        DB::beginTransaction();
        expect(app(PrimaryReservations::class)->lockReturnedCampaign($campaign->id)->returns)->toHaveCount(2);
        foreach ($targets as [$table, $id]) {
            expect(fn () => $observer->transaction(fn () => $observer->table($table)->where('id', $id)->lock('FOR UPDATE NOWAIT')->first()))
                ->toThrow(QueryException::class, 'could not obtain lock');
        }
        DB::rollBack();
        foreach ($targets as [$table, $id]) {
            expect($observer->transaction(fn () => $observer->table($table)->where('id', $id)->lock('FOR UPDATE NOWAIT')->first())?->id)->toBe($id);
        }
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        DB::purge('returns_observer');
    }
})->with(['during publication' => false, 'after publication deadline' => true]);

/** @return array{campaign: BusinessCampaign, investorId: int, reservationId: string} */
function campaignReturnRaceFixture(): array
{
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $checkout = app(PrimaryCheckout::class);
    $purchases = [];
    foreach ([1] as $index) {
        $investor = PrimaryReservationFixture::investor();
        $result = $checkout->reserve($investor['user']->id, 1, $campaign->id, '3', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
        $root = PrimaryReservationRecord::query()->whereKey($result['data']['reservation_id'])->sole();
        $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->sole();
        $checkout->confirm($investor['user']->id, 1, $campaign->id, $root->id, 1, $version->payload['terms']['disclosure_version'],
            $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...));
        $purchases[] = ['user' => $investor['user']->id, 'root' => $root->id];
    }

    return ['campaign' => $campaign, 'investorId' => $purchases[0]['user'], 'reservationId' => $purchases[0]['root']];
}

it('observes a completed or rolled-back refund only after the Business gate is released', function (bool $commitRefund): void {
    $this->freezeSecond();
    ['campaign' => $campaign, 'investorId' => $investorId, 'reservationId' => $reservationId] = campaignReturnRaceFixture();
    DB::disconnect();
    $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    if ($channels === false) {
        throw new RuntimeException('Could not create refund command barrier.');
    }
    stream_set_timeout($channels[0], 10);
    stream_set_timeout($channels[1], 10);
    $pid = pcntl_fork();
    if ($pid === -1) {
        fclose($channels[0]);
        fclose($channels[1]);
        throw new RuntimeException('Could not fork refund command contender.');
    }
    if ($pid === 0) {
        fclose($channels[0]);
        DB::purge();
        $code = 1;
        try {
            DB::statement("SET lock_timeout = '8s'");
            fwrite($channels[1], DB::selectOne('SELECT pg_backend_pid() AS pid')->pid."\n");
            if (fgets($channels[1]) !== "go\n") {
                throw new RuntimeException('Refund command barrier timed out.');
            }
            $proof = DB::transaction(fn () => app(PrimaryReservations::class)->lockReturnedCampaign($campaign->id));
            if (! $commitRefund || $proof->refundedPrincipal !== '15000' || count($proof->returns) !== 1) {
                throw new RuntimeException('The reader did not observe the committed return.');
            }
            $code = 0;
        } catch (WalletViolation $exception) {
            $code = ! $commitRefund && $exception->reason === 'PRIMARY_RETURNED_CASH_REQUIRED' ? 0 : 1;
        } catch (Throwable $exception) {
            fwrite(STDERR, $exception::class.' '.$exception->getCode()."\n");
        } finally {
            fclose($channels[1]);
            DB::purge();
        }
        exit($code);
    }
    fclose($channels[1]);
    $reaped = false;
    DB::purge();
    try {
        $childBackend = (int) trim((string) fgets($channels[0]));
        if ($childBackend < 1) {
            throw new RuntimeException('Refund command contender did not report its backend.');
        }
        DB::beginTransaction();
        $parentBackend = DB::selectOne('SELECT pg_backend_pid() AS pid')->pid;
        expect(app(PrimaryCheckout::class)->refund($investorId, 1, $campaign->id, $reservationId, 2, (string) Str::uuid())['code'])
            ->toBe('COMMITMENT_REFUNDED');
        fwrite($channels[0], "go\n");
        $blocked = false;
        $blockingQuery = '';
        $deadline = microtime(true) + 5;
        while (microtime(true) < $deadline) {
            if ((bool) DB::selectOne('SELECT ? = ANY(pg_blocking_pids(?)) AS blocked', [$parentBackend, $childBackend])->blocked) {
                $blocked = true;
                $blockingQuery = DB::scalar('SELECT query FROM pg_stat_activity WHERE pid = ?', [$childBackend]);
                break;
            }
            usleep(10000);
        }
        if ($commitRefund) {
            DB::commit();
        } else {
            DB::rollBack();
        }
        $deadline = microtime(true) + 10;
        do {
            $reaped = pcntl_waitpid($pid, $status, WNOHANG) === $pid;
            if (! $reaped) {
                usleep(10000);
            }
        } while (! $reaped && microtime(true) < $deadline);
        if (! $reaped) {
            throw new RuntimeException('Refund command contender did not finish.');
        }
        expect($blocked)->toBeTrue()->and($blockingQuery)->toContain('business_profiles')->and(pcntl_wifexited($status))->toBeTrue()
            ->and(pcntl_wexitstatus($status))->toBe(0);
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        fclose($channels[0]);
        if (! $reaped) {
            posix_kill($pid, SIGKILL);
            pcntl_waitpid($pid, $status);
        }
        DB::purge();
    }
    expect(LedgerEntry::query()->where('kind', 'primary_refund')->count())->toBe($commitRefund ? 1 : 0)
        ->and(CommandOperation::query()->where('command', 'primary.refund')->count())->toBe($commitRefund ? 1 : 0);
})->with(['refund commits' => true, 'refund rolls back' => false]);
