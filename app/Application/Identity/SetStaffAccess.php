<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\IdentityAccessStore;
use App\Domain\Identity\IdentityViolation;

/** Staff & Roles: suspensions are immediate; superadmin reactivation requires a second approver. */
final class SetStaffAccess
{
    public function __construct(private IdentityAccessStore $access) {}

    /**
     * @return array<string, mixed>
     *
     * @throws StaffAccessRefused when the store refuses the change
     */
    public function handle(int $actorId, int $userId, bool $enabled, string $reason, string $requestId): array
    {
        try {
            return $this->access->setStaffEnabled($actorId, $userId, $enabled, $reason, $requestId);
        } catch (IdentityViolation $violation) {
            throw new StaffAccessRefused($violation->reason, $violation);
        }
    }
}
