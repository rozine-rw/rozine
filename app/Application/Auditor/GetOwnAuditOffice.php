<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditLocationStore;

/** Whether dispatch can measure from the signed-in Audit Partner's registered office (BR-51). */
final class GetOwnAuditOffice
{
    public function __construct(private AuditLocationStore $store) {}

    /** @return 'verified'|'expired'|'missing' */
    public function handle(int $userId, int $contextRevision): string
    {
        return $this->store->ownOffice($userId, $contextRevision);
    }
}
