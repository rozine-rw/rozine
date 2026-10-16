<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditLocationStore;

/** @phpstan-import-type Location from AuditLocationStore */
final class GetAuditLocation
{
    public function __construct(private AuditLocationStore $store) {}

    /** @return Location */
    public function handle(int $actorId, string $kind, string $subjectId): array
    {
        return $this->store->get($actorId, $kind, $subjectId);
    }
}
