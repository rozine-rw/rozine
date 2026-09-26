<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessExposureStore;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Domain\Operations\CommandRejection;
use App\Models\BusinessApplicationQuote;
use App\Models\BusinessApplicationSignature;
use App\Models\BusinessApplicationSubmission;
use App\Models\BusinessExposureReservation;
use App\Models\BusinessProfile;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Support\BusinessQuoteFixture;

it('reserves full accepted principal only at the final required signature and replays one effect', function (): void {
    $fixture = BusinessQuoteFixture::ready(2);
    $acceptance = BusinessQuoteFixture::acceptance($fixture);
    BusinessQuoteFixture::submit($fixture, $acceptance);
    expect(BusinessExposureReservation::query()->count())->toBe(0);
    $request = (string) Str::uuid();
    $result = BusinessQuoteFixture::submit($fixture, $acceptance, 1, 5, $request);
    $reservation = BusinessExposureReservation::query()->sole();
    $submission = BusinessApplicationSubmission::query()->sole();
    expect($result['code'])->toBe('APPLICATION_SUBMITTED')
        ->and($reservation->principal)->toBe($acceptance['accepted_principal'])
        ->and($reservation->id)->toBe($submission->id)
        ->and($result['data']['submission']['exposure_reservation_id'])->toBe($reservation->id)
        ->and($reservation->payload['submission_sha256'])->toBe($submission->sha256)
        ->and($reservation->sha256)->toBe(hash('sha256', app(CanonicalJson::class)->encode($reservation->payload)))
        ->and($reservation->getRawOriginal('payload'))->not->toContain('submission_id')
        ->and($reservation->toArray())->not->toHaveKey('payload')
        ->and(BusinessQuoteFixture::submit($fixture, $acceptance, 1, 5, $request))->toBe($result)
        ->and(BusinessQuoteFixture::review($fixture))->toEqual($result['data'])
        ->and(BusinessExposureReservation::query()->count())->toBe(1)
        ->and(app(BusinessExposureStore::class)->current($fixture['audit']['business']))
        ->toBe([['id' => $reservation->id, 'principal' => $reservation->principal]]);
});

it('rolls back the final signature submission revision and reservation together on a write failure', function (): void {
    $fixture = BusinessQuoteFixture::ready(2);
    $acceptance = BusinessQuoteFixture::acceptance($fixture);
    BusinessQuoteFixture::submit($fixture, $acceptance);
    $event = 'eloquent.created: '.BusinessExposureReservation::class;
    Event::listen($event, fn () => throw new RuntimeException('synthetic exposure failure'));
    try {
        expect(fn () => BusinessQuoteFixture::submit($fixture, $acceptance, 1))->toThrow(RuntimeException::class, 'synthetic exposure failure');
    } finally {
        Event::forget($event);
    }
    expect(BusinessExposureReservation::query()->count())->toBe(0)->and(BusinessApplicationSubmission::query()->count())->toBe(0)
        ->and(BusinessApplicationSignature::query()->count())->toBe(1)->and($fixture['application']->refresh()->revision)->toBe(5);
    expect(BusinessQuoteFixture::submit($fixture, $acceptance, 1)['code'])->toBe('APPLICATION_SUBMITTED');
});

it('requires a fresh quote after another acceptance consumes borrowing room', function (): void {
    $fixture = BusinessQuoteFixture::ready();
    $accepted = BusinessQuoteFixture::acceptance($fixture);
    BusinessExposureReservation::factory()->create(['business_id' => $fixture['audit']['business'],
        'business_application_id' => $fixture['application']->id,
        'business_application_submission_id' => function (array $attributes) use ($fixture): string {
            return BusinessApplicationSubmission::factory()->create(['business_application_id' => $attributes['business_application_id'],
                'business_application_quote_id' => $fixture['application']->current_quote_id])->id;
        }, 'principal' => '10000000']);
    $request = (string) Str::uuid();
    $refused = BusinessQuoteFixture::submit($fixture, $accepted, request: $request);
    expect($refused['code'])->toBe('QUOTE_STALE')->and($refused['http_status'])->toBe(409)
        ->and(BusinessQuoteFixture::submit($fixture, $accepted, request: $request))->toBe($refused)
        ->and(BusinessApplicationSignature::query()->count())->toBe(0)
        ->and(BusinessQuoteFixture::evaluate($fixture, 4)['data']['quote']['principal']['amount'])->toBe('6800000');
});

