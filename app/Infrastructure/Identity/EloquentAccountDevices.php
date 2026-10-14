<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity;

use App\Application\Identity\Contracts\AccountDevices;
use App\Application\Identity\Contracts\PartyNotices;
use App\Domain\Identity\PartyNoticeKind;
use App\Models\AccountDevice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Devices in `account_devices`, each known by the SHA-256 of its browser token. The first device
 * an account signs in from is recorded silently; every later new device is noticed.
 */
final readonly class EloquentAccountDevices implements AccountDevices
{
    public function __construct(private PartyNotices $notices) {}

    public function recognize(int $userId, ?string $token, string $label): ?string
    {
        $owned = AccountDevice::query()->where('user_id', $userId);
        if (is_string($token) && $token !== '' && (clone $owned)->where('token_hash', hash('sha256', $token))->exists()) {
            return null;
        }

        return DB::transaction(function () use ($owned, $userId, $label): string {
            $known = $owned->exists();
            $token = Str::random(40);
            $device = new AccountDevice;
            $device->forceFill(['user_id' => $userId, 'token_hash' => hash('sha256', $token), 'label' => $label, 'created_at' => now('UTC')])->save();
            if ($known) {
                $this->notices->recordForAccount($userId, PartyNoticeKind::NewDevice, ['device' => $label], 'new-device:'.$device->id);
            }

            return $token;
        });
    }
}
