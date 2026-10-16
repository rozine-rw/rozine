<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Application\Operations\Contracts\CanonicalJson;
use App\Models\BusinessApplicationRelease;
use App\Models\BusinessExposureReservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<BusinessApplicationRelease> */
class BusinessApplicationReleaseFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['id' => (string) Str::ulid(), 'exposure_reservation_id' => BusinessExposureReservation::factory(),
            'business_application_id' => fn (array $a): string => BusinessExposureReservation::query()->whereKey($a['exposure_reservation_id'])->firstOrFail()->business_application_id,
            'business_id' => fn (array $a): string => BusinessExposureReservation::query()->whereKey($a['exposure_reservation_id'])->firstOrFail()->business_id,
            'actor_user_id' => User::factory(),

            'payload' => ['source' => 'unsupported-fixture'],
            'sha256' => fn (array $a): string => hash('sha256', app(CanonicalJson::class)->encode($a['payload']))];
    }
}
