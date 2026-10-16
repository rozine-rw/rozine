<?php

declare(strict_types=1);

namespace App\Application\Primary;

use App\Application\Primary\Contracts\RiskAcknowledgements;

/** The Investor's acknowledgement of the five risk statements before a first investment (KYC-R05). */
final readonly class AcknowledgeInvestorRisks
{
    public function __construct(private RiskAcknowledgements $acknowledgements) {}

    /** @return array{version: string, statements_sha256: string, statements: list<array{key: string, text: string}>, acknowledged: bool} */
    public function read(int $userId, ?int $contextRevision): array
    {
        return $this->acknowledgements->read($userId, $contextRevision);
    }

    /** @return array<string, mixed> */
    public function handle(int $userId, int $contextRevision, string $version, string $statementsSha256, string $requestId): array
    {
        return $this->acknowledgements->acknowledge($userId, $contextRevision, $version, $statementsSha256, $requestId);
    }
}
