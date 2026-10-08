<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Business\Contracts\BusinessDirectoryStore;
use App\Application\Identity\AuthorizeStaffPermission;
use App\Domain\Operations\CommandRejection;

/**
 * The Business directory (`businesses.view`): every Business on the platform with its notes,
 * Investors and capital, and one Business's notes and history. Every read checks the permission first.
 *
 * @phpstan-import-type Directory from BusinessDirectoryStore
 * @phpstan-import-type Detail from BusinessDirectoryStore
 */
final class BrowseBusinessDirectory
{
    public function __construct(private BusinessDirectoryStore $store, private AuthorizeStaffPermission $staff) {}

    /**
     * @param  'all'|'healthy'|'watch'|'distressed'|'frozen'  $chip
     * @param  'raised'|'name'  $sort
     * @return Directory
     */
    public function directory(int $actorId, string $chip, string $sort, string $sector, string $search, int $limit): array
    {
        $this->staff->check($actorId, 'businesses.view');

        return $this->store->directory($chip, $sort, $sector, $search, $limit);
    }

    /** @return Detail */
    public function business(int $actorId, string $businessId): array
    {
        $this->staff->check($actorId, 'businesses.view');

        return $this->store->business($businessId) ?? throw new CommandRejection('BUSINESS_NOT_FOUND', 404);
    }
}
