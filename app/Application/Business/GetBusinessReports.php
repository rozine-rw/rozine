<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Auditor\GetBusinessAuditReport;
use App\Application\Business\Contracts\BusinessApplicationStore;
use App\Domain\Operations\CommandRejection;

/**
 * The Business Reports tab for a current mandate holder. The one report read open to a Business
 * is its latest unamended sealed report, the same one its role landing offers for co-signing, read
 * through that report's own co-sign authority. There is no index of earlier reports and no read of
 * filed figures, so nothing older, and no inflow or health, is returned. A flash report is the
 * pre-listing audit rather than a monthly report, and a report its own page cannot open is not
 * offered here either.
 */
final class GetBusinessReports
{
    public function __construct(private BusinessApplicationStore $applications, private GetBusinessAuditReport $reports) {}

    /**
     * @return array{identity_context_revision: int, business_id: string, latest: array{id: string, period: string, status: string, auditor: string}|null}
     */
    public function handle(int $userId, int $contextRevision, string $businessId): array
    {
        $entry = $this->applications->home($userId, $contextRevision, $businessId);
        $latest = $entry['audit_report'];

        return ['identity_context_revision' => $contextRevision, 'business_id' => (string) $entry['business_id'],
            'latest' => $latest === null || $latest['kind'] !== 'monthly' ? null : $this->monthly($userId, $contextRevision, $businessId, $latest)];
    }

    /**
     * @param  array{id: string, kind: string, status: string}  $latest
     * @return array{id: string, period: string, status: string, auditor: string}|null
     */
    private function monthly(int $userId, int $contextRevision, string $businessId, array $latest): ?array
    {
        try {
            $report = $this->reports->handle($userId, $contextRevision, $businessId, $latest['id'])['report'];
        } catch (CommandRejection) {
            return null;
        }

        return ['id' => $latest['id'], 'period' => (string) $report['period'], 'status' => $latest['status'], 'auditor' => (string) $report['auditor']['name']];
    }
}
