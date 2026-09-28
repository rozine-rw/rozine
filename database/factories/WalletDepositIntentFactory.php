<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Application\Operations\Contracts\CanonicalJson;
use App\Models\CommandOperation;
use App\Models\DepositPolicy;
use App\Models\InvestorFundingMethod;
use App\Models\InvestorWallet;
use App\Models\WalletDepositIntent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<WalletDepositIntent> */
class WalletDepositIntentFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $reference = 'synthetic-'.Str::lower(Str::random(24));

        return ['wallet_id' => InvestorWallet::factory(),
            'party_id' => fn (array $attributes): string => InvestorWallet::query()->whereKey($attributes['wallet_id'])->firstOrFail()->party_id,
            'operation_id' => fn (): string => CommandOperation::factory()->create()->id, 'request_id' => (string) Str::uuid(),
            'method_id' => fn (array $attributes): string => InvestorFundingMethod::factory()->create(['party_id' => $attributes['party_id']])->id,
            'policy_id' => DepositPolicy::factory(), 'amount' => '5000', 'fee' => '0', 'credited' => '5000', 'currency' => 'RWF',
            'provider' => 'synthetic', 'provider_reference' => $reference, 'provider_reference_sha256' => hash('sha256', $reference),
            'payload' => ['source' => 'unsupported-fixture'],
            'sha256' => fn (array $attributes): string => hash('sha256', app(CanonicalJson::class)->encode($attributes['payload']))];
    }
}
