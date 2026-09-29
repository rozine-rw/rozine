<?php

declare(strict_types=1);

/*
 * Review probe for PR #175 CampaignReservationSummary: consistent snapshot under a real concurrent writer.
 * Copy into tests/Concurrency/.
 */

use App\Application\Primary\Contracts\CampaignReservationSummary;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

/** @return array{0: resource, 1: int} */
function r175tFork(Closure $child): array
{
    $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    stream_set_timeout($channels[0], 60);
    stream_set_timeout($channels[1], 60);
    DB::disconnect();
    $pid = pcntl_fork();
    if ($pid === 0) {
        fclose($channels[0]);
        DB::purge();
        try {
            $child($channels[1]);
            exit(0);
        } catch (Throwable $exception) {
            fwrite(STDERR, 'child: '.$exception::class.': '.$exception->getMessage()."\n");
            exit(4);
        }
    }
    fclose($channels[1]);

    return [$channels[0], $pid];
}

beforeEach(function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $this->campaign = PrimaryReservationFixture::campaign();
    $this->checkout = app(PrimaryCheckout::class);
    $this->holds = [];
    foreach (range(1, 4) as $n) {
        $investor = PrimaryReservationFixture::investor();
        foreach (range(1, 6) as $m) {
            $result = $this->checkout->reserve($investor['user']->id, 1, $this->campaign->id, (string) (1 + ($m % 3)), (string) Str::uuid(), PrimaryReservationFixture::terms(...));
            $this->holds[] = [$investor['user']->id, $result['data']['reservation_id']];
        }
    }
    $this->summary = app(CampaignReservationSummary::class);
});

it('PROBE an uncommitted confirmation is invisible and non-blocking, then visible whole after commit', function (): void {
    [$userId, $reservationId] = $this->holds[0];
    $campaignId = $this->campaign->id;
    [$channel, $pid] = r175tFork(function ($channel) use ($userId, $reservationId, $campaignId): void {
        $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $reservationId)->sole();
        DB::beginTransaction();
        $result = app(PrimaryCheckout::class)->confirm($userId, 1, $campaignId, $reservationId, 1,
            $version->payload['terms']['disclosure_version'], $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...));
        if ($result['code'] !== 'RESERVATION_CONFIRMED') {
            exit(2);
        }
        fwrite($channel, "confirmed-uncommitted\n");
        if (fgets($channel) !== "commit\n") {
            exit(3);
        }
        DB::commit();
        fwrite($channel, "committed\n");
    });
    try {
        expect(fgets($channel))->toBe("confirmed-uncommitted\n");
        DB::statement("SET statement_timeout = '2s'");
        $during = $this->summary->read($this->campaign->id, now()->toDateTimeImmutable());
        fwrite($channel, "commit\n");
        expect(fgets($channel))->toBe("committed\n");
        $after = $this->summary->read($this->campaign->id, now()->toDateTimeImmutable());
        $units = (string) PrimaryReservationRecord::query()->whereKey($reservationId)->value('units');
        expect($during['committed_units'])->toBe('0')->and($during['investors'])->toBe(0)
            ->and($after['committed_units'])->toBe($units)->and($after['investors'])->toBe(1)
            ->and((int) $after['held_units'])->toBe((int) $during['held_units'] - (int) $units);
    } finally {
        fclose($channel);
        pcntl_waitpid($pid, $status);
        expect(pcntl_wexitstatus($status))->toBe(0);
    }
});

it('PROBE summary reads never tear while a concurrent writer confirms and releases every hold', function (): void {
    $holds = $this->holds;
    $campaignId = $this->campaign->id;
    [$channel, $pid] = r175tFork(function ($channel) use ($holds, $campaignId): void {
        fwrite($channel, "ready\n");
        $checkout = app(PrimaryCheckout::class);
        foreach ($holds as $index => [$userId, $reservationId]) {
            if ($index % 2 === 0) {
                $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $reservationId)->sole();
                $code = $checkout->confirm($userId, 1, $campaignId, $reservationId, 1, $version->payload['terms']['disclosure_version'],
                    $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...))['code'];
            } else {
                $code = $checkout->release($userId, 1, $campaignId, $reservationId, 1, (string) Str::uuid())['code'];
            }
            if (! in_array($code, ['RESERVATION_CONFIRMED', 'RESERVATION_RELEASED'], true)) {
                exit(2);
            }
        }
        fwrite($channel, "done\n");
    });
    try {
        expect(fgets($channel))->toBe("ready\n");
        stream_set_blocking($channel, false);
        $reads = 0;
        $snapshots = [];
        $torn = 0;
        $done = false;
        $previous = ['committed_units' => 0, 'returned_units' => 0];
        while (! $done) {
            $done = fgets($channel) === "done\n";
            $read = $this->summary->read($campaignId, now()->toDateTimeImmutable());
            $reads++;
            $snapshots[$read['committed_units'].'/'.$read['returned_units']] = true;
            $buckets = (int) $read['committed_units'] + (int) $read['held_units'] + (int) $read['expired_hold_units'] + (int) $read['returned_units'];
            foreach (['committed', 'held', 'expired_hold', 'returned'] as $bucket) {
                if ((string) ((int) $read[$bucket.'_units'] * 5000) !== $read[$bucket.'_principal']) {
                    $torn++;
                }
            }
            if ($buckets !== (int) $read['occupied_units'] || (int) $read['committed_units'] < $previous['committed_units']
                || (int) $read['returned_units'] < $previous['returned_units']) {
                $torn++;
            }
            $previous = ['committed_units' => (int) $read['committed_units'], 'returned_units' => (int) $read['returned_units']];
            // Control: the same facts read as two statements can observe a commit in between.
            $confirmedLatest = (int) DB::selectOne("SELECT COUNT(*) AS c FROM primary_reservations r JOIN LATERAL (SELECT state FROM primary_reservation_versions v
                WHERE v.primary_reservation_id = r.id ORDER BY revision DESC LIMIT 1) v ON true WHERE r.business_campaign_id = ? AND v.state = 'confirmed'", [$campaignId])->c;
            $commitments = (int) DB::selectOne('SELECT COUNT(*) AS c FROM primary_commitments c JOIN primary_reservations r ON r.id = c.primary_reservation_id WHERE r.business_campaign_id = ?', [$campaignId])->c;
            $controlTorn = ($controlTorn ?? 0) + ($confirmedLatest !== $commitments ? 1 : 0);
        }
        $final = $this->summary->read($campaignId, now()->toDateTimeImmutable());
        fwrite(STDERR, "summary reads={$reads} distinct snapshots=".count($snapshots)." torn={$torn}; two-statement control mismatches={$controlTorn}\n");
        expect($torn)->toBe(0)->and(count($snapshots))->toBeGreaterThan(2)
            ->and($final['held_units'])->toBe('0')->and($final['investors'])->toBe(4)
            ->and((int) $final['committed_units'] + (int) $final['returned_units'])->toBe((int) $final['occupied_units']);
    } finally {
        fclose($channel);
        pcntl_waitpid($pid, $status);
        expect(pcntl_wexitstatus($status))->toBe(0);
    }
});
