<?php

declare(strict_types=1);

namespace App\Application\Evidence;

use App\Application\Evidence\Contracts\StatementStore;

final class FindStatementVerificationOperation
{
    public function __construct(private StatementStore $store) {}

    /** @return array<string, mixed> */
    public function handle(int $userId, int $contextRevision, string $requestId): array
    {
        return $this->store->findVerificationOperation($userId, $contextRevision, $requestId);
    }
}
