<?php

declare(strict_types=1);

namespace App\Application\Auditor\Contracts;

/**
 * The partner's own filed reports, as the Portfolio lists them (MVP-AUDITOR-SCR-07): each report
 * they sealed, with the Business and period it was sealed for and whether its publication has
 * completed. A plain read under the current Auditor role; it decides nothing.
 *
 * @phpstan-type FiledReport array{id: string, kind: string, period: string|null, business: string, district: string, sealed_at: string, due_at: string|null, published: bool}
 * @phpstan-type FiledReports array{data: list<FiledReport>, counts: array{all: int, published: int}}
 */
interface AuditorFiledReportStore
{
    /**
     * The current Auditor's sealed reports, most recently sealed first and at most `$limit` of
     * them: all of them, only the published ones (`$published` true) or only those still before
     * publication (false). The counts always cover every filed report, whatever the filter.
     *
     * @return FiledReports
     */
    public function filed(int $userId, int $contextRevision, ?bool $published, int $limit): array;
}
