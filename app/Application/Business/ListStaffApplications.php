<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Business\Contracts\StaffApplicationQueue;
use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Identity\Contracts\IdentityAccessStore;
use App\Domain\Identity\IdentityViolation;

final class ListStaffApplications
{
    public function __construct(private StaffApplicationQueue $queue, private BusinessCampaignStore $campaigns,
        private AuthorizeStaffPermission $staff, private IdentityAccessStore $access) {}

    /** @return array<string, mixed> */
    public function handle(int $userId, string $tab, string $search, ?string $before, int $limit, ?string $applicationId): array
    {
        $this->staff->check($userId, 'applications.review');
        $applicationId = $applicationId === null ? null : strtolower($applicationId);
        $before = $before === null ? null : strtolower($before);
        $page = $this->queue->page($tab, $search, $before, $limit, $applicationId);
        $release = $applicationId === null ? null : $this->campaigns->staffPage($userId, $applicationId);
        $access = $this->access->staffAccess($userId, true, false);
        if (! in_array('applications.review', $access['allowed_actions'], true)) {
            throw new IdentityViolation('STAFF_PERMISSION_REQUIRED');
        }

        return [...$page, 'release_page' => $release, 'roles' => $access['roles'], 'permissions' => $access['allowed_actions']];
    }
}
