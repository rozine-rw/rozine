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

    /**
     * The Business's own raises for its Home: every listing newest first with retained progress,
     * the latest published rating, released applications not yet listed, and capital totals from
     * retained funding locks only.
     *
     * @return array<string, mixed>
     */
    public function home(int $userId, int $contextRevision, string $businessId): array;

    /** @return array<string, mixed> */
    public function findRelease(int $userId, string $requestId): array;

    /** @return array<string, mixed> */
    public function findPublication(int $userId, int $contextRevision, string $requestId): array;

    /** @return array<string, mixed> */
    public function cancel(int $userId, int $contextRevision, string $businessId, string $campaignId, int $expectedRevision, ?string $reason, string $requestId): array;

    /** @return array<string, mixed> */
    public function findCancellation(int $userId, int $contextRevision, string $requestId): array;

    public function expireDue(int $limit): int;
}
