<?php

declare(strict_types=1);

use App\Application\Operations\Contracts\OperationJournal;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
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
