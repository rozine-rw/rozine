<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditorProfileStore;

final class RemindAccreditationRenewals
{
    public function __construct(private AuditorProfileStore $store) {}

    public function handle(): int
    {
        return $this->store->remindRenewals();
    }
}
