<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Business\Contracts\BusinessApplicationStore;

final class SubmitBusinessApplication
{
    public function __construct(private BusinessApplicationStore $store) {}

    /**
     * @param  array<string, mixed>  $acceptance
     * @return array<string, mixed>
     */
    public function handle(int $userId, int $contextRevision, string $businessId, string $applicationId, int $expectedRevision, array $acceptance, string $requestId): array
    {
        return $this->store->submit($userId, $contextRevision, $businessId, $applicationId, $expectedRevision, $acceptance, $requestId);
    }
}
