<?php

declare(strict_types=1);

namespace App\Events;

use App\Application\Operations\Contracts\ReconciliationVariances;

/**
 * Published after the persistent case commits. The CMA seat can consume this event once its
 * authorised delivery route exists; publishing is not a claim of CMA delivery.
 *
 * @phpstan-import-type Variance from ReconciliationVariances
 */
final readonly class ReconciliationVarianceOpened
{
    /** @param Variance $variance */
    public function __construct(public array $variance) {}
}
