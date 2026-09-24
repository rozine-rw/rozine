<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditorProfileStore;

/** @phpstan-import-type Profile from AuditorProfileStore */
final class GetAuditorProfile
{
    public function __construct(private AuditorProfileStore $store) {}

    /** @return Profile */
    public function handle(int $userId, int $contextRevision): array
    {
        return $this->store->get($userId, $contextRevision);
    }
}
