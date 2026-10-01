<?php

declare(strict_types=1);

use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryFunding;
use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\PostingSource;
use App\Domain\Operations\CommandRejection;
use App\Domain\Wallet\WalletMoney;
use App\Models\BusinessCampaign;
use App\Models\LedgerEntry;
use App\Models\PrimaryCampaignFunding;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

/** @return array{campaign: BusinessCampaign, admission: array<string, mixed>} */
function durableFundingFixture(): array
{
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

    return ['campaign' => $campaign, 'admission' => ['campaign_id' => $campaign->id, 'publication_sha256' => $campaign->sha256,
        ...array_fill_keys(['eligibility', 'policy', 'connections', 'destination'], ['status' => 'passed', 'evidence' => ['synthetic' => 'Isolated concurrency fixture, not live admission.']])]];
}

it('requires the caller transaction before attempting funding admission', function (): void {
    expect(fn () => app(PrimaryFunding::class)->lock('campaign', function (): array {
        throw new RuntimeException('Admission must not run.');
    }))->toThrow(CommandRejection::class, 'PRIMARY_TRANSACTION_REQUIRED');
});

it('retains the real funding lock chain through the outer transaction after deadline', function (): void {
    $this->freezeSecond();
    ['campaign' => $campaign, 'admission' => $admission] = durableFundingFixture();
    $this->travelTo($campaign->expires_at->addDay());
    config(['database.connections.funding_watcher' => config('database.connections.pgsql')]);
    $observer = DB::connection('funding_watcher');
    $targets = [['business_profiles', $campaign->business_id], ['business_campaigns', $campaign->id]];
    foreach (['primary_reservations', 'primary_commitments', 'investor_wallets'] as $table) {
        foreach (DB::table($table)->pluck('id') as $id) {
            $targets[] = [$table, $id];
        }
    }
    try {
        DB::beginTransaction();
        app(PrimaryFunding::class)->lock($campaign->id, fn (): array => $admission);
        foreach ($targets as [$table, $id]) {
            expect(fn () => $observer->transaction(fn () => $observer->table($table)->where('id', $id)->lock('FOR UPDATE NOWAIT')->first()))
                ->toThrow(QueryException::class, 'could not obtain lock');
        }
        DB::rollBack();
        foreach ($targets as [$table, $id]) {
            expect($observer->transaction(fn () => $observer->table($table)->where('id', $id)->lock('FOR UPDATE NOWAIT')->first())?->id)->toBe($id);
        }
        expect(PrimaryCampaignFunding::query()->count())->toBe(0)->and(DB::table('primary_funding_commitments')->count())->toBe(0)
            ->and(LedgerEntry::query()->where('kind', 'primary_commit')->count())->toBe(2);
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        DB::purge('funding_watcher');
    }
});

it('serializes simultaneous funding on the Business gate across commit or rollback', function (bool $commitFirst): void {
    $this->freezeSecond();
    ['campaign' => $campaign, 'admission' => $admission] = durableFundingFixture();
    DB::disconnect();
    $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    if ($channels === false) {
        throw new RuntimeException('Could not create funding barrier.');
    }
    stream_set_timeout($channels[0], 10);
    stream_set_timeout($channels[1], 10);
    $pid = pcntl_fork();
    if ($pid === -1) {
        throw new RuntimeException('Could not fork funding contender.');
    }
    if ($pid === 0) {
        fclose($channels[0]);
        DB::purge();
        try {
            DB::statement("SET lock_timeout = '8s'");
            fwrite($channels[1], DB::scalar('SELECT pg_backend_pid()')."\n");
            if (fgets($channels[1]) !== "go\n") {
                exit(3);
            }
            $funding = DB::transaction(fn () => app(PrimaryFunding::class)->lock($campaign->id, fn (): array => $admission));
            fwrite($channels[1], $funding['funding_id']."\n");
            exit(0);
        } catch (Throwable $exception) {
            fwrite(STDERR, $exception::class.' '.$exception->getMessage()."\n");
            exit(1);
        }
    }
    fclose($channels[1]);
    $waiter = (int) trim((string) fgets($channels[0]));
    DB::purge();
    try {
        DB::beginTransaction();
        $holder = DB::scalar('SELECT pg_backend_pid()');
        $first = app(PrimaryFunding::class)->lock($campaign->id, fn (): array => $admission);
        fwrite($channels[0], "go\n");
        $blocked = false;
        $waitingQuery = '';
        $deadline = microtime(true) + 5;
        while (microtime(true) < $deadline) {
            if ((bool) DB::scalar('SELECT ?::int = ANY(pg_blocking_pids(?::int))', [$holder, $waiter])) {
                $blocked = true;
                $waitingQuery = DB::scalar('SELECT query FROM pg_stat_activity WHERE pid = ?', [$waiter]);
                break;
            }
            usleep(10_000);
        }
        if ($commitFirst) {
            DB::commit();
        } else {
            DB::rollBack();
        }
        pcntl_waitpid($pid, $status);
        $winningId = trim((string) fgets($channels[0]));
        expect($blocked)->toBeTrue()->and($waitingQuery)->toContain('"business_profiles"')
            ->and(pcntl_wifexited($status))->toBeTrue()->and(pcntl_wexitstatus($status))->toBe(0)
            ->and(PrimaryCampaignFunding::query()->sole()->id)->toBe($winningId)
            ->and($winningId === $first['funding_id'])->toBe($commitFirst)
            ->and(DB::table('primary_funding_commitments')->count())->toBe(2)
            ->and(LedgerEntry::query()->where('kind', 'primary_commit')->count())->toBe(2);
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        fclose($channels[0]);
        pcntl_waitpid($pid, $status);
        DB::purge();
    }
})->with(['first funding commits' => true, 'first funding rolls back' => false]);

