<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\AccountDevices;
use App\Domain\Identity\DeviceLabel;

/**
 * Recognises the device behind each sign-in and tells the account's Party when a new one signs in
 * (BRS v2.0 NTF-R01, Security). A browser keeps one token for each account that signs in on it, so
 * switching accounts on one browser no longer makes each look new again (#595); a token never
 * stands for another account. Returns the cookie's new value when this account's device is new, or
 * null when the device is already known.
 */
final readonly class RecognizeSignInDevice
{
    public const string COOKIE = 'rozine_device';

    /** How many accounts' tokens one browser keeps; the oldest goes first. */
    public const int KEPT = 5;

    public function __construct(private AccountDevices $devices) {}

    public function handle(int $userId, ?string $cookie, ?string $userAgent): ?string
    {
        $tokens = array_values(array_filter(explode('.', $cookie ?? ''), fn (string $token): bool => $token !== ''));
        $token = $this->devices->recognize($userId, $tokens, DeviceLabel::fromUserAgent($userAgent));

        return $token === null ? null : implode('.', [...array_slice($tokens, 1 - self::KEPT), $token]);
    }
}
