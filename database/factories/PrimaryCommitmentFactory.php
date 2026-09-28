<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PrimaryCommitment> */
class PrimaryCommitmentFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['primary_reservation_version_id' => PrimaryReservationVersion::factory()->confirmed()->withCashMovement(),
            'primary_reservation_id' => fn (array $a): string => PrimaryReservationVersion::query()->whereKey($a['primary_reservation_version_id'])->firstOrFail()->primary_reservation_id,
            'operation_id' => fn (array $a): ?string => PrimaryReservationVersion::query()->whereKey($a['primary_reservation_version_id'])->firstOrFail()->operation_id,
            'confirmed_at' => fn (array $a) => PrimaryReservationVersion::query()->whereKey($a['primary_reservation_version_id'])->firstOrFail()->created_at,
            'created_at' => now()->startOfSecond()];
    }
}
