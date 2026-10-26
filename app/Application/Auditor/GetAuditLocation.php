<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditLocationStore;
use App\Domain\Auditor\VerifiedAuditLocation;
use DateTimeImmutable;

/**
 * @phpstan-import-type Location from AuditLocationStore
 *
 * @phpstan-type ReviewedLocation array{location: Location, standing: 'verified'|'expired'|'missing'}
 */
final class GetAuditLocation
{
    public function __construct(private AuditLocationStore $store) {}

    /** @return Location */
    public function handle(int $actorId, string $kind, string $subjectId): array
    {
        return $this->store->get($actorId, $kind, $subjectId);
    }

    /**
     * The location with whether dispatch can measure from it now.
     *
     * @return ReviewedLocation
     */
    public function review(int $actorId, string $kind, string $subjectId, DateTimeImmutable $now): array
    {
        $location = $this->handle($actorId, $kind, $subjectId);

        return ['location' => $location, 'standing' => VerifiedAuditLocation::standing($location['state'], $now)];
    }
}
