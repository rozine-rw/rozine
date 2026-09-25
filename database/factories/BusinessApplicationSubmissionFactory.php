<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Application\Operations\Contracts\CanonicalJson;
use App\Models\BusinessApplicationQuote;
use App\Models\BusinessApplicationSubmission;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BusinessApplicationSubmission> */
class BusinessApplicationSubmissionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['business_application_quote_id' => BusinessApplicationQuote::factory(),
            'business_application_id' => fn (array $attributes): string => BusinessApplicationQuote::query()->whereKey($attributes['business_application_quote_id'])->firstOrFail()->business_application_id,
            'revision' => 1,
            'binding_sha256' => hash('sha256', 'unsupported-fixture'), 'payload' => ['source' => 'unsupported-fixture'],
            'sha256' => fn (array $attributes): string => hash('sha256', app(CanonicalJson::class)->encode($attributes['payload']))];
    }
}
