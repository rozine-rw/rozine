<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BusinessProfile;
use App\Models\Party;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BusinessProfile> */
class BusinessProfileFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['entity_party_id' => Party::factory()->verified(), 'entity_kind' => 'person', 'revision' => 1, 'mandate_version' => 1,
            'profile' => ['name' => 'Synthetic business', 'company_code' => null, 'industry' => 'retail', 'district' => 'Gasabo', 'established_year' => 2020]];
    }
}
