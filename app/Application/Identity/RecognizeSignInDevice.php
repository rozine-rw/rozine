<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\AccountDevices;
use App\Domain\Identity\DeviceLabel;

/**
 * Recognises the device behind each sign-in and tells the account's Party when a new one signs in
 * (BRS v2.0 NTF-R01, Security). Returns the token a new device's browser keeps, or null when the
 * device is already known.
 */
final readonly class RecognizeSignInDevice
{
    public const string COOKIE = 'rozine_device';

    public function __construct(private AccountDevices $devices) {}

    public function handle(int $userId, ?string $token, ?string $userAgent): ?string
    {
        return $this->devices->recognize($userId, $token, DeviceLabel::fromUserAgent($userAgent));
    }
}
