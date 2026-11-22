<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\UserDirectoryStore;
use App\Domain\Operations\CommandRejection;

/**
 * The Admin console's Users page: every account with its roles, KYC state and basics, and one
 * account's detail. It names people and their identity decisions across every role, so it takes the
 * supervisory `investors.verify` that Activity & Audit and the Investor directory already take.
 * Every read checks the permission first.
 *
 * @phpstan-import-type Directory from UserDirectoryStore
 * @phpstan-import-type Detail from UserDirectoryStore
 * @phpstan-import-type Kyc from UserDirectoryStore
 */
final class BrowseUserDirectory
{
    public function __construct(private UserDirectoryStore $store, private AuthorizeStaffPermission $staff) {}

    /**
     * @param  'all'|'investor'|'business'|'auditor'|'staff'|'no_role'  $role
     * @param  'any'|Kyc  $kyc
     * @param  'newest'|'oldest'|'name'  $sort
     * @return Directory
     */
    public function directory(int $actorId, string $role, string $kyc, string $sort, string $search, int $limit, bool $closed = false): array
    {
        $this->staff->check($actorId, 'investors.verify');

        return $this->store->directory($role, $kyc, $sort, $search, $limit, $closed);
    }

    /** @return Detail */
    public function user(int $actorId, int $userId): array
    {
        $this->staff->check($actorId, 'investors.verify');

        return $this->store->user($userId) ?? throw new CommandRejection('USER_NOT_FOUND', 404);
    }
}
