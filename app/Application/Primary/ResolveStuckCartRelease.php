<?php

declare(strict_types=1);

namespace App\Application\Primary;

use App\Application\Primary\Contracts\CartCheckouts;

/**
 * Retries a stuck whole-cart expiry from the console's Exceptions page (#383 step 4c,
 * `cart.releases.resolve`). It runs the same group expiry the sweep runs, so every line of the cart
 * is released together, never one on its own.
 */
final readonly class ResolveStuckCartRelease
{
    public function __construct(private CartCheckouts $checkouts) {}

    /** @return array<string, mixed> */
    public function handle(int $staffUserId, string $requestId, string $checkoutId): array
    {
        return $this->checkouts->settleStuck($staffUserId, $checkoutId, $requestId);
    }
}
