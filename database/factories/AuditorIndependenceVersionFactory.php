<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AuditorIndependenceReview;
use App\Models\AuditorIndependenceVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AuditorIndependenceVersion> */
class AuditorIndependenceVersionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['review_id' => AuditorIndependenceReview::factory(), 'revision' => 1,
            'snapshot' => function (array $attributes): array {
                $review = AuditorIndependenceReview::query()->whereKey($attributes['review_id'])->firstOrFail();

                return ['id' => $review->id, 'business_id' => $review->business_id, 'party_id' => $review->party_id,
                    'revision' => $review->revision, 'state' => $review->state];
            }, 'actor_user_id' => User::factory(), 'reason' => 'Synthetic independence review.', 'policy_version' => 'engineering-2026-09-23.4'];
    }
}
