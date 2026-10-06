<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BusinessApplication;
use App\Models\BusinessApplicationVersion;
use App\Models\Party;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BusinessApplicationVersion> */
class BusinessApplicationVersionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['business_application_id' => BusinessApplication::factory(), 'revision' => 1,
            'snapshot' => function (array $attributes): array {
                $application = BusinessApplication::query()->whereKey($attributes['business_application_id'])->firstOrFail();

                return ['id' => $application->id, 'business_id' => $application->business_id, 'revision' => $application->revision,
                    'status' => $application->status, 'step' => $application->step, 'draft' => $application->draft, 'mandate_version' => $application->mandate_version];
            }, 'actor_party_id' => Party::factory()->verified(), 'actor_user_id' => 1,
            'mandate_version' => 1, 'policy_version' => 'engineering-2026-09-23.4'];
    }
}
