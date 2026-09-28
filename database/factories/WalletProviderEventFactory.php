<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\WalletDepositIntent;
use App\Models\WalletProviderEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<WalletProviderEvent> */
class WalletProviderEventFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['provider' => 'synthetic', 'provider_event_id' => 'synthetic-event-'.Str::lower(Str::random(16)),
            'intent_id' => WalletDepositIntent::factory(), 'content_sha256' => hash('sha256', Str::random(32)), 'state' => 'pending',
            'amount' => fn (array $attributes): string => WalletDepositIntent::query()->whereKey($attributes['intent_id'])->firstOrFail()->amount,
            'currency' => 'RWF', 'environment' => 'testing', 'observed_at' => now(), 'disposition' => 'applied', 'evidence' => ['source' => 'unsupported-fixture']];
    }
}
