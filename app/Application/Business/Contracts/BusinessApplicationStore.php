<?php

declare(strict_types=1);

namespace App\Application\Business\Contracts;

/**
 * @phpstan-import-type Fields from \App\Domain\Business\ApplicationDraft
 *
 * @phpstan-type Application array{id: string, business_id: string, revision: int, status: string, step: string, draft: Fields, mandate_version: int}
 */
interface BusinessApplicationStore
{
    /** @return array<string, mixed> */
    public function create(int $userId, int $contextRevision, string $businessId, int $expectedRevision, string $requestId): array;

    /**
     * @param  Fields  $fields
     * @return array<string, mixed>
     */
    public function save(int $userId, int $contextRevision, string $businessId, string $applicationId, int $expectedRevision, array $fields, string $step, string $requestId): array;

    /** @return Application */
    public function get(int $userId, int $contextRevision, string $businessId, string $applicationId): array;

    /** @return Application|null */
    public function current(int $userId, int $contextRevision, string $businessId): ?array;

    /** @return array<string, mixed> */
    public function findOperation(int $userId, int $contextRevision, string $command, string $requestId): array;
}
