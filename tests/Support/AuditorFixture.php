<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Application\Auditor\RecordAuditorStanding;
use App\Application\Auditor\SubmitAuditorAccreditation;
use App\Application\Identity\ConfigureStaffAccess;
use App\Application\Identity\SelectActiveRole;
use App\Models\Party;
use App\Models\RoleMembership;
use App\Models\User;
use Illuminate\Support\Str;

/** @phpstan-import-type Candidate from \App\Domain\Auditor\AuditorDispatch */
final class AuditorFixture
{
    /** @return array{user: User, party: Party, staff: User} */
    public static function make(): array
    {
        $party = Party::factory()->verified()->create();
        $user = User::factory()->withTwoFactor()->for($party)->create();
        RoleMembership::factory()->for($party)->active()->create(['role' => 'auditor']);
        app(SelectActiveRole::class)->handle($user->id, 'auditor', 0, (string) Str::uuid());
        $staff = User::factory()->withTwoFactor()->create();
        app(ConfigureStaffAccess::class)->handle($staff->id, true, 'Review synthetic auditor accreditation.', (string) Str::uuid(), ['compliance']);

        return ['user' => $user, 'party' => $party, 'staff' => $staff];
    }

    /** @return array<string, mixed> */
    public static function submit(User $user, int $revision = 0, ?string $request = null): array
    {
        return app(SubmitAuditorAccreditation::class)->handle($user->id, 1, $revision, 'SYNTHETIC-CPA', now()->addYear()->format('Y-m-d'),
            'private-certificate.pdf', "%PDF-1.7\nSynthetic private certificate\n%%EOF", $request ?? (string) Str::uuid());
    }

    /** @return array<string, mixed> */
    public static function review(User $staff, string $partyId, int $revision, string $decision, ?string $submissionId = null, ?string $request = null): array
    {
        return app(RecordAuditorStanding::class)->handle($staff->id, $partyId, $revision, $decision, $submissionId,
            now('UTC')->format('Y-m-d\TH:i:s\Z'), 'synthetic-manual-register-check', 'Synthetic reviewed evidence.', $request ?? (string) Str::uuid());
    }

    /** @return Candidate */
    public static function candidate(string $id = 'partner-a'): array
    {
        $location = ['verified_at' => '2026-09-24T08:00:00Z', 'moved_at' => null, 'uncertainty_m' => 50];

        return ['id' => $id, 'standing' => ['status' => 'active', 'licence' => 'SYNTHETIC-CPA', 'expires_on' => '2028-12-31',
            'checked_at' => '2026-09-24T08:00:00Z', 'check_reference' => 'synthetic-register-check'],
            'accepting' => true, 'active_count' => 0, 'consecutive_reports' => 0, 'last_assigned_at' => null,
            'office' => $location, 'premises' => $location, 'distance_upper_bound_m' => 29900,
            'financial_interest' => false, 'current_role_tie' => false, 'role_tie_ended_at' => null,
            'family_or_business_conflict' => false, 'unresolved_conflict' => false];
    }
}
