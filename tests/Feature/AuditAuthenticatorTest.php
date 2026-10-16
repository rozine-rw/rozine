<?php

declare(strict_types=1);

use App\Application\Identity\Contracts\Authenticator;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Models\User;
use PragmaRX\Google2FA\Google2FA;

it('binds the current confirmed authenticator and rejects an incorrect code', function (): void {
    $user = User::factory()->create(['two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'), 'two_factor_confirmed_at' => now()->subMinute()]);
    $authenticator = app(Authenticator::class);
    $code = (new Google2FA)->getCurrentOtp('JBSWY3DPEHPK3PXP');
    expect($authenticator->verify($user->id, $code))->toBe($authenticator->binding($user->id));
    expect(fn () => $authenticator->verify($user->id, $code))->toThrow(CommandRejection::class, 'STEP_UP_CODE_INVALID');
    expect(fn () => $authenticator->verify($user->id, $code === '000000' ? '000001' : '000000'))
        ->toThrow(CommandRejection::class, 'STEP_UP_CODE_INVALID');
});

it('refuses absent unconfirmed future or unreadable authenticators', function (string $case): void {
    $user = User::factory()->create(['two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'), 'two_factor_confirmed_at' => now()->subMinute()]);
    $user->forceFill(match ($case) {
        'missing' => ['two_factor_secret' => null], 'unconfirmed' => ['two_factor_confirmed_at' => null],
        'future' => ['two_factor_confirmed_at' => now()->addMinute()], default => ['two_factor_secret' => 'unreadable synthetic ciphertext'],
    })->save();
    expect(fn () => app(Authenticator::class)->verify($user->id, '123456'))->toThrow(IdentityViolation::class, 'MFA_REQUIRED');
})->with(['missing', 'unconfirmed', 'future', 'unreadable']);
