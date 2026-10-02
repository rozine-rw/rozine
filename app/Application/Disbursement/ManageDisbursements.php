<?php

declare(strict_types=1);

namespace App\Application\Disbursement;

use App\Application\Disbursement\Contracts\DisbursementStore;
use App\Application\Disbursement\Contracts\PayoutProvider;
use LogicException;
use Throwable;

/**
 * The staff disbursement console (C3 v2 §2e): queue and detail reads, the maker/checker commands,
 * the dedicated step-up exchange, the operation lookup and requery. Requery asks the provider
 * about the SAME durable operation outside every transaction and never sends; its observation
 * then feeds the ordinary reconciliation transaction.
 */
final class ManageDisbursements
{
    public function __construct(private DisbursementStore $store, private PayoutProvider $provider) {}

    /** @return array<string, mixed> */
    public function page(int $userId, ?string $disbursementId, ?string $before, int $limit): array
    {
        return $this->store->page($userId, $disbursementId, $before, $limit);
    }

    /** @return array<string, mixed> */
    public function command(int $userId, string $disbursementId, string $command, int $expectedRevision, string $reason, string $requestId, ?string $stepUpProof = null, bool $independenceDeclared = false): array
    {
        if ($command === 'requery') {
            return $this->requery($userId, $disbursementId, $expectedRevision, $reason, $requestId);
        }

        return $this->store->command($userId, $disbursementId, $command, $expectedRevision, $reason, $requestId, $stepUpProof, $independenceDeclared);
    }

    /** @return array{proof: string, expires_at: string} */
    public function stepUp(int $userId, string $disbursementId, int $expectedRevision, string $intentDigest, string $code): array
    {
        return $this->store->stepUp($userId, $disbursementId, $expectedRevision, $intentDigest, $code);
    }

    /** @return array<string, mixed> */
    public function find(int $userId, string $command, string $requestId): array
    {
        return $this->store->find($userId, $command, $requestId);
    }

    /** @return array<string, mixed> */
    private function requery(int $userId, string $disbursementId, int $expectedRevision, string $reason, string $requestId): array
    {
        $recorded = $this->store->recordedRequery($userId, $disbursementId, $requestId);
        if ($recorded !== null) {
            return $recorded;
        }
        $instruction = $this->store->requeryInstruction($userId, $disbursementId);
        if ($this->store->transactionOpen()) {
            throw new LogicException('DISBURSEMENT_QUERY_TRANSACTION_OPEN: a provider is asked only outside every transaction.');
        }
        try {
            $observed = $this->provider->query($instruction);
        } catch (Throwable) {
            $observed = null;
        }
        $result = $this->store->recordRequery($userId, $disbursementId, $expectedRevision, $reason, $requestId, $observed);
        if ($result['status'] === 'completed') {
            $this->store->reconcile($instruction->intentId);
        }

        return $result;
    }
}
