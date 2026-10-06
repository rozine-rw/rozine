<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditorProfileStore;

final class GetAuditorAccreditation
{
    public function __construct(private AuditorProfileStore $store) {}

    /** @return array<string, mixed> */
    public function handle(int $userId, int $contextRevision): array
    {
        return $this->store->accreditation($userId, $contextRevision);
    }
}
