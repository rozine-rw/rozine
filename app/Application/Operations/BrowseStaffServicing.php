<?php

declare(strict_types=1);

namespace App\Application\Operations;

use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Operations\Contracts\StaffServicingStore;

/** Servicing reads use the existing staff money-movement read authority; no new grant or command. */
final readonly class BrowseStaffServicing
{
    public function __construct(private AuthorizeStaffPermission $staff, private StaffServicingStore $store) {}

    /** @return array<string, mixed> */
    public function handle(int $actorId): array
    {
        $this->staff->check($actorId, 'payments.view');

        return $this->store->read();
    }
}
