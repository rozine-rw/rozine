<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditorProfileStore;

/** @phpstan-import-type Download from AuditorProfileStore */
final class ReadAuditorCertificate
{
    public function __construct(private AuditorProfileStore $store) {}

    /** @return Download */
    public function handle(int $userId, ?int $contextRevision, string $partyId, string $certificateId, bool $staff = false): array
    {
        return $this->store->readCertificate($userId, $contextRevision, $partyId, $certificateId, $staff);
    }
}
