<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Domain\Operations\CommandRejection;
use App\Domain\Wallet\WalletViolation;
use App\Models\BusinessCampaignClosure;
use App\Models\LedgerEntry;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

it('refuses an outer commit without its system closure and persists valid expiry returns after reconnect', function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $investor = PrimaryReservationFixture::investor();
    $checkout = app(PrimaryCheckout::class);
    $reserved = $checkout->reserve($investor['user']->id, 1, $campaign->id, '3', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $root = PrimaryReservationRecord::query()->whereKey($reserved['data']['reservation_id'])->sole();
    $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->sole();
    $checkout->confirm($investor['user']->id, 1, $campaign->id, $root->id, 1,
        $version->payload['terms']['disclosure_version'], $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    expect(DB::transactionLevel())->toBe(0)
        ->and(fn () => app(PrimaryReservations::class)->settleExpiredCampaign($campaign->id, strtolower((string) Str::ulid())))
        ->toThrow(CommandRejection::class, 'PRIMARY_TRANSACTION_REQUIRED');
    $this->travelTo($campaign->expires_at);
    expect(fn () => DB::transaction(function () use ($campaign, $root, $investor): void {
        $subjects = app(PrimaryReservations::class)->settleExpiredCampaign($campaign->id, strtolower((string) Str::ulid()));
        expect($subjects)->toBe([['party_id' => $investor['party']->id, 'reservation_id' => $root->id]]);
        expect(LedgerEntry::query()->where('kind', 'primary_refund')->count())->toBe(1);
    }))->toThrow(PDOException::class);
    expect(DB::transactionLevel())->toBe(0);
    DB::purge();
    expect(LedgerEntry::query()->where('kind', 'primary_refund')->count())->toBe(0)
        ->and(DB::table('primary_campaign_expiry_settlements')->count())->toBe(0);
    expect(app(BusinessCampaignStore::class)->expireDue(1))->toBe(1);
    DB::purge();
    expect(LedgerEntry::query()->where('kind', 'primary_refund')->count())->toBe(1)
        ->and(BusinessCampaignClosure::query()->sole()->phase)->toBe('expired')
        ->and(DB::table('primary_campaign_expiry_settlements')->sole()->business_campaign_closure_id)
        ->toBe(BusinessCampaignClosure::query()->sole()->id)
        ->and(app(BusinessCampaignStore::class)->expireDue(1))->toBe(0);
});

it('rejects system expiry cash under snapshot isolation without retaining a cause', function (): void {
    $this->freezeSecond();
    $campaign = PrimaryReservationFixture::campaign();
    $this->travelTo($campaign->expires_at);
    expect(fn () => DB::transaction(function () use ($campaign): void {
        DB::statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        app(PrimaryReservations::class)->settleExpiredCampaign($campaign->id, strtolower((string) Str::ulid()));
    }))->toThrow(WalletViolation::class, 'PRIMARY_CASH_ISOLATION_REQUIRED')
        ->and(DB::table('primary_campaign_expiry_settlements')->count())->toBe(0);
});

it('makes rollback wait before the cause table and refuse after an actual expiry refund commits', function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $investor = PrimaryReservationFixture::investor();
    $checkout = app(PrimaryCheckout::class);
    $reserved = $checkout->reserve($investor['user']->id, 1, $campaign->id, '3', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $root = PrimaryReservationRecord::query()->whereKey($reserved['data']['reservation_id'])->sole();
    $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->sole();
    $checkout->confirm($investor['user']->id, 1, $campaign->id, $root->id, 1,
        $version->payload['terms']['disclosure_version'], $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $this->travelTo($campaign->expires_at);
    $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    if ($channels === false) {
        throw new RuntimeException('Could not open expiry rollback barrier.');
    }
    stream_set_timeout($channels[0], 10);
    stream_set_timeout($channels[1], 10);
    DB::purge();
    $pid = pcntl_fork();
    if ($pid === -1) {
        fclose($channels[0]);
        fclose($channels[1]);
        throw new RuntimeException('Could not fork expiry rollback.');
    }
    if ($pid === 0) {
        fclose($channels[0]);
        DB::purge();
        DB::statement("SET lock_timeout = '8s'");
        fwrite($channels[1], DB::scalar('SELECT pg_backend_pid()')."\n");
        if (fgets($channels[1]) !== "go\n") {
            exit(2);
        }
        try {
            $migration = require database_path('migrations/2026_09_30_204213_create_primary_campaign_expiry_settlements_table.php');
            $migration->down();
            exit(1);
        } catch (RuntimeException $exception) {
            exit(str_contains($exception->getMessage(), 'forward migration') ? 0 : 2);
        }
    }
    fclose($channels[1]);
    $reaped = false;
    $status = 0;
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
            while (! $blocked && hrtime(true) < $deadline) {
                $blocked = (bool) DB::scalar('SELECT pg_backend_pid() = ANY(pg_blocking_pids(?))', [$backend]);
                if (! $blocked) {
                    usleep(10_000);
                }
            }
            expect($blocked)->toBeTrue()
                ->and(DB::scalar("SELECT EXISTS (SELECT 1 FROM pg_locks WHERE pid = ? AND NOT granted
                    AND relation = 'business_campaigns'::regclass)", [$backend]))->toBeTrue()
                ->and(DB::scalar("SELECT EXISTS (SELECT 1 FROM pg_locks WHERE pid = ? AND granted
                    AND relation = 'primary_campaign_expiry_settlements'::regclass)", [$backend]))->toBeFalse();
        });
        expect(app(BusinessCampaignStore::class)->expireDue(1))->toBe(1);
        $deadline = hrtime(true) + 10_000_000_000;
        while (! $reaped && hrtime(true) < $deadline) {
            $reaped = pcntl_waitpid($pid, $status, WNOHANG) === $pid;
            if (! $reaped) {
                usleep(10_000);
            }
        }
        expect($reaped)->toBeTrue()->and(pcntl_wifexited($status))->toBeTrue()->and(pcntl_wexitstatus($status))->toBe(0);
    } finally {
        Event::forget($event);
        fclose($channels[0]);
        if (! $reaped) {
            posix_kill($pid, SIGKILL);
            pcntl_waitpid($pid, $status);
        }
        DB::purge();
    }
    expect(LedgerEntry::query()->where('kind', 'primary_refund')->count())->toBe(1)
        ->and(DB::table('primary_campaign_expiry_settlements')->count())->toBe(1);
});
