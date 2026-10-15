<?php

declare(strict_types=1);

namespace App\Application\Wallet\Contracts;

/**
 * Linking and unlinking the Business's own deposit and withdrawal accounts under current Business
 * authority. Each is a journalled command answering the operation result shape: a refusal comes back
 * with its code and field errors instead of being thrown. Unlinking revokes the account one way; it
 * stays on record and on every deposit or withdrawal that used it.
 */
interface BusinessPayoutAccounts
{
    /** Whether an account can be linked at all here: a verifier exists and synthetic Business funding is on. */
    public function linkable(): bool;

    /**
     * @param  array{type?: mixed, network?: mixed, bank?: mixed, number?: mixed}  $input
     * @return array<string, mixed>
     */
    public function link(int $userId, int $contextRevision, string $businessId, string $requestId, array $input): array;

    /** @return array<string, mixed> */
    public function unlink(int $userId, int $contextRevision, string $businessId, string $requestId, string $methodId): array;
}
