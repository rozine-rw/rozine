<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditAssignmentStore;

/**
 * The audit still open on a Business as staff who manage assignments see it in its 360. While it waits
 * on Audit Operations it carries the dispatch attempts used and the Operations actions still allowed;
 * once it can no longer be redispatched (attempts used up or the Flash deadline passed) it says so.
 *
 * @phpstan-type Engagement array{id: string, revision: int, kind: string, status: string, attempt: int|null, operations_reason: string|null, allowed_actions: list<string>}
 */
final class GetBusinessAuditEngagement
{
    public function __construct(private AuditAssignmentStore $store) {}

    /** @return Engagement|null */
    public function handle(int $actorId, string $businessId): ?array
    {
        $open = $this->store->openEngagement($businessId);
        if ($open === null || $open['status'] !== 'operations') {
            return $open === null ? null : [...$open, 'attempt' => null, 'operations_reason' => null, 'allowed_actions' => []];
        }
        $case = $this->store->operationsCase($actorId, $open['id']);

        $exhausted = ! in_array('audit.assignment.redispatch', $case['allowed_actions'], true);

        return [...$open, 'revision' => $case['revision'], 'attempt' => $case['attempt'],
            'operations_reason' => $exhausted ? 'AUDIT_DISPATCH_EXHAUSTED' : $case['operations_reason'], 'allowed_actions' => $case['allowed_actions']];
    }
}
