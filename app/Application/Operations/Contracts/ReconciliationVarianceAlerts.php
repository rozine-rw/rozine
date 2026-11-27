<?php

declare(strict_types=1);

namespace App\Application\Operations\Contracts;

/** @phpstan-import-type Variance from ReconciliationVariances */
interface ReconciliationVarianceAlerts
{
    /** @param Variance $variance */
    public function opened(array $variance): void;
}
