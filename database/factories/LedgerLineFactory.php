<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LedgerLine> */
class LedgerLineFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['entry_id' => LedgerEntry::factory(),
            'account_id' => fn (array $attributes): string => LedgerAccount::factory()->create([
                'wallet_id' => LedgerEntry::query()->whereKey($attributes['entry_id'])->firstOrFail()->wallet_id])->id,
            'direction' => 'debit', 'amount' => '1000'];
    }
}
