<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditorDirectoryStore;
use App\Application\Identity\AuthorizeStaffPermission;
use App\Domain\Operations\CommandRejection;

/**
 * The Audit Partner network (`audit.partners.verify`): every partner with their standing and
 * engagements, and one partner's licence, engagements and accreditation history. Every read checks
 * the permission first; it is the one that already guards a partner's accreditation and certificate.
 *
 * @phpstan-import-type Directory from AuditorDirectoryStore
 * @phpstan-import-type Detail from AuditorDirectoryStore
 */
final class BrowseAuditorDirectory
{
    public function __construct(private AuditorDirectoryStore $store, private AuthorizeStaffPermission $staff) {}

    /**
     * @param  'all'|'active'|'pending'|'licence_expired'  $chip
     * @return Directory
     */
    public function directory(int $actorId, string $chip, string $search, int $limit): array
    {
        $this->staff->check($actorId, 'audit.partners.verify');

        return $this->store->directory($chip, $search, $limit);
    }

    /** @return Detail */
    public function partner(int $actorId, string $partyId): array
    {
        $this->staff->check($actorId, 'audit.partners.verify');

        return $this->store->partner($partyId) ?? throw new CommandRejection('AUDITOR_NOT_FOUND', 404);
    }
}
