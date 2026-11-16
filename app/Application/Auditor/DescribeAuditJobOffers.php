<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditAssignmentStore;

/**
 * Brings each "New audit job" notice in an Audit Partner's inbox up to date (#587). A notice keeps
 * the facts it was sent with, so on its own it would go on asking the partner to accept by a time
 * long past. Each one is told how its offer turned out, the job's real type (a statement review is
 * not a routine audit) and whether the partner still holds the job, so the notice can open it.
 *
 * @phpstan-import-type Inbox from \App\Application\Identity\Contracts\PartyNotices
 */
final readonly class DescribeAuditJobOffers
{
    public function __construct(private AuditAssignmentStore $assignments) {}

    /**
     * @param  Inbox  $inbox
     * @return Inbox
     */
    public function handle(int $userId, array $inbox): array
    {
        $ids = [];
        foreach ($inbox['notices'] as $notice) {
            if ($notice['kind'] === 'auditor.job_offered' && is_string($notice['facts']['assignment_id'] ?? null)) {
                $ids[] = $notice['facts']['assignment_id'];
            }
        }
        if ($ids === []) {
            return $inbox;
        }
        $outcomes = $this->assignments->offerOutcomes($userId, array_values(array_unique($ids)));

        return [...$inbox, 'notices' => array_map(function (array $notice) use ($outcomes): array {
            $id = $notice['kind'] === 'auditor.job_offered' ? $notice['facts']['assignment_id'] ?? null : null;
            $outcome = is_string($id) ? $outcomes[$id] ?? null : null;

            return $outcome === null ? $notice : [...$notice, 'facts' => [...$notice['facts'], 'type' => $outcome['type'],
                'outcome' => $outcome['outcome'], 'held' => $outcome['held']]];
        }, $inbox['notices'])];
    }
}
