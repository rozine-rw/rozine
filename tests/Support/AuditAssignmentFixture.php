<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Application\Auditor\RecordAuditorIndependence;
use App\Application\Auditor\RequestAuditAssignment;
use App\Application\Auditor\RespondToAuditAssignment;
use App\Application\Auditor\SetAuditorAvailability;
use App\Application\Auditor\VerifyAuditLocation;
use App\Application\Identity\ConfigureStaffAccess;
use App\Domain\Auditor\AuditEngagementState;
use App\Models\AuditAssignment;
use App\Models\BusinessProfile;
use App\Models\Party;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * @phpstan-import-type Fixture from BusinessAuthorityFixture as BusinessFixture
 *
 * @phpstan-type Partner array{user: User, party: Party, staff: User}
 * @phpstan-type Fixture array{staff: User, business: string, authority: BusinessFixture, partners: list<Partner>}
 */
final class AuditAssignmentFixture
{
    /** @return Fixture */
    public static function make(int $count = 2, string $kind = 'person'): array
    {
        $authority = BusinessAuthorityFixture::make($kind);
        $business = BusinessAuthorityFixture::configure($authority)['data']['business']['id'];
        $staff = $authority['staff'];
        app(ConfigureStaffAccess::class)->handle($staff->id, true, 'Manage synthetic audit assignments.', (string) Str::uuid(), ['approver', 'compliance']);
        $partners = [];
        self::location($staff, 'premises', $business);
        for ($index = 0; $index < $count; $index++) {
            $partner = AuditorFixture::make();
            $submitted = AuditorFixture::submit($partner['user']);
            AuditorFixture::review($partner['staff'], $partner['party']->id, 1, 'approve', $submitted['data']['submission_id']);
            app(SetAuditorAvailability::class)->handle($partner['user']->id, 1, 2, true, (string) Str::uuid());
            self::location($partner['staff'], 'office', $partner['party']->id);
            self::independence($staff, $business, $partner['party']->id);
            $partners[] = $partner;
        }

        return ['staff' => $staff, 'business' => $business, 'authority' => $authority, 'partners' => $partners];
    }

    public static function location(User $staff, string $kind, string $subjectId): void
    {
        app(VerifyAuditLocation::class)->handle($staff->id, $kind, $subjectId, 0, '-1.9441', '30.0619', 25,
            now('UTC')->format('Y-m-d\TH:i:s\Z'), 'synthetic:registered-coordinate', 'Reviewed location.', (string) Str::uuid());
    }

    public static function independence(User $staff, string $businessId, string $partyId, int $revision = 0): void
    {
        app(RecordAuditorIndependence::class)->handle($staff->id, $businessId, $partyId, $revision, AuditorIndependenceFixture::facts(),
            now('UTC')->format('Y-m-d\TH:i:s\Z'), 'synthetic:conflict-evidence', 'Reviewed current facts.', (string) Str::uuid());
    }

    /** @param Fixture $fixture */
    public static function request(array $fixture, string $kind = 'flash', ?string $requestId = null): AuditAssignment
    {
        $receipt = app(RequestAuditAssignment::class)->handle($fixture['staff']->id, $fixture['business'], $kind, 'Synthetic audit request.', $requestId ?? (string) Str::uuid());

        return AuditAssignment::query()->whereKey($receipt['data']['assignment_id'])->firstOrFail();
    }

    /**
     * @param  Fixture  $fixture
     * @return Partner
     */
    public static function recipient(array $fixture, AuditAssignment $assignment): array
    {
        return array_values(array_filter($fixture['partners'], fn (array $partner): bool => $partner['party']->id === $assignment->party_id))[0];
    }

    /** @return array<string, mixed> */
    public static function respond(User $user, AuditAssignment $assignment, string $decision = 'accept', string $reason = '', ?string $kind = null, ?string $requestId = null): array
    {
        return app(RespondToAuditAssignment::class)->handle($user->id, 1, $assignment->id, $assignment->revision, $decision, $kind, $reason, $requestId ?? (string) Str::uuid());
    }

    public static function engagement(string $partyId, string $status = 'accepted', ?string $businessId = null): AuditAssignment
    {
        $states = app(AuditEngagementState::class);
        $state = $states->accept($states->offer($states->start('routine', now()->toDateTimeImmutable()), $partyId, now()->toDateTimeImmutable()), now()->toDateTimeImmutable());

        return AuditAssignment::factory()->create(['business_id' => $businessId ?? BusinessProfile::factory()->create()->id,
            'party_id' => $partyId, 'state' => [...$state, 'status' => $status], 'status' => $status,
            'completed_at' => $status === 'completed' ? now() : null]);
    }
}