it('serializes funding against original principal refund at the wallet gate', function (bool $fundingFirst): void {
    $this->freezeSecond();
    ['campaign' => $campaign, 'admission' => $admission] = durableFundingFixture();
    $root = PrimaryReservationRecord::query()->orderBy('id')->firstOrFail();
    $refund = function () use ($root): void {
        $wallets = app(WalletPostings::class);
        $wallets->refund($wallets->lockForParty($root->party_id), WalletMoney::of($root->principal),
            new PostingSource('primary_reservation', $root->id, $root->origin_operation_id));
    };
    DB::disconnect();
    $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    if ($channels === false) {
        throw new RuntimeException('Could not create funding refund barrier.');
    }
    stream_set_timeout($channels[0], 10);
    stream_set_timeout($channels[1], 10);
    $pid = pcntl_fork();
    if ($pid === -1) {
        throw new RuntimeException('Could not fork funding refund contender.');
    }
    if ($pid === 0) {
        fclose($channels[0]);
        DB::purge();
        try {
            DB::statement("SET lock_timeout = '8s'");
            fwrite($channels[1], DB::scalar('SELECT pg_backend_pid()')."\n");
            if (fgets($channels[1]) !== "go\n") {
                exit(3);
            }
            DB::transaction(function () use ($fundingFirst, $refund, $campaign, $admission): void {
                if ($fundingFirst) {
                    $refund();
                } else {
                    app(PrimaryFunding::class)->lock($campaign->id, fn (): array => $admission);
                }
            });
            exit(4);
        } catch (Throwable $exception) {
            $expected = $fundingFirst
                ? $exception instanceof QueryException && str_contains($exception->getMessage(), 'authoritative failed closing')
                : $exception instanceof CommandRejection && $exception->reason === 'CAMPAIGN_NOT_FULLY_COMMITTED';
            fwrite($channels[1], $expected ? "refused\n" : $exception::class."\n");
            exit($expected ? 0 : 1);
        }
    }
    fclose($channels[1]);
    $waiter = (int) trim((string) fgets($channels[0]));
    DB::purge();
    try {
        DB::beginTransaction();
        $holder = DB::scalar('SELECT pg_backend_pid()');
        if ($fundingFirst) {
            app(PrimaryFunding::class)->lock($campaign->id, fn (): array => $admission);
        } else {
            $refund();
        }
        fwrite($channels[0], "go\n");
        $blocked = false;
        $query = '';
        $deadline = microtime(true) + 5;
        while (microtime(true) < $deadline) {
            if ((bool) DB::scalar('SELECT ?::int = ANY(pg_blocking_pids(?::int))', [$holder, $waiter])) {
                $blocked = true;
                $query = DB::scalar('SELECT query FROM pg_stat_activity WHERE pid = ?', [$waiter]);
                break;
            }
            usleep(10_000);
        }
        DB::commit();
        pcntl_waitpid($pid, $status);
        expect($blocked)->toBeTrue()->and($query)->toContain('investor_wallets')
            ->and(pcntl_wifexited($status))->toBeTrue()->and(pcntl_wexitstatus($status))->toBe(0)
            ->and(fgets($channels[0]))->toBe("refused\n")
            ->and(PrimaryCampaignFunding::query()->count())->toBe($fundingFirst ? 1 : 0)
            ->and(DB::table('primary_funding_commitments')->count())->toBe($fundingFirst ? 2 : 0)
            ->and(LedgerEntry::query()->where('kind', 'primary_refund')->count())->toBe($fundingFirst ? 0 : 1);
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        fclose($channels[0]);
        pcntl_waitpid($pid, $status);
        DB::purge();
    }
})->with(['funding wins the wallet gate' => true, 'refund wins the wallet gate' => false]);
