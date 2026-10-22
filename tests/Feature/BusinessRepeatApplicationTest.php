<?php

declare(strict_types=1);

use App\Application\Business\CreateBusinessApplication;
use App\Application\Business\GetBusinessHome;
use App\Models\BusinessApplication;
use App\Models\BusinessApplicationQuote;
use App\Models\BusinessApplicationSubmission;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\PrimaryHoldingFixture;
use Tests\Support\PrimaryReservationFixture;

/**
 * Submits another application for the Business straight at the database boundary.
 */
function submitAnotherApplication(string $businessId): BusinessApplication
{
    return DB::transaction(function () use ($businessId): BusinessApplication {
        $another = BusinessApplication::query()->where('business_id', $businessId)->where('status', 'draft')->first()
            ?? BusinessApplication::factory()->create(['business_id' => $businessId]);
        $quote = BusinessApplicationQuote::factory()->create(['business_application_id' => $another->id]);
        $submission = BusinessApplicationSubmission::factory()->create(['business_application_id' => $another->id, 'business_application_quote_id' => $quote->id]);
        $another->forceFill(['status' => 'submitted', 'step' => 'submitted', 'current_quote_id' => $quote->id, 'current_submission_id' => $submission->id])->save();

        return $another->refresh();
    });
}

it('lets a Business apply again once its earlier raise has funded, and only then', function (): void {
    $sealed = null;
    $campaign = PrimaryReservationFixture::campaign(sealing: function (array $fixture) use (&$sealed): mixed {
        $sealed = $fixture;

        return $fixture['code'];
    });
    $user = $sealed['audit']['authority']['users'][0];
    $businessId = $campaign->business_id;

    expect(app(CreateBusinessApplication::class)->handle($user->id, 1, $businessId, 0, (string) Str::uuid())['code'])->toBe('APPLICATION_PENDING_REVIEW')
        ->and(app(GetBusinessHome::class)->handle($user->id, 1, $businessId)['entry']['allowed_actions'])->toBe([])
        ->and(fn () => submitAnotherApplication($businessId))->toThrow(QueryException::class, 'A business may have only one pending application');

    PrimaryHoldingFixture::committed(live: $campaign->id);

    expect(app(GetBusinessHome::class)->handle($user->id, 1, $businessId)['entry']['allowed_actions'])->toBe(['application.create'])
        ->and(app(CreateBusinessApplication::class)->handle($user->id, 1, $businessId, 0, (string) Str::uuid())['code'])->toBe('APPLICATION_CREATED');
    $repeat = submitAnotherApplication($businessId);

    expect($repeat->status)->toBe('submitted')
        ->and(BusinessApplication::query()->where('business_id', $businessId)->where('status', 'submitted')->count())->toBe(2)
        ->and(app(GetBusinessHome::class)->handle($user->id, 1, $businessId)['entry'])->toMatchArray(['allowed_actions' => [],
            'application' => ['id' => $repeat->id, 'status' => 'submitted', 'step' => 'submitted', 'revision' => $repeat->revision]]);
});

it('refuses rollback once a Business holds a repeat submission, and restores the unique index otherwise', function (): void {
    $migration = require database_path('migrations/2026_10_22_130000_allow_repeat_business_applications.php');
    $campaign = PrimaryReservationFixture::campaign();
    PrimaryHoldingFixture::committed(live: $campaign->id);

    $migration->down();
    $migration->up();
    submitAnotherApplication($campaign->business_id);

    expect(fn () => $migration->down())->toThrow(QueryException::class, 'Repeat business applications require a forward migration; rollback is refused');
});
