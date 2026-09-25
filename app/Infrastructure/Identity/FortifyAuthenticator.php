<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity;

use App\Application\Identity\Contracts\Authenticator;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

final class FortifyAuthenticator implements Authenticator
{
    public function __construct(private TwoFactorAuthenticationProvider $provider) {}

    public function verify(int $userId, string $code): string
    {
        return DB::transaction(function () use ($userId, $code): string {
            $user = User::query()->lockForUpdate()->findOrFail($userId);
            $binding = $this->credentialBinding($user);
            try {
                $secret = Fortify::currentEncrypter()->decrypt($user->two_factor_secret);
            } catch (DecryptException) {
                throw new IdentityViolation('MFA_REQUIRED');
            }
            if (! preg_match('/^[0-9]{6}$/D', $code) || ! $this->provider->verify($secret, $code)) {
                throw new CommandRejection('STEP_UP_CODE_INVALID', 422, fieldErrors: ['code' => ['Enter a current six-digit authenticator code.']]);
            }

            return $binding;
        });
    }

    public function binding(int $userId): string
    {
        return $this->credentialBinding(User::query()->sharedLock()->findOrFail($userId));
    }

    private function credentialBinding(User $user): string
    {
        if (! Features::enabled(Features::twoFactorAuthentication()) || $user->two_factor_secret === null
            || $user->two_factor_confirmed_at === null || $user->two_factor_confirmed_at->isFuture()) {
            throw new IdentityViolation('MFA_REQUIRED');
        }

        return hash('sha256', $user->two_factor_secret.'|'.$user->two_factor_confirmed_at->toIso8601String().'|'.$user->password);
    }
}
