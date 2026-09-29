<?php

declare(strict_types=1);

/*
 * Review probe for PR #175 c8f30fcb, migration 2026_09_28_175455. Copy into tests/Concurrency/.
 *
 * The checker reads latest version and cash with plain READ COMMITTED reads at deferred commit. Try to
 * split one terminal outcome across two connections: A retains only the confirmed version and its
 * commitment, B posts only the matching commit. Both reach COMMIT together behind a barrier, so each
 * checker runs while the other half is uncommitted. PASSES when both are refused (exit 2, SQLSTATE
 * 23514) and only the original hold remains.
 */

use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\PostingSource;
use App\Domain\Wallet\WalletMoney;
use App\Models\LedgerEntry;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\Support\PrimaryReservationFixture;

beforeEach(function (): void {
    $this->freezeSecond();
});

it('PROBE: a terminal outcome split across two concurrent transactions is refused on both sides', function (string $order): void {
    $root = DB::transaction(fn () => PrimaryReservationRecord::factory()->withInitialVersion()->create());
    $source = new PostingSource('primary_reservation', $root->id, $root->origin_operation_id);
    $halves = [
        'version' => fn () => PrimaryReservationFixture::terminalVersion($source, 'confirmed'),
        'cash' => function () use ($root, $source): void {
            $wallets = app(WalletPostings::class);
            $wallets->commit($wallets->lockForParty($root->party_id), WalletMoney::of($root->principal), $source);
        },
    ];
    if ($order === 'cash_first') {
        $halves = array_reverse($halves, true);
    }
    DB::disconnect();
    $children = [];
    foreach ($halves as $half) {
        $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        stream_set_timeout($channels[0], 10);
        stream_set_timeout($channels[1], 10);
        $pid = pcntl_fork();
        if ($pid === 0) {
            fclose($channels[0]);
            DB::purge();
            try {
                DB::beginTransaction();
                $half();
                fwrite($channels[1], "ready\n");
                if (fgets($channels[1]) !== "go\n") {
                    exit(3);
                }
                DB::commit();
                exit(0);
            } catch (QueryException|PDOException $exception) {
                exit(str_contains($exception->getMessage(), 'terminal version and cash movement must agree') ? 2 : 1);
            } catch (Throwable) {
                exit(1);
            }
        }
        fclose($channels[1]);
        $children[] = [$pid, $channels[0]];
    }
    foreach ($children as [, $channel]) {
        expect(fgets($channel))->toBe("ready\n");
    }
    foreach ($children as [, $channel]) {
        fwrite($channel, "go\n");
    }
    $results = [];
    foreach ($children as [$pid, $channel]) {
        pcntl_waitpid($pid, $status);
        $results[] = pcntl_wifexited($status) ? pcntl_wexitstatus($status) : -1;
        fclose($channel);
    }
    DB::purge();
    expect($results)->toBe([2, 2])
        ->and(PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->pluck('state')->all())->toBe(['held'])
        ->and(LedgerEntry::query()->where('source_id', $root->id)->pluck('kind')->all())->toBe(['primary_hold']);
})->with(['version_first', 'cash_first']);
