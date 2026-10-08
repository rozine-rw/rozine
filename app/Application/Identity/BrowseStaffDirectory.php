<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\StaffDirectoryStore;
use App\Domain\Operations\CommandRejection;

/**
 * Staff & Roles (`admin.open`): every operator with their roles and account state, and one
 * operator's access history. Read only: no staff command is reachable from here. Every read checks
 * the permission first.
 *
 * @phpstan-import-type Directory from StaffDirectoryStore
 * @phpstan-import-type Member from StaffDirectoryStore
 */
final class BrowseStaffDirectory
{
    public function __construct(private StaffDirectoryStore $store, private AuthorizeStaffPermission $staff) {}

    /**
     * @param  'all'|'active'|'frozen'  $chip
     * @return Directory
     */
    public function directory(int $actorId, string $chip, string $search): array
    {
        $this->staff->check($actorId, 'admin.open');

        return $this->store->directory($chip, $search);
    }

    /** @return Member */
    public function member(int $actorId, int $userId): array
    {
        $this->staff->check($actorId, 'admin.open');

        return $this->store->member($userId) ?? throw new CommandRejection('STAFF_MEMBER_NOT_FOUND', 404);
    }
}
