<?php

declare(strict_types=1);

use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Models\LedgerEntry;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

it('queues receipt guard migration behind an actual refund without taking its later write locks', function (bool $install): void {
    $this->freezeSecond();
    $migration = require database_path('migrations/2026_09_30_120729_bind_primary_refund_receipts_to_returned_cash.php');
    if ($install) {
        $migration->down();
    }
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $investor = PrimaryReservationFixture::investor();
    $checkout = app(PrimaryCheckout::class);
    $reserved = $checkout->reserve($investor['user']->id, 1, $campaign->id, '3', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $root = PrimaryReservationRecord::query()->whereKey($reserved['data']['reservation_id'])->sole();
    $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->sole();
    $checkout->confirm($investor['user']->id, 1, $campaign->id, $root->id, 1,
        $version->payload['terms']['disclosure_version'], $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    if ($channels === false) {
        throw new RuntimeException('Could not open receipt migration barrier.');
    }
    stream_set_timeout($channels[0], 10);
    stream_set_timeout($channels[1], 10);
    DB::disconnect();
    $pid = pcntl_fork();
    if ($pid === -1) {
        fclose($channels[0]);
        fclose($channels[1]);
        if ($install) {
            $migration->up();
        }
        throw new RuntimeException('Could not fork receipt migration.');
    }
    if ($pid === 0) {
        fclose($channels[0]);
        DB::purge();
        try {
            DB::statement("SET lock_timeout = '8s'");
            DB::statement("SET statement_timeout = '12s'");
            fwrite($channels[1], DB::scalar('SELECT pg_backend_pid()')."\n");
            if (fgets($channels[1]) !== "go\n") {
                exit(3);
            }
            $migration->{$install ? 'up' : 'down'}();
            exit($install ? 0 : 2);
        } catch (QueryException $exception) {
            exit(! $install && str_contains($exception->getMessage(), 'Retained Primary refund receipts require a forward migration') ? 0 : 1);
        } catch (Throwable) {
            exit(1);
        }
    }
    fclose($channels[1]);
    $reaped = false;
    $event = 'eloquent.created: '.LedgerEntry::class;
    try {
        $backend = (int) trim((string) fgets($channels[0]));
        expect($backend)->toBeGreaterThan(0);
        Event::listen($event, function (LedgerEntry $entry) use ($channels, $backend): void {
            if ($entry->kind !== 'primary_refund') {
                return;
            }
            fwrite($channels[0], "go\n");
            $blocked = false;
            $deadline = hrtime(true) + 4_000_000_000;
            while (hrtime(true) < $deadline && ! $blocked) {
                $blocked = (bool) DB::scalar('SELECT pg_backend_pid() = ANY(pg_blocking_pids(?))', [$backend]);
                if (! $blocked) {
                    usleep(10_000);
                }
            }
            expect($blocked)->toBeTrue()
                ->and(DB::scalar("SELECT EXISTS (SELECT 1 FROM pg_locks WHERE pid = ? AND granted AND relation = 'command_operations'::regclass)", [$backend]))->toBeFalse();
        });
        expect($checkout->refund($investor['user']->id, 1, $campaign->id, $root->id, 2, (string) Str::uuid())['code'])->toBe('COMMITMENT_REFUNDED');
        $deadline = hrtime(true) + 10_000_000_000;
        do {
            $reaped = pcntl_waitpid($pid, $status, WNOHANG) === $pid;
            if (! $reaped) {
                usleep(10_000);
            }
        } while (! $reaped && hrtime(true) < $deadline);
        expect($reaped)->toBeTrue()->and(pcntl_wifexited($status))->toBeTrue()->and(pcntl_wexitstatus($status))->toBe(0);
    } finally {
        Event::forget($event);
        fclose($channels[0]);
        if (! $reaped) {
            posix_kill($pid, SIGKILL);
            pcntl_waitpid($pid, $status);
        }
        DB::purge();
        if (! DB::scalar("SELECT EXISTS (SELECT 1 FROM pg_trigger WHERE tgname = 'primary_refund_receipt_bound')")) {
            $migration->up();
        }
    }
    expect(LedgerEntry::query()->where('kind', 'primary_refund')->count())->toBe(1);
})->with(['install' => true, 'rollback refuses retained receipts' => false]);
