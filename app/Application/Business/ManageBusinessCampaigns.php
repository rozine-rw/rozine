<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Business\Contracts\BusinessCampaignStore;

final class ManageBusinessCampaigns
{
    public function __construct(private BusinessCampaignStore $store) {}

    /** @return array<string, mixed> */
    public function release(int $userId, string $applicationId, int $revision, string $reason, string $requestId): array
    {
        return $this->store->release($userId, $applicationId, $revision, $reason, $requestId);
    }

    /** @return array<string, mixed> */
    public function publish(int $userId, int $contextRevision, string $businessId, string $applicationId, int $revision, string $feeVersion, string $requestId): array
    {
        return $this->store->publish($userId, $contextRevision, $businessId, $applicationId, $revision, $feeVersion, $requestId);
    }

    /** @return array<string, mixed> */
    public function staffPage(int $userId, string $applicationId): array
    {
        return $this->store->staffPage($userId, $applicationId);
    }

    /** @return array<string, mixed> */
    public function publishPage(int $userId, int $contextRevision, string $businessId, string $applicationId): array
    {
        return $this->store->publishPage($userId, $contextRevision, $businessId, $applicationId);
    }

    /** @return array<string, mixed> */
    public function campaign(int $userId, int $contextRevision, string $businessId, string $campaignId): array
    {
        return $this->store->campaign($userId, $contextRevision, $businessId, $campaignId);
    }

    /** @return array<string, mixed> */
    public function findRelease(int $userId, string $requestId): array
    {
        return $this->store->findRelease($userId, $requestId);
    }
}
