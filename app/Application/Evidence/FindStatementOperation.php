<?php

declare(strict_types=1);

namespace App\Application\Evidence;

use App\Application\Evidence\Contracts\StatementStore;

final class FindStatementOperation
{
    public function __construct(private StatementStore $store) {}

    /** @return array<string, mixed> */
    public function handle(int $userId, int $contextRevision, string $requestId): array
    {
        return $this->store->findOperation($userId, $contextRevision, $requestId);
    }
}
