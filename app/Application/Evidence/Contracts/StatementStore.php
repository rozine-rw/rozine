<?php

declare(strict_types=1);

namespace App\Application\Evidence\Contracts;

/**
 * @phpstan-type Document array{id: string, filename: string, sha256: string, media_type: string, size_bytes: int, received_at: string, extraction: array{id: string, revision: int, parser_version: string, status: string, reason_codes: list<string>, record_count: int|null}}
 * @phpstan-type Manifest array{revision: int, documents: list<Document>}
 * @phpstan-type Original array{filename: string, media_type: string, sha256: string, content: string}
 */
interface StatementStore
{
    /** @return array<string, mixed> */
    public function ingest(int $userId, int $contextRevision, string $businessId, int $expectedRevision, string $filename, string $content, string $requestId): array;

    /** @return Manifest */
    public function get(int $userId, int $contextRevision, string $businessId): array;

    /** @return Original */
    public function read(int $userId, int $contextRevision, string $businessId, string $documentId): array;

    /** @return array<string, mixed> */
    public function findOperation(int $userId, int $contextRevision, string $requestId): array;
}
