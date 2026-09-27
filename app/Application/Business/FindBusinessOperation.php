<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Business\Contracts\BusinessApplicationStore;
use App\Application\Business\Contracts\BusinessCampaignStore;

final class FindBusinessOperation
{
    public function __construct(private BusinessApplicationStore $store, private BusinessCampaignStore $campaigns) {}

    /** @return array<string, mixed> */
    public function handle(int $userId, int $contextRevision, string $command, string $requestId): array
    {
        if ($command === 'application.publish') {
            return $this->campaigns->findPublication($userId, $contextRevision, $requestId);
        }

        return $this->store->findOperation($userId, $contextRevision, $command, $requestId);
    }
}
