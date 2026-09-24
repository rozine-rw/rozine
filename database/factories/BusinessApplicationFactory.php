<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Business\ApplicationDraft;
use App\Models\BusinessApplication;
use App\Models\BusinessProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BusinessApplication> */
class BusinessApplicationFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['business_id' => BusinessProfile::factory(), 'revision' => 1, 'status' => 'draft', 'step' => 'business',
            'draft' => (new ApplicationDraft)->empty(), 'mandate_version' => 1];
    }
}
