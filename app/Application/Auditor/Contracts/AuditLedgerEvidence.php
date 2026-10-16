<?php

declare(strict_types=1);

namespace App\Application\Auditor\Contracts;

/**
 * Called only inside AuditReportStore's current accepted-assignment authority and report lock.
 *
 * @phpstan-import-type Document from \App\Application\Evidence\Contracts\StatementStore
 * @phpstan-import-type Original from \App\Application\Evidence\Contracts\StatementStore
 *
 * @phpstan-type Reference array{id: string, revision: int, sha256: string, replaces: string|null}
 */
interface AuditLedgerEvidence
{
    /**
     * @param  list<Reference>  $references
     * @return Reference
     */
    public function retain(string $reportId, int $reportRevision, string $partyId, int $userId, array $references, string $filename, string $content, ?string $replaces): array;

    /**
     * @param  list<Reference>  $references
     * @return list<Document>
     */
    public function documents(string $reportId, array $references): array;

    /** @return Original */
    public function read(string $reportId, string $documentId): array;
}
