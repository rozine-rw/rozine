<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditAssignmentStore;

/**
 * The audit still open on a Business as staff who manage assignments see it in its 360. An accepted
 * audit whose report is published counts as done, since the next request completes it. While one
 * waits on Audit Operations it carries the dispatch attempts used, whether a partner can take it now
 * or why not (premises not verified, no partner office within 30 km, no independence review for the
 * partners in range, or none of them able to) and the Operations actions still allowed; once it can
 * no longer be redispatched (attempts used up or the Flash deadline passed) it says so. Alongside it,
 * the partners within 30 km and whether each has been reviewed as independent of the Business.
 *
 * @phpstan-type Engagement array{id: string, revision: int, kind: string, status: string, attempt: int|null, operations_reason: string|null, allowed_actions: list<string>}
 * @phpstan-type NearbyPartner array{party_id: string, name: string, revision: int, reviewed: bool}
 */
final class GetBusinessAuditEngagement
{
    public function __construct(private AuditAssignmentStore $store) {}

    /** @return Engagement|null */
    public function handle(int $actorId, string $businessId): ?array
    {
        $open = $this->store->openEngagement($businessId);
        if ($open === null || ($open['status'] === 'accepted' && $this->store->workPublished($open['id']))) {
            return null;
        }
        if ($open['status'] !== 'operations') {
            return [...$open, 'attempt' => null, 'operations_reason' => null, 'allowed_actions' => []];
        }
        $case = $this->store->operationsCase($actorId, $open['id']);

        $exhausted = ! in_array('audit.assignment.redispatch', $case['allowed_actions'], true);

        return [...$open, 'revision' => $case['revision'], 'attempt' => $case['attempt'],
            'operations_reason' => $exhausted ? 'AUDIT_DISPATCH_EXHAUSTED' : $this->store->dispatchBlocker($actorId, $open['id']) ?? 'AUDIT_PARTNER_AVAILABLE',
            'allowed_actions' => $case['allowed_actions']];
    }

    /** Whether Audit Operations closed the Business's last audit, so a Flash audit can't be requested for it. */
    public function closed(string $businessId): bool
    {
        return $this->store->lastClosed($businessId);
    }

    /** @return list<NearbyPartner> */
    public function partners(int $actorId, string $businessId): array
    {
        return $this->store->nearbyPartners($actorId, $businessId);
    }
}
