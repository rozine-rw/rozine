<?php

declare(strict_types=1);

use App\Application\Operations\Contracts\CanonicalJson;
use App\Infrastructure\Business\RetainedFundedCampaignFacts;
use App\Models\BusinessApplicationQuote;
use App\Models\BusinessApplicationRelease;
use App\Models\BusinessApplicationSubmission;
use App\Models\BusinessExposureReservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Support\PrimaryHoldingFixture;

beforeEach(fn () => $this->freezeSecond());

it('refuses corrupted original exposure acceptance digests before projecting funded facts', function (string $source): void {
    ['campaign' => $campaign] = PrimaryHoldingFixture::committed();
    $projection = app(RetainedFundedCampaignFacts::class);
    expect($projection->find($campaign->id)->exposureReservationId)->toBe($campaign->exposure_reservation_id);
    $exposure = BusinessExposureReservation::query()->whereKey($campaign->exposure_reservation_id)->sole();
    [$record, $trigger] = match ($source) {
        'exposure' => [$exposure, 'business_exposure_reservations_protected'],
        'submission' => [BusinessApplicationSubmission::query()->whereKey($exposure->business_application_submission_id)->sole(), 'business_application_submissions_immutable'],
        'quote' => [BusinessApplicationQuote::query()->whereKey($exposure->payload['quote_id'])->sole(), 'business_application_quotes_immutable'],
        'release' => [BusinessApplicationRelease::query()->whereKey($campaign->business_application_release_id)->sole(), 'business_application_releases_protected'],
        default => throw new InvalidArgumentException('Unknown exposure source.'),
    };
    DB::statement('ALTER TABLE '.$record->getTable().' DISABLE TRIGGER '.$trigger);
    try {
        $record->forceFill(['sha256' => str_repeat('0', 64)])->save();
    } finally {
        DB::statement('ALTER TABLE '.$record->getTable().' ENABLE TRIGGER '.$trigger);
    }
    expect(fn () => $projection->find($campaign->id))->toThrow(RuntimeException::class, 'BUSINESS_EXPOSURE_INTEGRITY_FAILED');
})->with(['exposure', 'submission', 'quote', 'release']);

it('refuses digest-consistent exposure envelopes detached from original acceptance', function (string $field): void {
    ['campaign' => $campaign] = PrimaryHoldingFixture::committed();
    $exposure = BusinessExposureReservation::query()->whereKey($campaign->exposure_reservation_id)->sole();
    $payload = $exposure->payload;
    $payload[$field] = 'foreign';
    DB::statement('ALTER TABLE business_exposure_reservations DISABLE TRIGGER business_exposure_reservations_protected');
    try {
        $exposure->forceFill(['payload' => $payload, 'sha256' => hash('sha256', app(CanonicalJson::class)->encode($payload))])->save();
    } finally {
        DB::statement('ALTER TABLE business_exposure_reservations ENABLE TRIGGER business_exposure_reservations_protected');
    }
    expect(fn () => app(RetainedFundedCampaignFacts::class)->find($campaign->id))->toThrow(RuntimeException::class, 'BUSINESS_EXPOSURE_INTEGRITY_FAILED');
})->with(['reservation_id', 'business_id', 'application_id', 'submission_id', 'submission_sha256', 'quote_id', 'quote_sha256', 'principal', 'accepted_at', 'policy_version', 'extra']);

it('refuses native acceptance rows detached from their authenticated envelopes', function (string $damage): void {
    ['campaign' => $campaign] = PrimaryHoldingFixture::committed();
    $exposure = BusinessExposureReservation::query()->whereKey($campaign->exposure_reservation_id)->sole();
    [$record, $trigger, $field, $value] = match ($damage) {
        'exposure_principal' => [$exposure, 'business_exposure_reservations_protected', 'principal', (string) ((int) $exposure->principal + 5000)],
        'release_actor' => [BusinessApplicationRelease::query()->whereKey($campaign->business_application_release_id)->sole(), 'business_application_releases_protected', 'actor_user_id', User::factory()->create()->id],
        'submission_revision' => [BusinessApplicationSubmission::query()->whereKey($exposure->business_application_submission_id)->sole(), 'business_application_submissions_immutable', 'revision', 999],
        'submission_agreement' => [BusinessApplicationSubmission::query()->whereKey($exposure->business_application_submission_id)->sole(), 'business_application_submissions_immutable', 'binding_sha256', str_repeat('0', 64)],
        'quote_revision' => [BusinessApplicationQuote::query()->whereKey($exposure->payload['quote_id'])->sole(), 'business_application_quotes_immutable', 'revision', 999],
        default => throw new InvalidArgumentException('Unknown acceptance row.'),
    };
    DB::statement('ALTER TABLE '.$record->getTable().' DISABLE TRIGGER '.$trigger);
    try {
        $record->forceFill([$field => $value])->save();
    } finally {
        DB::statement('ALTER TABLE '.$record->getTable().' ENABLE TRIGGER '.$trigger);
    }
    expect(fn () => app(RetainedFundedCampaignFacts::class)->find($campaign->id))->toThrow(RuntimeException::class, 'BUSINESS_EXPOSURE_INTEGRITY_FAILED');
})->with(['exposure_principal', 'release_actor', 'submission_revision', 'submission_agreement', 'quote_revision']);

it('refuses absent malformed or substituted retained release bindings', function (mixed $binding): void {
    ['campaign' => $campaign] = PrimaryHoldingFixture::committed();
    $release = BusinessApplicationRelease::query()->whereKey($campaign->business_application_release_id)->sole();
    $payload = [...$release->payload, 'binding' => $binding];
    DB::statement('ALTER TABLE business_application_releases DISABLE TRIGGER business_application_releases_protected');
    try {
        $release->forceFill(['payload' => $payload, 'sha256' => hash('sha256', app(CanonicalJson::class)->encode($payload))])->save();
    } finally {
        DB::statement('ALTER TABLE business_application_releases ENABLE TRIGGER business_application_releases_protected');
    }
    expect(fn () => app(RetainedFundedCampaignFacts::class)->find($campaign->id))->toThrow(RuntimeException::class, 'BUSINESS_EXPOSURE_INTEGRITY_FAILED');
})->with([[null], ['foreign'], [[]], [['report' => ['digest' => 'foreign']]]]);
