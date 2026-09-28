<?php

declare(strict_types=1);

use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Domain\Operations\CommandRejection;
use App\Models\CommandOperation;
use App\Models\InvestorWallet;
use App\Models\LedgerEntry;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

it('requires an outer transaction for confirmation persistence', function (): void {
    expect(fn () => app(PrimaryReservations::class)->confirm('campaign', 'reservation', 'party', 'operation', 1, 'version', 'hash', PrimaryReservationFixture::terms(...)))
        ->toThrow(CommandRejection::class, 'PRIMARY_TRANSACTION_REQUIRED');
});

it('retains Business current caller campaign reservation and wallet locks through confirmation commit', function (string $action): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $investor = PrimaryReservationFixture::investor();
    $checkout = app(PrimaryCheckout::class);
    $checkout->reserve($investor['user']->id, 1, $campaign->id, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $root = PrimaryReservationRecord::query()->sole();
    $version = PrimaryReservationVersion::query()->sole();
    $request = (string) Str::uuid();
    $confirm = fn (): array => $checkout->confirm($investor['user']->id, 1, $campaign->id, $root->id, 1,
        $version->payload['terms']['disclosure_version'], $version->payload['disclosure_sha256'], $request, PrimaryReservationFixture::terms(...));
    if ($action === 'lookup') {
        $confirm();
    }
    config(['database.connections.primary_confirmation_observer' => config('database.connections.pgsql')]);
    $observer = DB::connection('primary_confirmation_observer');
    $targets = ['business_profiles' => $campaign->business_id, 'users' => $investor['user']->id, 'parties' => $investor['party']->id];
    try {
        DB::beginTransaction();
        $result = $action === 'lookup' ? $checkout->findConfirmation($investor['user']->id, 1, $campaign->id, $root->id, $request) : $confirm();
        expect($result['code'])->toBe('RESERVATION_CONFIRMED');
        if ($action === 'confirm') {
            $targets['business_campaigns'] = $campaign->id;
            $targets['primary_reservations'] = $root->id;
            $targets['investor_wallets'] = InvestorWallet::query()->where('party_id', $investor['party']->id)->value('id');
        }
        foreach ($targets as $table => $id) {
            expect(fn () => $observer->transaction(fn () => $observer->table($table)->where('id', $id)->lock('FOR UPDATE NOWAIT')->first()))
                ->toThrow(QueryException::class, 'could not obtain lock');
        }
        DB::commit();
        foreach ($targets as $table => $id) {
            expect($observer->transaction(fn () => $observer->table($table)->where('id', $id)->lock('FOR UPDATE NOWAIT')->first())?->id)->toBe($id);
        }
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        DB::purge('primary_confirmation_observer');
    }
})->with(['confirm', 'lookup']);

it('allows one new confirmation but replays an identical request for simultaneous callers', function (bool $sameKey): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $investor = PrimaryReservationFixture::investor();
    app(PrimaryCheckout::class)->reserve($investor['user']->id, 1, $campaign->id, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $root = PrimaryReservationRecord::query()->sole();
    $version = PrimaryReservationVersion::query()->sole();
    $sharedKey = (string) Str::uuid();
    $children = [];
    DB::disconnect();
    foreach (range(1, 2) as $index) {
        $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        if ($channels === false) {
            throw new RuntimeException('Could not create confirmation barrier.');
        }
        stream_set_timeout($channels[0], 8);
        stream_set_timeout($channels[1], 8);
        $pid = pcntl_fork();
        if ($pid === -1) {
            throw new RuntimeException('Could not fork confirmation contender.');
        }
        if ($pid === 0) {
            fclose($channels[0]);
            DB::purge();
            try {
                DB::statement("SET lock_timeout = '5s'");
                fwrite($channels[1], "ready\n");
                if (fgets($channels[1]) !== "go\n") {
                    exit(3);
                }
                $result = app(PrimaryCheckout::class)->confirm($investor['user']->id, 1, $campaign->id, $root->id, 1,
                    $version->payload['terms']['disclosure_version'], $version->payload['disclosure_sha256'],
                    $sameKey ? $sharedKey : (string) Str::uuid(), PrimaryReservationFixture::terms(...));
                exit(match ($result['code']) {
                    'RESERVATION_CONFIRMED' => 0, 'VERSION_CONFLICT' => 2, default => 3
                });
            } catch (Throwable) {
                exit(4);
            }
        }
        fclose($channels[1]);
        $children[$pid] = $channels[0];
    }
    try {
        foreach ($children as $channel) {
            expect(fgets($channel))->toBe("ready\n");
        }
        foreach ($children as $channel) {
            fwrite($channel, "go\n");
        }
        $results = [];
        foreach ($children as $pid => $channel) {
            pcntl_waitpid($pid, $status);
            $results[] = pcntl_wifexited($status) ? pcntl_wexitstatus($status) : -1;
        }
        sort($results);
        expect($results)->toBe($sameKey ? [0, 0] : [0, 2])
            ->and(PrimaryCommitment::query()->count())->toBe(1)->and(PrimaryReservationVersion::query()->count())->toBe(2)
            ->and(LedgerEntry::query()->where('kind', 'primary_commit')->count())->toBe(1)
            ->and(CommandOperation::query()->where('command', 'primary.confirm')->count())->toBe($sameKey ? 1 : 2);
    } finally {
        foreach ($children as $pid => $channel) {
            fclose($channel);
            pcntl_waitpid($pid, $status);
        }
    }
})->with([false, true]);

it('rolls the journal commitment version and cash movement back with the caller transaction', function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $investor = PrimaryReservationFixture::investor();
    $checkout = app(PrimaryCheckout::class);
    $checkout->reserve($investor['user']->id, 1, $campaign->id, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $root = PrimaryReservationRecord::query()->sole();
    $version = PrimaryReservationVersion::query()->sole();
    $confirm = fn (): array => $checkout->confirm($investor['user']->id, 1, $campaign->id, $root->id, 1,
        $version->payload['terms']['disclosure_version'], $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    expect(fn () => DB::transaction(function () use ($confirm): void {
        expect($confirm()['code'])->toBe('RESERVATION_CONFIRMED');
        throw new RuntimeException('Caller aborted.');
    }))->toThrow(RuntimeException::class, 'Caller aborted.');
    expect(PrimaryCommitment::query()->count())->toBe(0)->and(PrimaryReservationVersion::query()->count())->toBe(1)
        ->and(LedgerEntry::query()->where('kind', 'primary_commit')->count())->toBe(0)
        ->and(CommandOperation::query()->where('command', 'primary.confirm')->count())->toBe(0);
    expect($confirm()['code'])->toBe('RESERVATION_CONFIRMED');
});
