<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditorFiledReportStore;
use App\Domain\Auditor\AuditReadPolicy;

/** @phpstan-import-type FiledReports from AuditorFiledReportStore */
final class ListAuditorFiledReports
{
    public function __construct(private AuditorFiledReportStore $reports) {}

    /**
     * The partner's own filed reports; the page never lists more than one Auditor list read allows.
     *
     * @return FiledReports
     */
    public function handle(int $userId, int $contextRevision, ?bool $published = null, int $limit = AuditReadPolicy::MAX_PAGE_SIZE): array
    {
        return $this->reports->filed($userId, $contextRevision, $published, $limit);
    }
}
