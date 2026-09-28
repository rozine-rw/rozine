<?php

declare(strict_types=1);

use App\Application\Operations\Contracts\OperationJournal;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\PostingSource;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Domain\Wallet\WalletMoney;
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

it('requires a caller transaction before reading or writing reservation state', function (): void {
    expect(fn () => app(PrimaryReservations::class)->reserve('campaign', 'party', 'operation', '1', PrimaryReservationFixture::terms(...)))
        ->toThrow(CommandRejection::class, 'PRIMARY_TRANSACTION_REQUIRED');
});

it('rolls back successful nested holds with the outer command and can commit after a caught cash refusal', function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $investor = PrimaryReservationFixture::investor('5000');
    expect(fn () => DB::transaction(function () use ($campaign, $investor): void {
        expect(PrimaryReservationFixture::reserve($campaign, $investor, '1')['code'])->toBe('RESERVATION_HELD');
        throw new RuntimeException('outer command aborted');
    }))->toThrow(RuntimeException::class, 'outer command aborted');
    expect(PrimaryReservationRecord::query()->count())->toBe(0)->and(PrimaryReservationVersion::query()->count())->toBe(0)
        ->and(LedgerEntry::query()->where('kind', 'primary_hold')->count())->toBe(0);
    DB::transaction(function () use ($campaign, $investor): void {
        $operation = CommandOperation::factory()->create();
        expect(fn () => app(PrimaryReservations::class)->reserve($campaign->id, $investor['party']->id, $operation->id, '2', PrimaryReservationFixture::terms(...)))
            ->toThrow(CommandRejection::class, 'INSUFFICIENT_AVAILABLE_FUNDS');
    });
    expect(PrimaryReservationRecord::query()->count())->toBe(0)->and(PrimaryReservationVersion::query()->count())->toBe(0)
        ->and(LedgerEntry::query()->where('kind', 'primary_hold')->count())->toBe(0);
    expect(PrimaryReservationFixture::reserve($campaign, $investor, '1')['code'])->toBe('RESERVATION_HELD');
});

it('allows exactly one of two independent contenders to reserve the last available units', function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    PrimaryReservationFixture::reserve($campaign, PrimaryReservationFixture::investor(), '1080');
    $investors = [PrimaryReservationFixture::investor(), PrimaryReservationFixture::investor()];
    $children = [];
    DB::disconnect();
    foreach ($investors as $investor) {
        $pid = pcntl_fork();
        if ($pid === -1) {
            throw new RuntimeException('Could not fork reservation contender.');
        }
        if ($pid === 0) {
            DB::purge();
            try {
                $result = PrimaryReservationFixture::reserve($campaign, $investor, '1080');
                exit(match ($result['code']) {
                    'RESERVATION_HELD' => 0, 'UNITS_UNAVAILABLE' => 2, default => 3
                });
            } catch (Throwable) {
                exit(4);
            }
        }
        $children[] = $pid;
    }
    $results = [];
    foreach ($children as $pid) {
        pcntl_waitpid($pid, $status);
        $results[] = pcntl_wifexited($status) ? pcntl_wexitstatus($status) : -1;
    }
    sort($results);
    expect($results)->toBe([0, 2])->and(PrimaryReservationRecord::query()->sum('units'))->toEqual(2160)
        ->and(LedgerEntry::query()->where('kind', 'primary_hold')->count())->toBe(2)
        ->and(PrimaryReservationRecord::query()->orderBy('id')->get()->pluck('payload.ordinals')->all())
        ->toBe([[['first' => '1', 'last' => '1080']], [['first' => '1081', 'last' => '2160']]]);
});

it('enforces the campaign aggregate for racing raw persistence writers too', function (): void {
    $this->freezeSecond();
    $campaign = BusinessCampaign::factory()->create();
    $contenders = array_map(fn (): array => PrimaryReservationRecord::factory()->raw([
        'business_campaign_id' => $campaign->id, 'units' => 600, 'principal' => '3000000',
    ]), range(1, 2));
    $children = [];
    DB::disconnect();
    foreach ($contenders as $attributes) {
        $pid = pcntl_fork();
        if ($pid === -1) {
            throw new RuntimeException('Could not fork raw reservation contender.');
        }
        if ($pid === 0) {
            DB::purge();
            try {
                DB::transaction(fn () => PrimaryReservationRecord::factory()->withInitialVersion()->create($attributes));
                exit(0);
            } catch (QueryException $exception) {
                exit(str_contains($exception->getMessage(), 'published campaign capacity') ? 2 : 3);
            } catch (Throwable) {
                exit(4);
            }
        }
        $children[] = $pid;
    }
    $results = [];
    foreach ($children as $pid) {
        pcntl_waitpid($pid, $status);
        $results[] = pcntl_wifexited($status) ? pcntl_wexitstatus($status) : -1;
    }
    sort($results);
    expect($results)->toBe([0, 2])->and(PrimaryReservationRecord::query()->sum('principal'))->toEqual(3000000);
});

