<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Auditor\AccreditationProfile;
use App\Domain\Auditor\AuditorStanding;
use App\Models\AuditorProfile;
use App\Models\Party;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AuditorProfile> */
class AuditorProfileFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['party_id' => Party::factory()->verified(), 'revision' => 1,
            'state' => (new AccreditationProfile(new AuditorStanding))->empty()];
    }
}
