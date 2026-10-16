<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Application\Operations\Contracts\CanonicalJson;
use App\Models\BusinessApplication;
use App\Models\BusinessApplicationSubmission;
use App\Models\BusinessExposureReservation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<BusinessExposureReservation> */
class BusinessExposureReservationFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'business_application_submission_id' => BusinessApplicationSubmission::factory(),
            'business_application_id' => fn (array $attributes): string => BusinessApplicationSubmission::query()
                ->whereKey($attributes['business_application_submission_id'])->firstOrFail()->business_application_id,
            'business_id' => fn (array $attributes): string => BusinessApplication::query()->whereKey($attributes['business_application_id'])->firstOrFail()->business_id,
            'principal' => '3000000',
            'payload' => fn (array $attributes): array => ['reservation_id' => $attributes['id'], 'business_id' => $attributes['business_id'],
                'application_id' => $attributes['business_application_id'], 'submission_id' => $attributes['business_application_submission_id'],
                'principal' => $attributes['principal'], 'source' => 'unsupported-fixture'],
            'sha256' => fn (array $attributes): string => hash('sha256', app(CanonicalJson::class)->encode($attributes['payload'])),
        ];
    }
}
