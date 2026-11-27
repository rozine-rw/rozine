<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\InstitutionDirectoryStore;
use App\Domain\Operations\CommandRejection;

/**
 * The Institutions directory and each institution's 360 (Erastus, 27 Nov 2026), for the staff who review
 * institution KYB (`businesses.verify`, as the review drawer checks). Every read checks the permission first.
 *
 * @phpstan-import-type Directory from InstitutionDirectoryStore
 * @phpstan-import-type Detail from InstitutionDirectoryStore
 */
final class BrowseInstitutionDirectory
{
    public const string PERMISSION = 'businesses.verify';

    public function __construct(private InstitutionDirectoryStore $store, private AuthorizeStaffPermission $staff) {}

    /**
     * @param  'all'|'draft'|'submitted'|'changes_requested'|'approved'|'rejected'  $chip
     * @return Directory
     */
    public function directory(int $actorId, string $chip, string $search, int $limit): array
    {
        $this->staff->check($actorId, self::PERMISSION);

        return $this->store->directory($chip, $search, $limit);
    }

    /** @return Detail */
    public function institution(int $actorId, string $applicationId): array
    {
        $this->staff->check($actorId, self::PERMISSION);

        return $this->store->institution($applicationId) ?? throw new CommandRejection('INSTITUTION_NOT_FOUND', 404);
    }
}
