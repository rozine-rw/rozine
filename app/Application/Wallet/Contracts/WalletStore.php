<?php

declare(strict_types=1);

namespace App\Application\Wallet\Contracts;

use App\Application\Wallet\DepositInstruction;
use App\Application\Wallet\VerifiedDepositEvent;
use Closure;

/**
 * The Investor wallet port. The Party always comes from the authenticated user's current investor
 * authority; no method accepts a Party, wallet or provider reference as authority.
 */
interface WalletStore
{
    /**
     * The caller's wallet facts: ledger-derived buckets, the applicable policy and verified methods,
     * the quote for `amount`, recent deposits with their receipts, history and an opened receipt.
     *
     * @param  array{kind?: string|null, amount?: string|null, movement?: string|null, before?: string|null, receipt?: string|null}  $query
     * @return array<string, mixed>
     */
    public function page(int $userId, ?int $contextRevision, array $query): array;

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

    /**
     * Records a verified provider event once and applies its outcome transition under the wallet
     * and intent locks. Only a matching success on a non-final intent posts the balanced credit.
     *
     * @return array{disposition: string, state: string, credited: bool, replayed: bool}
     */
    public function applyOutcome(VerifiedDepositEvent $event, string $environment): array;

    /**
     * Claims queued dispatches (and, for a provider with safe idempotent sends, dispatches whose
     * send was interrupted) in a short transaction of its own, before any provider is called.
     *
     * @return list<DepositInstruction>
     */
    public function claimDispatches(?string $intentId, int $limit, bool $resendInterrupted, string $environment): array;

    /** Records whether the provider acknowledged a claimed dispatch. The first outcome stands. */
    public function recordDispatch(string $intentId, bool $acknowledged): void;

    /**
     * Runs the callback once the caller's outermost transaction commits; a rollback discards it.
     * With no transaction open it runs now.
     */
    public function afterCommit(Closure $callback): void;

    /** Whether a caller's database transaction is open, so no provider may be called yet. */
    public function transactionOpen(): bool;
}
