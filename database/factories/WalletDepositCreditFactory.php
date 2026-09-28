<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Application\Operations\Contracts\CanonicalJson;
use App\Models\LedgerEntry;
use App\Models\WalletDepositCredit;
use App\Models\WalletDepositIntent;
use App\Models\WalletProviderEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Binds an applied success and an entry row; the entry still needs balanced lines to commit.
 *
 * @extends Factory<WalletDepositCredit>
 */
class WalletDepositCreditFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $intent = fn (array $attributes): WalletDepositIntent => WalletDepositIntent::query()->whereKey($attributes['intent_id'])->firstOrFail();

        return ['intent_id' => WalletDepositIntent::factory(),
            'wallet_id' => fn (array $attributes): string => $intent($attributes)->wallet_id,
            'ledger_entry_id' => fn (array $attributes): string => LedgerEntry::factory()->create(['wallet_id' => $attributes['wallet_id'], 'source_id' => $attributes['intent_id']])->id,
            'provider_event_id' => fn (array $attributes): string => WalletProviderEvent::factory()->create(['intent_id' => $attributes['intent_id'], 'state' => 'succeeded'])->id,
            'operation_id' => fn (array $attributes): string => $intent($attributes)->operation_id,
            'request_id' => fn (array $attributes): string => $intent($attributes)->request_id,
            'amount' => fn (array $attributes): string => $intent($attributes)->credited,
            'payload' => ['source' => 'unsupported-fixture'],
            'sha256' => fn (array $attributes): string => hash('sha256', app(CanonicalJson::class)->encode($attributes['payload']))];
    }
}
