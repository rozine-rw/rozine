<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Business\Contracts\BusinessApplicationStore;

final class EvaluateBusinessApplication
{
    public function __construct(private BusinessApplicationStore $store) {}

    /** @return array<string, mixed> */
    public function handle(int $userId, int $contextRevision, string $businessId, string $applicationId, int $expectedRevision, ?string $acceptedPrincipal, string $requestId): array
    {
        return $this->store->evaluate($userId, $contextRevision, $businessId, $applicationId, $expectedRevision, $acceptedPrincipal, $requestId);
    }
}
