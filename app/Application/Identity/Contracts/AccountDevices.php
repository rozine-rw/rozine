<?php

declare(strict_types=1);

namespace App\Application\Identity\Contracts;

/**
 * The devices an account has signed in from (BRS v2.0 NTF-R01, Security: new device).
 */
interface AccountDevices
{
    /**
     * Recognises the device signing in by the token its browser holds. A known token returns
     * null. Otherwise the device is recorded under a fresh token, which is returned for the
     * browser to keep; when the account had signed in from another device before, its Party is
     * told a new device signed in.
     */
    public function recognize(int $userId, ?string $token, string $label): ?string;
}
