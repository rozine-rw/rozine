<?php

declare(strict_types=1);

namespace App\Application\Evidence;

use App\Application\Evidence\Contracts\StatementStore;

final class FindStatementReviewOperation
{
    public function __construct(private StatementStore $store) {}

    /** @return array<string, mixed> */
    public function handle(int $actorId, string $businessId, string $requestId): array
    {
        return $this->store->findReviewOperation($actorId, $businessId, $requestId);
    }
}
