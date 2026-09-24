<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Application\Business\ConfigureBusinessAuthority;
use App\Application\Identity\ConfigureStaffAccess;
use App\Application\Identity\SelectActiveRole;
use App\Domain\Business\MandateAuthority;
use App\Models\Party;
use App\Models\RoleMembership;
use App\Models\User;
use App\Models\VerifiedOrganizationIdentity;
use Illuminate\Support\Str;

/**
 * @phpstan-import-type Profile from MandateAuthority
 * @phpstan-import-type Terms from MandateAuthority
 *
 * @phpstan-type Fixture array{staff: User, people: list<Party>, users: list<User>, entity: string, kind: string, profile: Profile, terms: Terms}
 */
final class BusinessAuthorityFixture
{
    /** @return Fixture */
    public static function make(string $kind = 'person', int $count = 1): array
    {
        $staff = User::factory()->withTwoFactor()->create();
        app(ConfigureStaffAccess::class)->handle($staff->id, true, 'Verify business mandates.', (string) Str::uuid(), ['compliance']);
        $people = [];
        $users = [];
        $members = [];
        for ($index = 0; $index < $count; $index++) {
            $party = Party::factory()->verified()->create();
            $user = User::factory()->for($party)->create();
            RoleMembership::factory()->for($party)->active()->create(['role' => 'business']);
            app(SelectActiveRole::class)->handle($user->id, 'business', 0, (string) Str::uuid());
            $people[] = $party;
            $users[] = $user;
            $members[] = ['party_id' => $party->id, 'name' => 'Verified person '.$index,
                'roles' => ['owner', 'controller', 'signatory'], 'permissions' => MandateAuthority::PERMISSIONS];
        }
        $entity = $kind === 'person' ? $people[0]->id : VerifiedOrganizationIdentity::factory()->create(['registry_digest' => hash('sha256', 'RDB:COMPANY-001')])->party_id;

        return ['staff' => $staff, 'people' => $people, 'users' => $users, 'entity' => $entity, 'kind' => $kind,
            'profile' => ['name' => 'Synthetic business', 'company_code' => $kind === 'person' ? null : 'COMPANY-001', 'industry' => 'retail', 'district' => 'Gasabo', 'established_year' => 2020],
            'terms' => ['people' => $members, 'required_signatories' => array_column($members, 'party_id'),
                'effective_at' => now('UTC')->subMinute()->format('Y-m-d\TH:i:s\Z'), 'expires_at' => null, 'status' => 'active', 'attested_complete' => true]];
    }

    /**
     * @param  Fixture  $fixture
     * @return array<string, mixed>
     */
    public static function configure(array $fixture, int $revision = 0, ?string $requestId = null): array
    {
        return app(ConfigureBusinessAuthority::class)->handle($fixture['staff']->id, $fixture['kind'], $fixture['entity'], $fixture['profile'], $fixture['terms'], $revision,
            'fixture:reviewed-mandate', 'Reviewed complete authority evidence.', $requestId ?? (string) Str::uuid());
    }
}
