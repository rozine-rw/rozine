<?php

declare(strict_types=1);

namespace App\Application\Wallet\Contracts;

use App\Application\Wallet\DepositInstruction;
use App\Application\Wallet\VerifiedDepositEvent;
use Closure;

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

    /**
     * `repayment.pay`: debits the Business wallet by exactly the quoted option of a servicing note.
     *
     * @param  array{currency: string, amount: string}  $quotedTotal
     * @return array<string, mixed>
     */
    public function pay(int $userId, int $contextRevision, string $businessId, string $requestId, string $noteId, string $option,
        int $expectedRevision, array $quotedTotal): array;

    /** @return array<string, mixed> */
    public function findRepayment(int $userId, int $contextRevision, string $businessId, string $requestId): array;

    /**
     * Claims queued Business deposit dispatches, committing each claim before any provider call.
     *
     * @return list<DepositInstruction>
     */
    public function claimDispatches(?string $intentId, int $limit, bool $resendInterrupted, string $environment): array;

    public function recordDispatch(string $intentId, bool $acknowledged): void;

    /**
     * Applies an authenticated provider event to the Business intent its reference is registered to.
     * A reference registered to no Business intent is refused, never applied elsewhere.
     *
     * @return array{disposition: string, state: string, credited: bool, replayed: bool}
     */
    public function applyOutcome(VerifiedDepositEvent $event, string $environment): array;

    public function afterCommit(Closure $callback): void;

    public function transactionOpen(): bool;
}
