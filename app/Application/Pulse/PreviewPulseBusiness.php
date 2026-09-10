<?php

declare(strict_types=1);

namespace App\Application\Pulse;

use App\Domain\Pulse\PulseSector;
use App\Domain\Pulse\PulseUnderwriting;

final class PreviewPulseBusiness
{
    /** @return array<string, mixed> */
    public function handle(
        int $annualRevenue,
        int $annualCosts,
        PulseSector $sector,
        int $registeredYear,
        int $termMonths,
        int $currentYear,
    ): array {
        return PulseUnderwriting::size(
            $annualRevenue,
            $annualCosts,
            $sector,
            $registeredYear,
            $termMonths,
            $currentYear,
        );
    }
}
