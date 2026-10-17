<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditCoverage;
use App\Application\Identity\AuthorizeStaffPermission;
use Carbon\CarbonImmutable;

/**
 * Partner coverage for staff who verify Audit Partners (`audit.partners.verify`): every district a
 * Business operates in, with the partners dispatch could reach there and the audits still open.
 *
 * @phpstan-import-type District from AuditCoverage
 */
final readonly class BrowseAuditCoverage
{
    public function __construct(private AuditCoverage $coverage, private AuthorizeStaffPermission $staff) {}

    /** @return list<District> */
    public function handle(int $actorId): array
    {
        $this->staff->check($actorId, 'audit.partners.verify');

        return $this->coverage->districts(CarbonImmutable::now()->toDateTimeImmutable());
    }
}
