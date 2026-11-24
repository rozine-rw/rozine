<?php

declare(strict_types=1);

namespace App\Application\Identity\Contracts;

/**
 * The devices an account has signed in from (BRS v2.0 NTF-R01, Security: new device).
 */
interface AccountDevices
{
    /**
     * Recognises the device signing in by the tokens its browser holds, one for each account that
     * has signed in on it. When one of them is this account's, it returns null. Otherwise the device
     * is recorded under a fresh token, which is returned for the browser to keep; when the account
     * had signed in from another device before, its Party is told a new device signed in, and the
     * account is also e-mailed while its login alerts are on.
     *
     * @param  list<string>  $tokens
     */
    public function recognize(int $userId, array $tokens, string $label): ?string;
}
