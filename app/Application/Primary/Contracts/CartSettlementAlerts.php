<?php

declare(strict_types=1);

namespace App\Application\Primary\Contracts;

/**
 * Tells the staff who may resolve it that a held cart checkout could not be expired (#383 step 4c,
 * Erastus 1 Nov 2026: whoever holds an admin role, not one named person). Sent once, on the first
 * failure, after the failure is recorded; a failed delivery never stops the sweep.
 */
interface CartSettlementAlerts
{
    public function stuck(string $checkoutId, string $total, string $reasonCode): void;
}
