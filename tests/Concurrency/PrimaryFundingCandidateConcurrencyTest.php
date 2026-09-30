<?php

declare(strict_types=1);

use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Domain\Operations\CommandRejection;
use App\Models\LedgerEntry;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

it('requires an outer transaction before acquiring any funding candidate locks', function (): void {
    expect(fn () => app(PrimaryReservations::class)->lockFundingCandidate('campaign'))
        ->toThrow(CommandRejection::class, 'PRIMARY_TRANSACTION_REQUIRED');
});

it('retains all candidate locks until the callers real transaction ends', function (bool $afterDeadline): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $checkout = app(PrimaryCheckout::class);
    foreach ([1, 2] as $index) {
        $investor = PrimaryReservationFixture::investor();
        $result = $checkout->reserve($investor['user']->id, 1, $campaign->id, '1080', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
        $root = PrimaryReservationRecord::query()->whereKey($result['data']['reservation_id'])->sole();
        $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->sole();
        expect($checkout->confirm($investor['user']->id, 1, $campaign->id, $root->id, 1,
            $version->payload['terms']['disclosure_version'], $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...))['code'])->toBe('RESERVATION_CONFIRMED');
    }
    if ($afterDeadline) {
        $this->travelTo($campaign->expires_at->addDay());
    }
    config(['database.connections.funding_observer' => config('database.connections.pgsql')]);
    $observer = DB::connection('funding_observer');
    $targets = [['business_profiles', $campaign->business_id], ['business_campaigns', $campaign->id]];
    foreach (['primary_reservations', 'primary_commitments', 'investor_wallets'] as $table) {
        foreach (DB::table($table)->pluck('id') as $id) {
            $targets[] = [$table, $id];
        }
    }
    try {
        DB::beginTransaction();
        expect(app(PrimaryReservations::class)->lockFundingCandidate($campaign->id)->purchases)->toHaveCount(2);
        foreach ($targets as [$table, $id]) {
            expect(fn () => $observer->transaction(fn () => $observer->table($table)->where('id', $id)->lock('FOR UPDATE NOWAIT')->first()))
                ->toThrow(QueryException::class, 'could not obtain lock');
        }
        DB::rollBack();
        foreach ($targets as [$table, $id]) {
            expect($observer->transaction(fn () => $observer->table($table)->where('id', $id)->lock('FOR UPDATE NOWAIT')->first())?->id)->toBe($id);
        }
        expect(PrimaryCommitment::query()->count())->toBe(2)
            ->and(LedgerEntry::query()->where('kind', 'primary_commit')->count())->toBe(2);
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        DB::purge('funding_observer');
    }
})->with(['during publication' => false, 'after publication deadline' => true]);

it('reads the winning final confirmation only after its Business transaction ends', function (bool $commitConfirmation): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $checkout = app(PrimaryCheckout::class);
    foreach ([true, false] as $confirm) {
        $investor = PrimaryReservationFixture::investor();
        $result = $checkout->reserve($investor['user']->id, 1, $campaign->id, '1080', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
        $root = PrimaryReservationRecord::query()->whereKey($result['data']['reservation_id'])->sole();
        $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->sole();
        if ($confirm) {
            expect($checkout->confirm($investor['user']->id, 1, $campaign->id, $root->id, 1,
                $version->payload['terms']['disclosure_version'], $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...))['code'])->toBe('RESERVATION_CONFIRMED');
        }
    }
    DB::disconnect();
    $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    if ($channels === false) {
        throw new RuntimeException('Could not create final confirmation barrier.');
    }
    stream_set_timeout($channels[0], 10);
    stream_set_timeout($channels[1], 10);
    $pid = pcntl_fork();
    if ($pid === -1) {
        throw new RuntimeException('Could not fork funding candidate contender.');
    }
    if ($pid === 0) {
        fclose($channels[0]);
        DB::purge();
        try {
            DB::statement("SET lock_timeout = '8s'");
            fwrite($channels[1], DB::selectOne('SELECT pg_backend_pid() AS pid')->pid."\n");
            if (fgets($channels[1]) !== "go\n") {
                exit(3);
            }
            $candidate = DB::transaction(fn () => app(PrimaryReservations::class)->lockFundingCandidate($campaign->id));
            exit(count($candidate->purchases) === 2 && $candidate->principal === '10800000' ? 0 : 1);
        } catch (CommandRejection $exception) {
            exit($exception->reason === 'CAMPAIGN_NOT_FULLY_COMMITTED' ? 2 : 1);
        } catch (Throwable $exception) {
            fwrite(STDERR, $exception::class.' '.$exception->getMessage()."\n");
            exit(1);
        }
    }
    fclose($channels[1]);
    $childBackend = (int) trim((string) fgets($channels[0]));
    DB::purge();
    try {
        DB::beginTransaction();
        $parentBackend = DB::selectOne('SELECT pg_backend_pid() AS pid')->pid;
        expect($checkout->confirm($investor['user']->id, 1, $campaign->id, $root->id, 1,
            $version->payload['terms']['disclosure_version'], $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...))['code'])->toBe('RESERVATION_CONFIRMED');
        fwrite($channels[0], "go\n");
        $blocked = false;
        $waitingQuery = '';
        $deadline = microtime(true) + 5;
        while (microtime(true) < $deadline) {
            if ((bool) DB::selectOne('SELECT ? = ANY(pg_blocking_pids(?)) AS blocked', [$parentBackend, $childBackend])->blocked) {
                $blocked = true;
                $waitingQuery = DB::selectOne('SELECT query FROM pg_stat_activity WHERE pid = ?', [$childBackend])->query;
                break;
            }
            usleep(10000);
        }
        if ($commitConfirmation) {
            DB::commit();
        } else {
            DB::rollBack();
        }
        pcntl_waitpid($pid, $status);
        expect($blocked)->toBeTrue()->and($waitingQuery)->toContain('"business_profiles"')
            ->and(pcntl_wifexited($status))->toBeTrue()->and(pcntl_wexitstatus($status))->toBe($commitConfirmation ? 0 : 2);
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        fclose($channels[0]);
        pcntl_waitpid($pid, $status);
        DB::purge();
    }
    expect(PrimaryCommitment::query()->count())->toBe($commitConfirmation ? 2 : 1)
        ->and(LedgerEntry::query()->where('kind', 'primary_commit')->count())->toBe($commitConfirmation ? 2 : 1)
        ->and(LedgerEntry::query()->where('kind', 'primary_hold')->count())->toBe(2);
})->with(['confirmation commits' => true, 'confirmation rolls back' => false]);
