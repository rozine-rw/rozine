<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Business\Contracts\ReadinessRequirementStore;
use App\Application\Identity\AuthorizeStaffPermission;

/**
 * The readiness requirements a Business has triggered so far (BRS v3.0 RDY-R03), read-only for
 * staff who may view Businesses (APP-D01 readiness monitor, #821). It answers "what is this
 * Business still due on?" from the same store the hourly readiness sweep keeps, in BRS order.
 *
 * @phpstan-import-type Requirement from ReadinessRequirementStore
 */
final class GetStaffBusinessReadiness
{
    public function __construct(private AuthorizeStaffPermission $staff, private ReadinessRequirementStore $requirements) {}

    /** @return list<Requirement> */
    public function handle(int $staffUserId, string $businessId): array
    {
        $this->staff->check($staffUserId, 'businesses.view');

        return $this->requirements->forBusiness($businessId);
    }
}
