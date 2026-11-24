<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\AccountPulse;

/** Whether anything the person's dashboard shows has changed since the page last asked. */
final class GetAccountPulse
{
    public function __construct(private AccountPulse $pulse) {}

    public function handle(int $userId): string
    {
        return $this->pulse->version($userId);
    }
}
