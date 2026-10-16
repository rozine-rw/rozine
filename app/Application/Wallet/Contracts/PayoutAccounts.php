<?php

declare(strict_types=1);

namespace App\Application\Wallet\Contracts;

/**
 * Linking and unlinking the Investor's own payout accounts under current Investor authority. Each is
 * a journalled command answering the operation result shape: a refusal comes back with its code
 * and field errors instead of being thrown. Unlinking revokes the account one way; it stays on
 * record and on every deposit or withdrawal that used it.
 */
interface PayoutAccounts
{
    /** Whether an account can be linked at all here: a verifier exists. */
    public function linkable(): bool;

    /**
     * @param  array{type?: mixed, network?: mixed, bank?: mixed, number?: mixed}  $input
     * @return array<string, mixed>
     */
    public function link(int $userId, string $requestId, array $input): array;

    /** @return array<string, mixed> */
    public function unlink(int $userId, string $requestId, string $methodId): array;
}
