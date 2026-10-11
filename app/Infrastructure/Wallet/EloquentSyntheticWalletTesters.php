<?php

declare(strict_types=1);

namespace App\Infrastructure\Wallet;

use App\Application\Wallet\Contracts\SyntheticWalletTesters;
use App\Application\Wallet\SyntheticWalletGuard;
use App\Models\SyntheticWalletTesterChange;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Grants and revocations are appended to `synthetic_wallet_tester_changes`, the latest one deciding.
 * Both take the tester account's row lock, which every Investor deposit holds while it is recorded.
 */
final class EloquentSyntheticWalletTesters implements SyntheticWalletTesters
{
    public function __construct(private SyntheticWalletGuard $guard) {}

    public function authorized(string $partyId): bool
    {
        return SyntheticWalletTesterChange::query()->where('party_id', $partyId)->orderByDesc('id')->value('action') === 'granted';
    }

    public function grant(string $partyId, int $userId, string $reason, string $requestId, Closure $prepare): array
    {
        $this->guard->assertAllowed();

        return DB::transaction(function () use ($partyId, $userId, $reason, $requestId, $prepare): array {
            User::query()->lockForUpdate()->findOrFail($userId);
            $changeId = $this->authorized($partyId) ? null : $this->append($partyId, $userId, 'granted', $reason, $requestId);

            return ['change_id' => $changeId, 'prepared' => $prepare()];
        }, 3);
    }

    public function revoke(string $partyId, int $userId, string $reason, string $requestId): string
    {
        $this->guard->assertAllowed();

        return DB::transaction(function () use ($partyId, $userId, $reason, $requestId): string {
            User::query()->lockForUpdate()->findOrFail($userId);
            if (! $this->authorized($partyId)) {
                throw new LogicException('WALLET_STAGING_TESTER_NOT_NAMED: that account is not a named wallet tester, so nothing was revoked.');
            }

            return $this->append($partyId, $userId, 'revoked', $reason, $requestId);
        }, 3);
    }

    private function append(string $partyId, int $userId, string $action, string $reason, string $requestId): string
    {
        $change = new SyntheticWalletTesterChange;
        $change->forceFill(['party_id' => $partyId, 'user_id' => $userId, 'action' => $action, 'reason' => $reason,
            'actor_key' => 'console', 'request_id' => $requestId, 'created_at' => now()])->save();

        return $change->id;
    }
}
