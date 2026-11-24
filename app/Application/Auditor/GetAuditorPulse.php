<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditorPulse;

/** Whether anything the partner's Auditor home shows has changed since the page last asked. */
final class GetAuditorPulse
{
    public function __construct(private AuditorPulse $pulse) {}

    public function handle(int $userId): string
    {
        return $this->pulse->version($userId);
    }
}
