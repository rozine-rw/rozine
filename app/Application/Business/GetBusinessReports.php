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
 * filed figures, so nothing older, and no inflow or health, is returned. The pre-listing flash
 * report is listed too, so a report waiting for the business's signature is always here, with the
 * end of its co-sign window when it has one. A report its own page cannot open is not offered.
 */
/**
 * @phpstan-type Latest array{id: string, kind: 'flash'|'monthly', period: string|null, status: string, auditor: string, cosign_by: string|null}
 */
final class GetBusinessReports
{
    public function __construct(private BusinessApplicationStore $applications, private GetBusinessAuditReport $reports) {}

    /**
     * @return array{identity_context_revision: int, business_id: string, latest: Latest|null}
     */
    public function handle(int $userId, int $contextRevision, string $businessId): array
    {
        $entry = $this->applications->home($userId, $contextRevision, $businessId);
        $latest = $entry['audit_report'];

        return ['identity_context_revision' => $contextRevision, 'business_id' => (string) $entry['business_id'],
            'latest' => $latest === null ? null : $this->latest($userId, $contextRevision, $businessId, $latest)];
    }

    /**
     * @param  array{id: string, kind: string, status: string}  $latest
     * @return Latest|null
     */
    private function latest(int $userId, int $contextRevision, string $businessId, array $latest): ?array
    {
        try {
            $read = $this->reports->handle($userId, $contextRevision, $businessId, $latest['id']);
        } catch (CommandRejection) {
            return null;
        }
        $report = $read['report'];

        return ['id' => $latest['id'], 'kind' => $report['kind'] === 'flash' ? 'flash' : 'monthly', 'period' => $report['period'] === null ? null : (string) $report['period'],
            'status' => $latest['status'], 'auditor' => (string) $report['auditor']['name'],
            'cosign_by' => $latest['status'] === 'pending' && $read['cosign']['due_at'] !== null ? (string) $read['cosign']['due_at'] : null];
    }
}
