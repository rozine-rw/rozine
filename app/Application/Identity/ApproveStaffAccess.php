<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\IdentityAccessStore;
use App\Domain\Identity\IdentityViolation;

final class ApproveStaffAccess
{
    public function __construct(private IdentityAccessStore $access) {}

    /** @return array<string, mixed> */
    public function handle(int $actorId, string $proposalId, string $reason, string $requestId): array
    {
        try {
            return $this->access->approveStaff($actorId, $proposalId, $reason, $requestId);
        } catch (IdentityViolation $violation) {
            throw new StaffAccessRefused($violation->reason, $violation);
        }
    }
}
