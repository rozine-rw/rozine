<?php

declare(strict_types=1);

namespace App\Application\Disbursement\Contracts;

use App\Application\Disbursement\PayoutInstruction;
use App\Application\Disbursement\VerifiedPayoutEvent;

/**
 * Disbursement persistence and its locked transactions. Every staff method checks current staff
 * authority before any record detail; every effect runs in one transaction in the agreed lock
 * order, and nothing here ever calls the payout provider.
 */
interface DisbursementStore
{
    /** @return array<string, mixed> the queue page, and the opened disbursement when one is named */
    public function page(int $userId, ?string $disbursementId, ?string $before, int $limit): array;

    /**
     * authorize, approve, reject, hold or release_hold through the operation journal.
     *
     * @return array<string, mixed>
     */
    public function command(int $userId, string $disbursementId, string $command, int $expectedRevision, string $reason, string $requestId, ?string $stepUpProof): array;

    /** @return array{proof: string, expires_at: string} */
    public function stepUp(int $userId, string $disbursementId, int $expectedRevision, string $intentDigest, string $code): array;

    /**
     * An already recorded requery under this key, or null when it has not been recorded.
     *
     * @return array<string, mixed>|null
     */
    public function recordedRequery(int $userId, string $disbursementId, string $requestId): ?array;

    /** Authorizes a requery and returns the same durable operation to ask about. */
    public function requeryInstruction(int $userId, string $disbursementId): PayoutInstruction;

    /** @return array<string, mixed> */
    public function recordRequery(int $userId, string $disbursementId, int $expectedRevision, string $reason, string $requestId, ?VerifiedPayoutEvent $observed): array;

    /** @return array<string, mixed> */
    public function find(int $userId, string $command, string $requestId): array;

    public function transactionOpen(): bool;

    /** @return array{opened: int, known: int} */
    public function openFunded(int $limit): array;

    /**
     * Claims queued intents after a fresh locked recheck, and interrupted claims that may be resent.
     *
     * @return list<array{instruction: PayoutInstruction, source: 'dispatch'|'recovery'}>
     */
    public function claim(?string $intentId, int $limit, bool $idempotentSends): array;

    public function recordDispatch(string $intentId, bool $sent): void;

    /**
     * Dispatched intents with no closing yet, each with its query call already recorded.
     *
     * @return list<PayoutInstruction>
     */
    public function queryDue(?string $intentId, int $limit): array;

    /**
     * Records one authenticated observation in its own transaction and returns its intent id and
     * disposition. A callback names its intent by provider reference; a query already knows it.
     *
     * @param  'callback'|'query'  $source
     * @return array{intent_id: string, disposition: string}
     */
    public function observe(VerifiedPayoutEvent $event, string $source, ?string $intentId = null): array;

    /** The deterministic reconciliation transaction; returns its decision. */
    public function reconcile(string $intentId): string;
}
