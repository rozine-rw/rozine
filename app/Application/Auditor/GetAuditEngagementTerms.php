<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditEngagementStore;

/** @phpstan-import-type Page from AuditEngagementStore */
final class GetAuditEngagementTerms
{
    public function __construct(private AuditEngagementStore $engagements) {}

    /** @return Page */
    public function handle(int $userId, int $contextRevision): array
    {
        return $this->engagements->get($userId, $contextRevision);
    }
}
