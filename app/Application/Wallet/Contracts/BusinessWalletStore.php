<?php

declare(strict_types=1);

namespace App\Application\Wallet\Contracts;

/**
 * The Business servicing wallet port (S4-A1). Every read and command runs under current Business
 * authority: the actor's current Business role and mandate. A deposit records an intent and its
 * queued dispatch only; nothing is credited until a verified provider success.
 */
interface BusinessWalletStore
{
    /**
     * @param  array{kind?: string|null, amount?: string|null, movement?: string|null, before?: string|null, receipt?: string|null}  $query
     * @return array<string, mixed>
     */
    public function page(int $userId, int $contextRevision, string $businessId, array $query): array;

    /**
     * @param  array{currency: string, amount: string}  $amount
     * @return array<string, mixed>
     */
    public function deposit(int $userId, int $contextRevision, string $businessId, string $requestId, array $amount, string $methodId): array;

    /** @return array<string, mixed> */
    public function findDeposit(int $userId, int $contextRevision, string $businessId, string $requestId): array;
}
