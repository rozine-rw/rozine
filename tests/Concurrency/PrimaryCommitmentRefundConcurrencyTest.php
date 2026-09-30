<?php

declare(strict_types=1);

use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryFunding;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Domain\Operations\CommandRejection;
use App\Domain\Wallet\WalletViolation;
use App\Models\BusinessCampaign;
use App\Models\CommandOperation;
use App\Models\LedgerEntry;
use App\Models\PrimaryCampaignFunding;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

/** @return array{campaign: BusinessCampaign, investorId: int, reservationId: string, admission: array<string, mixed>} */
function retainedRefundRaise(): array
{
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $checkout = app(PrimaryCheckout::class);
    $purchases = [];
    foreach ([1, 2] as $index) {
        $investor = PrimaryReservationFixture::investor();
        $result = $checkout->reserve($investor['user']->id, 1, $campaign->id, '1080', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
        $root = PrimaryReservationRecord::query()->whereKey($result['data']['reservation_id'])->sole();
        $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->sole();
        $checkout->confirm($investor['user']->id, 1, $campaign->id, $root->id, 1, $version->payload['terms']['disclosure_version'],
            $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...));
        $purchases[] = ['user' => $investor['user']->id, 'root' => $root->id];
    }

    return ['campaign' => $campaign, 'investorId' => $purchases[0]['user'], 'reservationId' => $purchases[0]['root'],
        'admission' => ['campaign_id' => $campaign->id, 'publication_sha256' => $campaign->sha256,
            ...array_fill_keys(['eligibility', 'policy', 'connections', 'destination'], ['status' => 'passed', 'evidence' => ['synthetic' => 'Isolated refund command race.']])]];
}

it('requires a caller transaction before attempting a commitment refund', function (): void {
    expect(fn () => app(PrimaryReservations::class)->refund('campaign', 'reservation', 'party', 2))
        ->toThrow(CommandRejection::class, 'PRIMARY_TRANSACTION_REQUIRED');
});

it('serializes the actual investor refund command and full funding on the Business gate', function (bool $fundingFirst): void {
    $this->freezeSecond();
    ['campaign' => $campaign, 'investorId' => $investorId, 'reservationId' => $reservationId, 'admission' => $admission] = retainedRefundRaise();
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
            DB::transaction(function () use ($fundingFirst, $campaign, $investorId, $reservationId, $admission): void {
                if ($fundingFirst) {
                    $result = app(PrimaryCheckout::class)->refund($investorId, 1, $campaign->id, $reservationId, 2, (string) Str::uuid());
                    if ($result['code'] !== 'CAMPAIGN_FUNDED') {
                        throw new RuntimeException('Funded purchase was not refused.');
                    }
                } else {
                    app(PrimaryFunding::class)->lock($campaign->id, fn (): array => $admission);
                    throw new RuntimeException('Refunded purchase incorrectly funded.');
                }
            });
            $code = 0;
        } catch (WalletViolation $exception) {
            $code = ! $fundingFirst && $exception->reason === 'PRIMARY_COMMITTED_CASH_REQUIRED' ? 0 : 1;
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
        if ($fundingFirst) {
            app(PrimaryFunding::class)->lock($campaign->id, fn (): array => $admission);
        } else {
            expect(app(PrimaryCheckout::class)->refund($investorId, 1, $campaign->id, $reservationId, 2, (string) Str::uuid())['code'])
                ->toBe('COMMITMENT_REFUNDED');
        }
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
        DB::commit();
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
    expect(PrimaryCampaignFunding::query()->count())->toBe($fundingFirst ? 1 : 0)
        ->and(LedgerEntry::query()->where('kind', 'primary_refund')->count())->toBe($fundingFirst ? 0 : 1)
        ->and(CommandOperation::query()->where('command', 'primary.refund')->sole()->result['code'])
        ->toBe($fundingFirst ? 'CAMPAIGN_FUNDED' : 'COMMITMENT_REFUNDED');
})->with(['funding first' => true, 'refund first' => false]);
