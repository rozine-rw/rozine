<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Application\Operations\Contracts\CanonicalJson;
use App\Models\BusinessCreditSnapshot;
use App\Models\BusinessProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusinessCreditSnapshot>
 */
class BusinessCreditSnapshotFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['business_id' => BusinessProfile::factory(), 'actor_user_id' => User::factory(), 'revision' => 1,
            'status' => 'withdrawn', 'source_kind' => 'isolated_alpha', 'source_reference' => 'synthetic:unavailable-credit-facts',
            'facts' => null, 'reason' => 'Synthetic historical source; no borrowing authority.',
            'sha256' => fn (array $attributes): string => hash('sha256', app(CanonicalJson::class)->encode(array_intersect_key($attributes,
                array_flip(['business_id', 'revision', 'status', 'source_kind', 'source_reference', 'facts']))))];
    }
}
