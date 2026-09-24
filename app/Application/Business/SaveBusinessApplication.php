<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Business\Contracts\BusinessApplicationStore;

/** @phpstan-import-type Fields from \App\Domain\Business\ApplicationDraft */
final class SaveBusinessApplication
{
    public function __construct(private BusinessApplicationStore $store) {}

    /**
     * @param  Fields  $fields
     * @return array<string, mixed>
     */
    public function handle(int $userId, int $contextRevision, string $businessId, string $applicationId, int $expectedRevision, array $fields, string $step, string $requestId): array
    {
        return $this->store->save($userId, $contextRevision, $businessId, $applicationId, $expectedRevision, $fields, $step, $requestId);
    }
}
