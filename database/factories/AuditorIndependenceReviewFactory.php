<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AuditorIndependenceReview;
use App\Models\BusinessProfile;
use App\Models\Party;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AuditorIndependenceReview> */
class AuditorIndependenceReviewFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['business_id' => BusinessProfile::factory(), 'party_id' => Party::factory()->verified(), 'revision' => 1,
            'state' => ['facts' => ['financial_interest' => false, 'current_role_tie' => false, 'role_tie_ended_at' => null,
                'family_or_business_conflict' => false, 'unresolved_conflict' => true],
                'mandate_version' => 1, 'checked_at' => now('UTC')->format('Y-m-d\TH:i:s\Z'), 'evidence_reference' => 'synthetic:needs-review']];
    }
}
