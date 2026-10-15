<?php

declare(strict_types=1);

namespace App\Application\Wallet\Contracts;

use App\Application\Wallet\VerifiedWithdrawalEvent;
use App\Application\Wallet\WithdrawalInstruction;
use Closure;

/**
 * Business wallet withdrawals (BRS v2.0 APP-B01, LED-R04): to the Business's own verified account
 * only, from its available balance, under current Business authority and the mandate's
 * `business.wallet.withdraw` permission. A withdrawal records its hold and its queued dispatch;
 * nothing leaves until a verified provider success, and a verified failure returns the cash.
 */
interface BusinessWithdrawals
{
    /**
     * @param  array{currency: string, amount: string}  $amount
     * @return array<string, mixed>
     */
    public function withdraw(int $userId, int $contextRevision, string $businessId, string $requestId, array $amount, string $methodId): array;

    /** @return array<string, mixed> */
    public function findWithdrawal(int $userId, int $contextRevision, string $businessId, string $requestId): array;

    /**
     * What the Business wallet page shows about withdrawals, read inside the caller's Business
     * authority: the terms (null when no provider can pay) with a quote when `$quoting`, the latest
     * withdrawals, the cash on its way out, and the opened withdrawal when `$receipt` names one of
     * this wallet's.
     *
     * @return array{withdrawal: array<string, mixed>|null, withdrawals: list<array<string, mixed>>,
     *     pending: array{currency: string, amount: string}, receipt: array{type: string, withdrawal: array<string, mixed>}|null}
     */
    public function view(?string $walletId, bool $hasMethod, bool $quoting, ?string $amount, ?string $receipt): array;

    /** Whether a provider can pay a Business withdrawal here at all. */
    public function offered(): bool;

    /**
     * Applies an authenticated provider outcome to the Business withdrawal its reference names.
     *
     * @return array{disposition: string, state: string, settled: bool, replayed: bool}
     */
    public function applyOutcome(VerifiedWithdrawalEvent $event, string $environment): array;

    /** @return list<WithdrawalInstruction> */
    public function claim(?string $withdrawalId, int $limit, bool $resendInterrupted, string $environment): array;

    public function claimable(bool $resendInterrupted): int;

    public function recordDispatch(string $withdrawalId, bool $acknowledged): void;

    public function afterCommit(Closure $callback): void;

    public function transactionOpen(): bool;
}
