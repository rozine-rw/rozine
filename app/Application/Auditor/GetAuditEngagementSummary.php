<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditEngagementStore;

/** @phpstan-type Summary array{status: 'current'|'required'|'unavailable'} */
final class GetAuditEngagementSummary
{
    public function __construct(private AuditEngagementStore $engagements) {}

    /** @return Summary */
    public function handle(int $userId, int $contextRevision): array
    {
        $page = $this->engagements->get($userId, $contextRevision);

        return ['status' => $page['release'] === null ? 'unavailable' : ($page['acceptance'] === null ? 'required' : 'current')];
    }
}
