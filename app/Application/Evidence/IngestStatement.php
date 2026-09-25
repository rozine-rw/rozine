<?php

declare(strict_types=1);

namespace App\Application\Evidence;

use App\Application\Evidence\Contracts\StatementStore;

final class IngestStatement
{
    public function __construct(private StatementStore $store) {}

    /** @return array<string, mixed> */
    public function handle(int $userId, int $contextRevision, string $businessId, int $expectedRevision, string $filename, string $content, string $requestId): array
    {
        return $this->store->ingest($userId, $contextRevision, $businessId, $expectedRevision, $filename, $content, $requestId);
    }
}
