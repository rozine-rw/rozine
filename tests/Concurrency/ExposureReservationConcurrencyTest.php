<?php

declare(strict_types=1);

use App\Models\BusinessApplicationSignature;
use App\Models\BusinessApplicationSubmission;
use App\Models\BusinessExposureReservation;
use App\Models\CommandOperation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Support\BusinessQuoteFixture;

/**
 * @param  list<Closure(): void>  $operations
 * @return list<int>
 */
function exposureContenders(array $operations): array
{
    $pids = [];
    foreach ($operations as $operation) {
        $pid = pcntl_fork();
        if ($pid === -1) {
            throw new RuntimeException('Could not fork an exposure contender.');
        }
        if ($pid === 0) {
            DB::purge();
            try {
                $operation();
                exit(0);
            } catch (Throwable) {
                exit(1);
            }
        }
        $pids[] = $pid;
    }
    $statuses = [];
    foreach ($pids as $pid) {
        pcntl_waitpid($pid, $status);
        $exitStatus = pcntl_wifexited($status) ? pcntl_wexitstatus($status) : false;
        if ($exitStatus === false) {
            throw new RuntimeException('Exposure contender did not exit normally.');
        }
        $statuses[] = $exitStatus;
    }

    return $statuses;
}

it('reserves exposure once across simultaneous final signatures and retries', function (bool $sameRequest): void {
    $fixture = BusinessQuoteFixture::ready();
    $accepted = BusinessQuoteFixture::acceptance($fixture);
    $request = (string) Str::uuid();
    $submit = fn (string $id): Closure => function () use ($fixture, $accepted, $id): void {
        expect(BusinessQuoteFixture::submit($fixture, $accepted, revision: 4, request: $id)['code'])
            ->toBeIn(['APPLICATION_SUBMITTED', 'VERSION_CONFLICT']);
    };
    expect(exposureContenders([$submit($request), $submit($sameRequest ? $request : (string) Str::uuid())]))->toBe([0, 0])
        ->and(BusinessExposureReservation::query()->count())->toBe(1)
        ->and(BusinessApplicationSubmission::query()->count())->toBe(1)
        ->and(CommandOperation::query()->where('command', 'application.submit')->count())->toBe($sameRequest ? 1 : 2);
})->with([false, true]);

it('waits for all concurrent signatories before reserving the full amount', function (): void {
    $fixture = BusinessQuoteFixture::ready(2);
    $accepted = BusinessQuoteFixture::acceptance($fixture);
    $sign = fn (int $index): Closure => function () use ($fixture, $accepted, $index): void {
        expect(BusinessQuoteFixture::submit($fixture, $accepted, $index, 4)['code'])
            ->toBeIn(['APPLICATION_SIGNATURE_RECORDED', 'VERSION_CONFLICT']);
    };
    expect(exposureContenders([$sign(0), $sign(1)]))->toBe([0, 0])->and(BusinessExposureReservation::query()->count())->toBe(0);
    $signed = BusinessApplicationSignature::query()->sole()->actor_party_id;
    $missing = $fixture['audit']['authority']['users'][0]->party_id === $signed ? 1 : 0;
    expect(BusinessQuoteFixture::submit($fixture, $accepted, $missing, 5)['code'])->toBe('APPLICATION_SUBMITTED')
        ->and(BusinessExposureReservation::query()->count())->toBe(1)
        ->and(BusinessExposureReservation::query()->sole()->principal)->toBe($accepted['accepted_principal']);
});

it('holds the Business exposure lock through the final reservation write', function (): void {
    $fixture = BusinessQuoteFixture::ready();
    $accepted = BusinessQuoteFixture::acceptance($fixture);
    $event = 'eloquent.creating: '.BusinessExposureReservation::class;
    Event::listen($event, function (BusinessExposureReservation $record): void {
        config(['database.connections.exposure_contender' => config('database.connections.pgsql')]);
        $connection = DB::connection('exposure_contender');
        $connection->statement("SET lock_timeout = '500ms'");
        try {
            expect(fn () => $connection->table('business_exposure_reservations')->insert($record->getAttributes()))
                ->toThrow(QueryException::class, 'lock timeout');
        } finally {
            DB::purge('exposure_contender');
        }
    });
    try {
        expect(BusinessQuoteFixture::submit($fixture, $accepted)['code'])->toBe('APPLICATION_SUBMITTED');
    } finally {
        Event::forget($event);
    }
    expect(BusinessExposureReservation::query()->count())->toBe(1);
});
