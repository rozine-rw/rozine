<?php

declare(strict_types=1);

namespace App\Application\Wallet\Contracts;

use App\Application\Wallet\DepositInstruction;
use App\Application\Wallet\VerifiedDepositEvent;
use App\Application\Wallet\VerifiedWithdrawalEvent;
use App\Application\Wallet\WithdrawalInstruction;
use Closure;

/**
 * The Investor wallet port. The Party always comes from the authenticated user's current investor
 * authority; no method accepts a Party, wallet or provider reference as authority.
 */
interface WalletStore
{
    /** The wallet history periods, each from a Kigali midnight: today, the last 7 or 30 days, or all. */
    public const array HISTORY_PERIODS = ['today', '7d', '30d', 'all'];

    /**
     * The caller's wallet facts: ledger-derived buckets, the applicable policy and verified methods,
     * the quote for `amount` under the chosen `kind`, recent deposits and withdrawals with their
     * receipts, history and an opened receipt.
     *
     * @param  array{kind?: string|null, amount?: string|null, movement?: string|null, period?: string|null, before?: string|null, receipt?: string|null}  $query
     * @return array<string, mixed>
     */
    public function page(int $userId, ?int $contextRevision, array $query): array;

    /**
     * The caller's wallet statement: every credited deposit, paid withdrawal and internal transfer recorded
     * between `from` and `to` (inclusive calendar days, either open), newest first, each with the
     * reference its receipt carries. An Investor with no wallet gets an empty statement.
     *
     * @return array{generated_at: string, from: string|null, to: string|null, rows: list<array{occurred_at: string, movement: string, kind: string, deal_name: string|null, counterparty: string|null, direction: string, amount: array{currency: string, amount: string}, reference: string}>}
     */
    public function statement(int $userId, ?int $contextRevision, ?string $from, ?string $to): array;

    /**
     * The calendar months (first day, `Y-m-d`) in which the caller's wallet recorded a credited
     * deposit, a paid withdrawal or an internal transfer, newest first: the months a statement has rows for.
     *
     * @return list<string>
     */
    public function statementMonths(int $userId, ?int $contextRevision): array;

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
     * send was interrupted) in a short transaction of its own, before any provider is called. At
     * most $limit in all, queued ones first.
     *
     * @return list<DepositInstruction>
     */
    public function claimDispatches(?string $intentId, int $limit, bool $resendInterrupted, string $environment): array;

    /** How many dispatches a claim with no limit would take now, so a bounded run can report what it left. */
    public function claimableDispatches(bool $resendInterrupted): int;

    /** Records whether the provider acknowledged a claimed dispatch. The first outcome stands. */
    public function recordDispatch(string $intentId, bool $acknowledged): void;

    /**
     * Records a withdrawal to one of the caller's verified accounts, the hold that moves its amount
     * out of the available balance, and its dispatch outbox row, or journals the refusal. Never pays out.
     *
     * @param  array{currency: string, amount: string}  $amount
     * @return array<string, mixed>
     */
    public function withdraw(int $userId, int $contextRevision, string $requestId, array $amount, string $methodId): array;

    /**
     * The recorded `wallet.withdraw` result for this caller's request, unchanged.
     *
     * @return array<string, mixed>
     */
    public function findWithdrawal(int $userId, int $contextRevision, string $requestId): array;

    /**
     * Records a verified withdrawal outcome once and applies its transition under the wallet and
     * withdrawal locks. An applied success pays the held amount out; an applied failure returns it.
     *
     * @return array{disposition: string, state: string, settled: bool, replayed: bool}
     */
    public function applyWithdrawalOutcome(VerifiedWithdrawalEvent $event, string $environment): array;

    /**
     * Claims queued withdrawal dispatches (and interrupted ones, for a provider with safe idempotent
     * sends) in a short transaction of its own, before any provider is called.
     *
     * @return list<WithdrawalInstruction>
     */
    public function claimWithdrawals(?string $withdrawalId, int $limit, bool $resendInterrupted, string $environment): array;

    /** How many withdrawal dispatches a claim with no limit would take now. */
    public function claimableWithdrawals(bool $resendInterrupted): int;

    /** Records whether the provider acknowledged a claimed withdrawal dispatch. The first outcome stands. */
    public function recordWithdrawalDispatch(string $withdrawalId, bool $acknowledged): void;

    /**
     * Runs the callback once the caller's outermost transaction commits; a rollback discards it.
     * With no transaction open it runs now.
     */
    public function afterCommit(Closure $callback): void;

    /** Whether a caller's database transaction is open, so no provider may be called yet. */
    public function transactionOpen(): bool;
}
