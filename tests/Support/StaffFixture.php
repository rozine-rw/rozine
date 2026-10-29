<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Application\Identity\ConfigureStaffAccess;
use App\Application\Identity\GetStaffAccess;
use App\Domain\Identity\StaffPermission;
use App\Models\IdentityAuditEvent;
use App\Models\StaffAccount;
use App\Models\User;
use Illuminate\Support\Str;

/** Synthetic pre-existing administrators are fixtures, never a production bootstrap exception. */
final class StaffFixture
{
    /** @param list<string> $roles */
    public static function provision(User $user, array $roles, string $reason): void
    {
        if (! in_array('superadmin', $roles, true)) {
            app(ConfigureStaffAccess::class)->handle($user->id, true, $reason, (string) Str::uuid(), $roles);

            return;
        }
        $roles = StaffPermission::normalizeRoles($roles);
        StaffAccount::factory()->withRoles($roles)->create(['user_id' => $user->id]);
        IdentityAuditEvent::factory()->create(['target_id' => (string) $user->id, 'action' => 'staff.configure', 'reason' => $reason,
            'before' => ['enabled' => false, 'party_id' => null, 'roles' => []],
            'after' => ['enabled' => true, 'party_id' => null, 'roles' => $roles],
            'result' => ['code' => 'STAFF_ACCESS_ENABLED', 'user_id' => $user->id]]);
    }

    /** @param list<string> $roles
     * @return list<string>
     */
    public static function permissions(array $roles): array
    {
        $user = User::factory()->withTwoFactor()->create();
        self::provision($user, $roles, 'Synthetic role permission check.');

        return app(GetStaffAccess::class)->handle($user->id)['allowed_actions'];
    }
}
