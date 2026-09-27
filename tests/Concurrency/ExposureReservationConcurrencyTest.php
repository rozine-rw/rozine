<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Business\Contracts\BusinessExposureStore;
use App\Models\BusinessApplicationRelease;
use App\Models\BusinessApplicationSignature;
use App\Models\BusinessApplicationSubmission;
use App\Models\BusinessCampaign;
use App\Models\BusinessCampaignClosure;
use App\Models\BusinessExposureReservation;
use App\Models\CommandOperation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Support\AuditSealingFixture;
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

it('releases and publishes only once when concurrent staff and Business requests race', function (bool $sameRequest): void {
    $fixture = AuditSealingFixture::ready();
    AuditSealingFixture::seal($fixture);
    AuditSealingFixture::cosign($fixture);
    $releaseRequest = (string) Str::uuid();
    $release = fn (string $id): Closure => function () use ($fixture, $id): void {
        expect(app(BusinessCampaignStore::class)->release($fixture['audit']['staff']->id,
            $fixture['application']->id, 0, 'Verified the current release gates.', $id)['code'])->toBeIn(['APPLICATION_RELEASED', 'VERSION_CONFLICT']);
    };
    DB::disconnect();
    expect(exposureContenders([$release($releaseRequest), $release($sameRequest ? $releaseRequest : (string) Str::uuid())]))->toBe([0, 0]);
    $revision = $fixture['application']->refresh()->revision;
    $publishRequest = (string) Str::uuid();
    $publish = fn (string $id): Closure => function () use ($fixture, $id, $revision): void {
        expect(app(BusinessCampaignStore::class)->publish($fixture['audit']['authority']['users'][0]->id,
            1, $fixture['audit']['business'], $fixture['application']->id, $revision, 'listing-fee-waiver-1', $id)['code'])
            ->toBeIn(['LISTING_PUBLISHED', 'LISTING_ALREADY_PUBLISHED']);
    };
    DB::disconnect();
    expect(exposureContenders([$publish($publishRequest), $publish($sameRequest ? $publishRequest : (string) Str::uuid())]))->toBe([0, 0])
        ->and(BusinessApplicationRelease::query()->count())->toBe(1)
        ->and(BusinessCampaign::query()->count())->toBe(1)
        ->and(BusinessExposureReservation::query()->count())->toBe(1)
        ->and(CommandOperation::query()->where('command', 'application.release')->count())->toBe($sameRequest ? 1 : 2)
        ->and(CommandOperation::query()->where('command', 'application.publish')->count())->toBe($sameRequest ? 1 : 2);
})->with([false, true]);

it('serializes cancellation expiry and retries against a single exposure release', function (string $race): void {
    $this->freezeSecond();
    $fixture = AuditSealingFixture::ready();
    AuditSealingFixture::seal($fixture);
    AuditSealingFixture::cosign($fixture);
    $store = app(BusinessCampaignStore::class);
    $user = $fixture['audit']['authority']['users'][0];
    $business = $fixture['audit']['business'];
    $store->release($fixture['audit']['staff']->id, $fixture['application']->id, 0, 'Reviewed.', (string) Str::uuid());
    $store->publish($user->id, 1, $business, $fixture['application']->id,
        $fixture['application']->refresh()->revision, 'listing-fee-waiver-1', (string) Str::uuid());
    $campaign = BusinessCampaign::query()->sole();
    $request = (string) Str::uuid();
    $cancel = fn (string $id): Closure => function () use ($user, $business, $campaign, $id): void {
        $this->travelTo($campaign->expires_at->subSecond());
        expect(app(BusinessCampaignStore::class)->cancel($user->id, 1, $business, $campaign->id, 1, null, $id)['code'])
            ->toBeIn(['CAMPAIGN_CANCELLED', 'VERSION_CONFLICT']);
    };
    $expire = function () use ($campaign): void {
        $this->travelTo($campaign->expires_at);
        expect(app(BusinessCampaignStore::class)->expireDue(100))->toBeIn([0, 1]);
    };
    $operations = match ($race) {
        'same-request' => [$cancel($request), $cancel($request)],
        'distinct-requests' => [$cancel($request), $cancel((string) Str::uuid())],
        'cancel-expire' => [$cancel($request), $expire],
        'two-sweeps' => [$expire, $expire],
        default => throw new InvalidArgumentException('Unknown race.'),
    };
    DB::disconnect();
    expect(exposureContenders($operations))->toBe([0, 0])
        ->and(BusinessCampaignClosure::query()->count())->toBe(1)
        ->and(BusinessExposureReservation::query()->count())->toBe(1)
        ->and(app(BusinessExposureStore::class)->current($business))->toBe([]);
    if ($race === 'same-request') {
        expect(CommandOperation::query()->where('command', 'campaign.cancel')->count())->toBe(1);
    }
})->with(['same-request', 'distinct-requests', 'cancel-expire', 'two-sweeps']);
