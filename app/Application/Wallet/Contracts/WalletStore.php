<?php

declare(strict_types=1);

namespace App\Application\Wallet\Contracts;

/**
 * The Investor wallet port. The Party always comes from the authenticated user's current investor
 * authority; no method accepts a Party, wallet or provider reference as authority.
 */
interface WalletStore
{
    /**
     * Records a deposit intent and its dispatch outbox row, or journals the refusal. Never credits.
     *
     * @param  array{currency: string, amount: string}  $amount
     * @return array<string, mixed>
     */
    public function deposit(int $userId, int $contextRevision, string $requestId, array $amount, string $methodId): array;

    /**
     * The recorded `wallet.deposit` result for this caller's request, unchanged.
     *
     * @return array<string, mixed>
     */
    public function findDeposit(int $userId, int $contextRevision, string $requestId): array;
}