it('refuses reservation mutation deletion and rollback at the database boundary', function (string $change): void {
    $fixture = BusinessQuoteFixture::ready();
    BusinessQuoteFixture::submit($fixture, BusinessQuoteFixture::acceptance($fixture));
    $reservation = BusinessExposureReservation::query()->sole();
    expect(fn () => DB::transaction(function () use ($change, $reservation): void {
        if ($change === 'rollback') {
            (require database_path('migrations/2026_09_26_190340_create_business_exposure_reservations_table.php'))->down();
        } elseif ($change === 'delete') {
            $reservation->delete();
        } else {
            $reservation->forceFill(['principal' => '5000000'])->save();
        }
    }))->toThrow(QueryException::class, $change === 'rollback' ? 'Reserved exposure requires a forward migration' : 'Business exposure reservations are immutable');
    expect($reservation->refresh()->principal)->toBe('10800000');
})->with(['update', 'delete', 'rollback']);

it('rejects corrupt exposure records before using them in borrowing room', function (string $field): void {
    $fixture = BusinessQuoteFixture::ready();
    $submission = BusinessApplicationSubmission::factory()->create(['business_application_id' => $fixture['application']->id,
        'business_application_quote_id' => $fixture['application']->current_quote_id]);
    $id = (string) Str::ulid();
    $payload = ['reservation_id' => $id, 'business_id' => $fixture['audit']['business'], 'application_id' => $fixture['application']->id,
        'submission_id' => $submission->id, 'principal' => '3000000'];
    if ($field !== 'sha256') {
        $payload[$field] = 'corrupt';
    }
    BusinessExposureReservation::factory()->create(['id' => $id, 'business_id' => $fixture['audit']['business'],
        'business_application_id' => $fixture['application']->id, 'business_application_submission_id' => $submission->id,
        'payload' => $payload, 'sha256' => $field === 'sha256' ? str_repeat('0', 64) : hash('sha256', app(CanonicalJson::class)->encode($payload))]);
    expect(fn () => BusinessQuoteFixture::quote($fixture))->toThrow(RuntimeException::class, 'BUSINESS_EXPOSURE_INTEGRITY_FAILED');
})->with(['sha256', 'reservation_id', 'business_id', 'application_id', 'submission_id', 'principal']);

it('enforces the principal grid and parent ownership even for direct database inserts', function (string $change): void {
    $fixture = BusinessQuoteFixture::ready();
    $submission = BusinessApplicationSubmission::factory()->create(['business_application_id' => $fixture['application']->id,
        'business_application_quote_id' => $fixture['application']->current_quote_id]);
    $attributes = ['business_id' => $fixture['audit']['business'], 'business_application_id' => $fixture['application']->id,
        'business_application_submission_id' => $submission->id];
    $attributes = [...$attributes, ...match ($change) {
        'floor' => ['principal' => '2995000'], 'ceiling' => ['principal' => '100005000'], 'grid' => ['principal' => '3000001'],
        'business' => ['business_id' => BusinessProfile::factory()->create()->id],
        'submission' => ['business_application_submission_id' => BusinessApplicationSubmission::factory()->create()->id],
        default => throw new LogicException('Unknown reservation constraint.'),
    }];
    expect(fn () => DB::transaction(fn () => BusinessExposureReservation::factory()->create($attributes)))->toThrow(QueryException::class);
})->with(['floor', 'ceiling', 'grid', 'business', 'submission']);

it('does not silently backfill pre-C3 submissions when the reservation migration runs', function (): void {
    $legacy = BusinessApplicationSubmission::factory()->create();
    $migration = require database_path('migrations/2026_09_26_190340_create_business_exposure_reservations_table.php');
    $migration->down();
    $migration->up();
    expect(BusinessApplicationSubmission::query()->find($legacy->id))->not->toBeNull()
        ->and(BusinessExposureReservation::query()->count())->toBe(0);
});

it('refuses a changed exposure snapshot or insufficient exact room at the reservation write', function (bool $stale): void {
    $fixture = BusinessQuoteFixture::ready();
    $quote = BusinessApplicationQuote::query()->findOrFail($fixture['application']->current_quote_id);
    $payload = $quote->payload;
    if ($stale) {
        $payload['accepted_commitments'] = [['id' => 'missing', 'principal' => '3000000']];
    } else {
        $payload['result']['cash_flow']['remaining_room'] = ['numerator' => '1079999999', 'denominator' => '100'];
    }
    $quote = BusinessApplicationQuote::factory()->create(['business_application_id' => $fixture['application']->id, 'revision' => 2, 'payload' => $payload]);
    $submission = BusinessApplicationSubmission::factory()->create(['business_application_id' => $fixture['application']->id,
        'business_application_quote_id' => $quote->id]);
    expect(fn () => DB::transaction(fn () => app(BusinessExposureStore::class)->reserve($fixture['audit']['business'], $submission->id)))
        ->toThrow(CommandRejection::class, $stale ? 'QUOTE_STALE' : 'EXPOSURE_LIMIT');
    expect(BusinessExposureReservation::query()->count())->toBe(0);
})->with([false, true]);
