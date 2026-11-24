<?php

declare(strict_types=1);

namespace App\Application\Identity\Contracts;

/**
 * A short fingerprint of everything the launcher shows about one account (Erastus, 24 Nov 2026: the
 * dashboard updates by itself when anything changes on the server). It changes whenever the account's
 * identity check, role application, roles, access requests, notices or sign-in settings change, so a
 * page can ask cheaply and reload only when it moved. It reveals nothing but that something changed.
 */
interface AccountPulse
{
    public function version(int $userId): string;
}
