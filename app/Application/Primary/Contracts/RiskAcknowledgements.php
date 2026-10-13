<?php

declare(strict_types=1);

namespace App\Application\Primary\Contracts;

/**
 * The Investor's own acknowledgement of the current KYC-R05 risk statements. Checkout admission
 * refuses a new reserve or confirm until the acting Party has acknowledged the current version.
 */
interface RiskAcknowledgements
{
    /**
     * Records the acknowledgement as the `investor.risk.acknowledge` operation under current
     * Investor authority; a version or digest other than the current statements is refused.
     *
     * @return array<string, mixed>
     */
    public function acknowledge(int $userId, int $contextRevision, string $version, string $statementsSha256, string $requestId): array;

    /**
     * The current statements, their version and digest, and whether the acting Investor has
     * acknowledged them, read under current Investor authority.
     *
     * @return array{version: string, statements_sha256: string, statements: list<array{key: string, text: string}>, acknowledged: bool}
     */
    public function read(int $userId, ?int $contextRevision): array;

    /** Whether the Party has acknowledged the statements as they are worded now. */
    public function current(string $partyId): bool;
}
