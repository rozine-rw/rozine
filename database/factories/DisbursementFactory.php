<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Application\Operations\Contracts\CanonicalJson;
use App\Models\Disbursement;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * A synthetic funding snapshot for schema tests. It is not funding authority: a disbursement
 * opened through the application reads its facts from the `FundedCampaigns` port.
 *
 * @extends Factory<Disbursement>
 */
class DisbursementFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['id' => strtolower((string) Str::ulid()), 'business_campaign_id' => strtolower((string) Str::ulid()),
            'business_id' => strtolower((string) Str::ulid()), 'exposure_reservation_id' => strtolower((string) Str::ulid()),
            'amount' => '3000000', 'currency' => 'RWF', 'commitments_digest' => hash('sha256', Str::random(16)), 'commitment_count' => 2,
            'term_months' => 12, 'funded_at' => now('UTC')->startOfSecond(), 'environment' => 'testing',
            'payload' => ['source' => 'synthetic-schema-fixture'],
            'sha256' => fn (array $a): string => hash('sha256', app(CanonicalJson::class)->encode($a['payload']))];
    }
}
