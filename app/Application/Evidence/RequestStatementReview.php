<?php

declare(strict_types=1);

namespace App\Application\Evidence;

use App\Application\Evidence\Contracts\StatementStore;

/** @phpstan-import-type ReviewRequest from StatementStore */
final class RequestStatementReview
{
    public function __construct(private StatementStore $store) {}

    /**
     * @param  ReviewRequest  $binding
     * @return array<string, mixed>
     */
    public function handle(int $actorId, string $businessId, array $binding, string $reason, string $requestId): array
    {
        return $this->store->requestReview($actorId, $businessId, $binding, $reason, $requestId);
    }
}
