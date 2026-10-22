<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Application\Identity\ConfigureStaffAccess;
use App\Application\Identity\RecordStaffPerson;
use App\Models\IdentityOperator;
use App\Models\StaffPersonIdentity;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use PragmaRX\Google2FA\Google2FA;

final class StaffRecoveryFixture
{
    /** @return array{0: User, 1: User, 2: User, 3: User} */
    public static function people(): array
    {
        // Use the test clock for the real TOTP algorithm and Fortify anti-replay provider.
        app()->instance(Google2FA::class, new class extends Google2FA
        {
            public function getTimestamp(): int
            {
                return intdiv(now()->getTimestamp(), 30);
            }
        });
        $identityOperator = User::factory()->withTwoFactor()->create();
        IdentityOperator::factory()->create(['user_id' => $identityOperator->id]);
        $people = [];
        foreach (range(1, 4) as $index) {
            $user = User::factory()->withTwoFactor()->create(['two_factor_secret' => Fortify::currentEncrypter()->encrypt(app(Google2FA::class)->generateSecretKey())]);
            app(ConfigureStaffAccess::class)->handle($user->id, true, 'Synthetic ordinary staff.', (string) Str::uuid(), ['analyst']);
            app(RecordStaffPerson::class)->handle($identityOperator->id, $user->id, 'nid:synthetic-recovery-'.$index,
                'test:independently-verified-'.$index, 'Synthetic identity verification.', (string) Str::uuid());
            $people[] = $user->refresh();
        }
        [$first, $second] = $people;
        config(['staff-recovery.operators' => [
            $first->id => StaffPersonIdentity::query()->where('staff_user_id', $first->id)->sole()->identity_digest,
            $second->id => StaffPersonIdentity::query()->where('staff_user_id', $second->id)->sole()->identity_digest,
        ]]);

        return [$people[0], $people[1], $people[2], $people[3]];
    }

    public static function code(User $user): string
    {
        return app(Google2FA::class)->getCurrentOtp(Fortify::currentEncrypter()->decrypt($user->refresh()->two_factor_secret));
    }
}
