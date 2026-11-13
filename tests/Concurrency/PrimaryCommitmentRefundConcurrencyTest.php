<?php

declare(strict_types=1);

use App\Application\Primary\Contracts\CampaignReservationSummary;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryFunding;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Domain\Operations\CommandRejection;
use App\Models\BusinessCampaign;
use App\Models\CommandOperation;
use App\Models\LedgerEntry;
use App\Models\PrimaryCampaignFunding;
use App\Models\PrimaryCommitment;
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

it('requires a caller transaction before attempting a commitment refund or withdrawal', function (): void {
    expect(fn () => app(PrimaryReservations::class)->refund('campaign', 'reservation', 'party', 2))
        ->toThrow(CommandRejection::class, 'PRIMARY_TRANSACTION_REQUIRED')
        ->and(fn () => app(PrimaryReservations::class)->withdraw('campaign', 'reservation', 'party', 2))
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
        } catch (CommandRejection $exception) {
            $code = ! $fundingFirst && $exception->reason === 'CAMPAIGN_NOT_FULLY_COMMITTED' ? 0 : 1;
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

/**
 * A raise one held reservation short of 100%: the withdrawing Investor's 3 confirmed units, a filler
 * confirmed, and the final buyer's reservation held for the units that would complete it.
 *
 * @return array{campaign: BusinessCampaign, withdrawing: array{user: int, root: string}, final: array{user: int, root: string, version: array<string, mixed>}}
 */
function withdrawalRaceRaise(): array
{
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $checkout = app(PrimaryCheckout::class);
    $units = intdiv((int) $campaign->principal, 5000);
    $hold = function (string $quantity) use ($checkout, $campaign): array {
        $investor = PrimaryReservationFixture::investor();
        $result = $checkout->reserve($investor['user']->id, 1, $campaign->id, $quantity, (string) Str::uuid(), PrimaryReservationFixture::terms(...));
        $root = PrimaryReservationRecord::query()->whereKey($result['data']['reservation_id'])->sole();

        return ['user' => $investor['user']->id, 'root' => $root->id,
            'version' => PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->sole()->payload];
    };
    $confirm = fn (array $held): array => $checkout->confirm($held['user'], 1, $campaign->id, $held['root'], 1, $held['version']['terms']['disclosure_version'],
        $held['version']['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $withdrawing = $hold('3');
    expect($confirm($withdrawing)['code'])->toBe('RESERVATION_CONFIRMED')
        ->and($confirm($hold((string) intdiv($units, 2)))['code'])->toBe('RESERVATION_CONFIRMED');

    return ['campaign' => $campaign, 'withdrawing' => ['user' => $withdrawing['user'], 'root' => $withdrawing['root']],
        'final' => $hold((string) ($units - intdiv($units, 2) - 3))];
}

it('serializes an Investor withdrawal and the confirmation that completes the raise on the Business gate', function (bool $confirmationFirst): void {
    $this->freezeSecond();
    ['campaign' => $campaign, 'withdrawing' => $withdrawing, 'final' => $final] = withdrawalRaceRaise();
    $withdraw = fn (): array => app(PrimaryCheckout::class)->withdraw($withdrawing['user'], 1, $campaign->id, $withdrawing['root'], 2, (string) Str::uuid());
    $confirm = fn (): array => app(PrimaryCheckout::class)->confirm($final['user'], 1, $campaign->id, $final['root'], 1, $final['version']['terms']['disclosure_version'],
        $final['version']['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    DB::disconnect();
    $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    if ($channels === false) {
        throw new RuntimeException('Could not create withdrawal barrier.');
    }
    stream_set_timeout($channels[0], 10);
    stream_set_timeout($channels[1], 10);
    $pid = pcntl_fork();
    if ($pid === -1) {
        fclose($channels[0]);
        fclose($channels[1]);
        throw new RuntimeException('Could not fork withdrawal contender.');
    }
    if ($pid === 0) {
        fclose($channels[0]);
        DB::purge();
        $code = 1;
        try {
            DB::statement("SET lock_timeout = '8s'");
            fwrite($channels[1], DB::selectOne('SELECT pg_backend_pid() AS pid')->pid."\n");
            if (fgets($channels[1]) !== "go\n") {
                throw new RuntimeException('Withdrawal barrier timed out.');
            }
            // Behind a completed raise the withdrawal is final; behind a withdrawal the final buyer still confirms.
            $result = DB::transaction(fn (): array => $confirmationFirst ? $withdraw() : $confirm());
            $code = $result['code'] === ($confirmationFirst ? 'COMMITMENT_LOCKED' : 'RESERVATION_CONFIRMED') ? 0 : 1;
        } catch (Throwable $exception) {
            fwrite(STDERR, $exception::class.' '.$exception->getMessage()."\n");
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
            throw new RuntimeException('Withdrawal contender did not report its backend.');
        }
        DB::beginTransaction();
        $parentBackend = DB::selectOne('SELECT pg_backend_pid() AS pid')->pid;
        expect(($confirmationFirst ? $confirm() : $withdraw())['code'])->toBe($confirmationFirst ? 'RESERVATION_CONFIRMED' : 'COMMITMENT_REFUNDED');
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
            throw new RuntimeException('Withdrawal contender did not finish.');
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
    $summary = app(CampaignReservationSummary::class)->read($campaign->id, now('UTC')->toDateTimeImmutable());
    $units = (string) intdiv((int) $campaign->principal, 5000);
    expect(PrimaryCommitment::query()->count())->toBe(3)
        ->and(LedgerEntry::query()->where('kind', 'primary_refund')->count())->toBe($confirmationFirst ? 0 : 1)
        ->and(DB::table('primary_held_claim_releases')->where('primary_reservation_id', $withdrawing['root'])->exists())->toBe(! $confirmationFirst)
        ->and([$summary['committed_units'], $summary['returned_units']])->toBe($confirmationFirst ? [$units, '0'] : [(string) ((int) $units - 3), '3'])
        ->and(CommandOperation::query()->where('command', 'primary.refund')->sole()->result['code'])
        ->toBe($confirmationFirst ? 'COMMITMENT_LOCKED' : 'COMMITMENT_REFUNDED');
})->with(['confirmation first' => true, 'withdrawal first' => false]);
