<?php

declare(strict_types=1);

namespace App\Application\Evidence;

use App\Application\Evidence\Contracts\StatementStore;

/** @phpstan-import-type Manifest from StatementStore */
final class GetStatementEvidence
{
    public function __construct(private StatementStore $store) {}

    /** @return Manifest */
    public function handle(int $userId, int $contextRevision, string $businessId): array
    {
        return $this->store->get($userId, $contextRevision, $businessId);
    }
}
