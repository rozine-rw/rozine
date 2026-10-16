<?php

declare(strict_types=1);

namespace App\Application\Evidence;

use App\Application\Evidence\Contracts\StatementStore;

/** @phpstan-import-type Original from StatementStore */
final class ReadStatementOriginal
{
    public function __construct(private StatementStore $store) {}

    /** @return Original */
    public function handle(int $userId, int $contextRevision, string $businessId, string $documentId): array
    {
        return $this->store->read($userId, $contextRevision, $businessId, $documentId);
    }
}