it('rolls cash and inventory back when a mismatched journal command reaches the outer commit', function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $investor = PrimaryReservationFixture::investor('5000');
    expect(fn () => app(OperationJournal::class)->execute('party:'.$investor['party']->id,
        $investor['user']->id, 'wallet.deposit', (string) Str::uuid(), 'campaign', $campaign->id, ['units' => '1'],
        function (): void {},
        fn (string $operation): OperationResult => PrimaryReservationFixture::outcome(
            app(PrimaryReservations::class)->reserve($campaign->id, $investor['party']->id, $operation, '1', PrimaryReservationFixture::terms(...))
        )))->toThrow(PDOException::class, 'Party command and target binding');
    expect(PrimaryReservationRecord::query()->count())->toBe(0)->and(PrimaryReservationVersion::query()->count())->toBe(0)
        ->and(LedgerEntry::query()->where('kind', 'primary_hold')->count())->toBe(0);
    expect(PrimaryReservationFixture::reserve($campaign, $investor, '1')['code'])->toBe('RESERVATION_HELD');
});

it('keeps unit identities unique for simultaneous writers with older snapshots', function (string $isolation): void {
    $this->freezeSecond();
    $campaign = BusinessCampaign::factory()->create();
    $contenders = array_map(fn (): array => PrimaryReservationRecord::factory()->raw([
        'business_campaign_id' => $campaign->id, 'ordinal_ranges' => '{[1,4)}', 'units' => 3, 'principal' => '15000',
    ]), range(1, 2));
    DB::statement('ALTER TABLE primary_reservations DISABLE TRIGGER primary_ordinals_unique');
    $children = [];
    DB::disconnect();
    try {
        foreach ($contenders as $attributes) {
            $channels = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
            if ($channels === false) {
                throw new RuntimeException('Could not create the ordinal contender barrier.');
            }
            stream_set_timeout($channels[0], 8);
            stream_set_timeout($channels[1], 8);
            $pid = pcntl_fork();
            if ($pid === -1) {
                throw new RuntimeException('Could not fork the ordinal contender.');
            }
            if ($pid === 0) {
                fclose($channels[0]);
                DB::purge();
                try {
                    DB::statement("SET lock_timeout = '5s'");
                    DB::beginTransaction();
                    DB::statement('SET TRANSACTION ISOLATION LEVEL '.$isolation);
                    PrimaryReservationRecord::query()->count();
                    fwrite($channels[1], "ready\n");
                    if (fgets($channels[1]) !== "go\n") {
                        exit(3);
                    }
                    PrimaryReservationRecord::factory()->withInitialVersion()->create($attributes);
                    DB::commit();
                    exit(0);
                } catch (QueryException $exception) {
                    exit(str_contains($exception->getMessage(), 'primary_ordinal_claims_pkey') ? 2 : 3);
                } catch (Throwable) {
                    exit(4);
                }
            }
            fclose($channels[1]);
            $children[$pid] = $channels[0];
        }
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
        expect($results)->toBe([0, 2])->and(PrimaryReservationRecord::query()->count())->toBe(1)
            ->and(DB::table('primary_ordinal_claims')->orderBy('ordinal')->pluck('ordinal')->all())->toBe([1, 2, 3]);
    } finally {
        foreach ($children as $pid => $channel) {
            fclose($channel);
            pcntl_waitpid($pid, $status);
        }
        DB::statement('ALTER TABLE primary_reservations ENABLE TRIGGER primary_ordinals_unique');
    }
})->with(['READ COMMITTED', 'REPEATABLE READ']);

it('rejects incomplete reservation cash bindings at the real outer commit atomically', function (string $case): void {
    $this->freezeSecond();
    $investor = PrimaryReservationFixture::investor('50000');
    $foreign = $case === 'party' ? PrimaryReservationFixture::investor('50000') : $investor;
    expect(fn () => DB::transaction(function () use ($investor, $foreign, $case): void {
        $root = $case === 'orphan' ? null : PrimaryReservationRecord::factory()->withInitialVersion(false)->create(['party_id' => $investor['party']->id]);
        if ($case !== 'missing') {
            $postings = app(WalletPostings::class);
            $postings->hold($postings->lockForParty($foreign['party']->id), WalletMoney::of($case === 'amount' ? '5001' : '5000'),
                new PostingSource('primary_reservation', $root->id ?? strtolower((string) Str::ulid()),
                    $case === 'operation' ? strtolower((string) Str::ulid()) : ($root->origin_operation_id ?? strtolower((string) Str::ulid()))));
        }
    }))->toThrow(PDOException::class, 'Primary');
    expect(PrimaryReservationRecord::query()->count())->toBe(0)->and(PrimaryReservationVersion::query()->count())->toBe(0)
        ->and(DB::table('primary_ordinal_claims')->count())->toBe(0)
        ->and(LedgerEntry::query()->where('kind', 'primary_hold')->count())->toBe(0);
    DB::transaction(fn () => PrimaryReservationRecord::factory()->withInitialVersion()->create());
    expect(PrimaryReservationRecord::query()->count())->toBe(1);
})->with(['missing', 'orphan', 'party', 'operation', 'amount']);
