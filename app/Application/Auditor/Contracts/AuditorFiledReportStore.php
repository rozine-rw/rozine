<?php

declare(strict_types=1);

namespace App\Application\Auditor\Contracts;

/**
 * The partner's own filed reports, as the Portfolio lists them (MVP-AUDITOR-SCR-07): each report
 * they sealed, with the Business and period it was sealed for and whether its publication has
 * completed. A plain read under the current Auditor role; it decides nothing. A report's variance is
 * read from its sealed findings on money (counted stock value, cash): the absolute differences over
 * the reported amounts, so a large figure weighs more than a small one.
 *
 * @phpstan-type FiledReport array{id: string, kind: string, period: string|null, business: string, district: string, sealed_at: string, due_at: string|null, published: bool, variance_pct: string|null}
 * @phpstan-type FiledReports array{data: list<FiledReport>, counts: array{all: int, published: int}}
 * @phpstan-type TrackRecord array{on_time_pct: int|null, clock_expiries: int, avg_variance_pct: string|null, variance_flagged: bool}
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

    /**
     * The current Auditor's track record: the whole-percent share of their standing filed reports
     * that were sealed by their due date (rounded down, null before any report with a due date),
     * how many offers ran out on them without being accepted, and the weighted variance across
     * those reports' money findings (one decimal, null before any money finding).
     *
     * @return TrackRecord
     */
    public function trackRecord(int $userId, int $contextRevision): array;
}
