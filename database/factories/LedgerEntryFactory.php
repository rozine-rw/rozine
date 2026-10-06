<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Application\Operations\Contracts\CanonicalJson;
use App\Models\InvestorWallet;
use App\Models\LedgerEntry;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * An entry row alone never commits: it must be followed by balanced lines in the same transaction.
 *
 * @extends Factory<LedgerEntry>
 */
class LedgerEntryFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['wallet_id' => InvestorWallet::factory(), 'kind' => 'deposit_credit', 'source_type' => 'wallet_deposit_intent',
            'source_id' => strtolower((string) Str::ulid()), 'currency' => 'RWF', 'payload' => ['source' => 'unsupported-fixture'],
            'sha256' => fn (array $attributes): string => hash('sha256', app(CanonicalJson::class)->encode($attributes['payload']))];
    }
}
