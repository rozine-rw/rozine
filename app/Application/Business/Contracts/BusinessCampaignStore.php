<?php

declare(strict_types=1);

namespace App\Application\Business\Contracts;

interface BusinessCampaignStore
{
    /** @return array<string, mixed> */
    public function release(int $userId, string $applicationId, int $expectedRevision, string $reason, string $requestId): array;

    /** @return array<string, mixed> */
    public function publish(int $userId, int $contextRevision, string $businessId, string $applicationId, int $expectedRevision, string $feeVersion, string $requestId): array;

    /** @return array<string, mixed> */
    public function staffPage(int $userId, string $applicationId): array;

    /** @return array<string, mixed> */
    public function publishPage(int $userId, int $contextRevision, string $businessId, string $applicationId): array;

    /** @return array<string, mixed> */
    public function campaign(int $userId, int $contextRevision, string $businessId, string $campaignId): array;

    /** @return array<string, mixed> */
    public function findRelease(int $userId, string $requestId): array;

    /** @return array<string, mixed> */
    public function findPublication(int $userId, int $contextRevision, string $requestId): array;
}
