<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditReportRegisterStore;
use App\Application\Identity\AuthorizeStaffPermission;
use App\Domain\Operations\CommandRejection;

/**
 * The Admin console's audit report register (`audit.reports.view`): every accepted or completed
 * audit with its report's state, and one sealed report as its seal recorded it, for its PDF.
 *
 * @phpstan-import-type RegisterRow from AuditReportRegisterStore
 * @phpstan-import-type SealedReport from AuditReportRegisterStore
 */
final class BrowseAuditReportRegister
{
    public function __construct(private AuditReportRegisterStore $store, private AuthorizeStaffPermission $staff) {}

    /** @return list<RegisterRow> */
    public function register(int $actorId): array
    {
        $this->staff->check($actorId, 'audit.reports.view');

        return $this->store->register(now()->toDateTimeImmutable());
    }

    /** @return SealedReport */
    public function sealed(int $actorId, string $reportId): array
    {
        $this->staff->check($actorId, 'audit.reports.view');

        return $this->store->sealed($reportId) ?? throw new CommandRejection('AUDIT_REPORT_NOT_FOUND', 404);
    }
}
