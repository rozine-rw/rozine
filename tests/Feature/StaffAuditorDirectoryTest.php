<?php

declare(strict_types=1);

use App\Application\Auditor\SetAuditorAvailability;
use App\Application\Auditor\SubmitAuditorAccreditation;
use App\Application\Auditor\WithdrawAuditorAccreditation;
use App\Application\Identity\ConfigureStaffAccess;
use App\Domain\Auditor\AccreditationProfile;
use App\Domain\Auditor\AuditorStanding;
use App\Models\AuditorProfile;
use App\Models\BusinessProfile;
use App\Models\Party;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\Support\AuditAssignmentFixture;
use Tests\Support\AuditorFixture;

beforeEach(function (): void {
    $this->officer = auditorDirectoryStaff(['compliance']);
});

/** @param  list<string>  $roles */
function auditorDirectoryStaff(array $roles): User
{
    $user = User::factory()->withTwoFactor()->create();
    app(ConfigureStaffAccess::class)->handle($user->id, true, 'Audit Partner network assignment.', (string) Str::uuid(), $roles);

    return $user;
}

/** @return array{user: User, party: Party, staff: User} */
function auditorDirectoryPartner(string $name): array
{
    $partner = AuditorFixture::make();
    $partner['user']->forceFill(['name' => $name])->save();

    return $partner;
}

/** A partner whose recorded standing is as given, with no submission waiting. */
function auditorDirectoryStanding(string $name, string $status, string $expiresOn): AuditorProfile
{
    $partner = auditorDirectoryPartner($name);
    $state = (new AccreditationProfile(new AuditorStanding))->empty();
    $state['standing'] = ['status' => $status, 'licence' => 'PPC-'.Str::upper(Str::random(4)), 'expires_on' => $expiresOn,
        'checked_at' => now('UTC')->format('Y-m-d\TH:i:s\Z'), 'check_reference' => 'synthetic-register-check'];
    $state['certificate_id'] = strtolower((string) Str::ulid());

    return AuditorProfile::factory()->create(['party_id' => $partner['party']->id, 'state' => $state]);
}

test('staff who verify Audit Partners see the designed network; other staff and guests do not', function (): void {
    $this->actingAs($this->officer)->get(route('staff.auditors.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/parties')->where('kind', 'auditor')->where('viewer.role', 'compliance')
            ->where('contract_version', 'staff-auditor-directory-v1')->where('nav.auditors.url', '/admin/auditors')
            ->where('nav.businesses.url', '/admin/businesses')->where('policy', [])->where('filters', [])->where('party', null));
    $this->actingAs(auditorDirectoryStaff(['approver']))->get(route('staff.auditors.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('viewer.role', 'approver'));
    $this->actingAs(auditorDirectoryStaff(['superadmin', 'approver']))->get(route('staff.auditors.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('viewer.role', 'superadmin'));
    $this->actingAs(auditorDirectoryStaff(['analyst']))->get(route('staff.auditors.index'))->assertForbidden();
    auth()->logout();
    $this->get(route('staff.auditors.index'))->assertRedirect(route('login'));
});

test('an empty network shows the design with zero figures and no rows', function (): void {
    $this->actingAs($this->officer)->get(route('staff.auditors.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats', [['key' => 'partners', 'value' => ['kind' => 'count', 'value' => 0]],
                ['key' => 'active_partners', 'value' => ['kind' => 'count', 'value' => 0]],
                ['key' => 'pending_partners', 'value' => ['kind' => 'count', 'value' => 0]],
                ['key' => 'licences_expiring', 'value' => ['kind' => 'count', 'value' => 0]]])
            ->where('chips.0', ['key' => 'all', 'count' => 0, 'link' => ['url' => '/admin/auditors', 'method' => 'get'], 'active' => true])
            ->where('chips', fn (Collection $chips): bool => $chips->pluck('count', 'key')->all() === ['all' => 0, 'active' => 0, 'pending' => 0, 'licence_expired' => 0])
            ->where('shown', 0)->where('total', 0)->where('directory', ['kind' => 'auditor', 'rows' => []]));
});

test('rows carry real standing, licences and engagements, and the chips and search narrow them', function (): void {
    $joined = auditorDirectoryPartner('Aline Mukamana');
    $waiting = auditorDirectoryPartner('Bosco Habimana');
    AuditorFixture::submit($waiting['user']);
    $verified = auditorDirectoryPartner('Claude Niyonzima');
    $submission = AuditorFixture::submit($verified['user']);
    AuditorFixture::review($verified['staff'], $verified['party']->id, 1, 'approve', $submission['data']['submission_id']);
    $business = BusinessProfile::factory()->create();
    $business->forceFill(['profile' => [...$business->profile, 'name' => 'Isoko Farms', 'district' => 'Huye']])->save();
    $engagement = AuditAssignmentFixture::engagement($verified['party']->id, 'accepted', $business->id);
    AuditAssignmentFixture::engagement($verified['party']->id, 'completed');
    $expiring = auditorDirectoryStanding('Diane Uwase', 'active', now('Africa/Kigali')->addDays(10)->format('Y-m-d'));
    auditorDirectoryStanding('Eric Nkusi', 'active', now('Africa/Kigali')->subDay()->format('Y-m-d'));
    auditorDirectoryStanding('Fabrice Gatete', 'revoked', now('Africa/Kigali')->addYear()->format('Y-m-d'));

    $this->actingAs($this->officer)->get(route('staff.auditors.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats', [['key' => 'partners', 'value' => ['kind' => 'count', 'value' => 6]],
                ['key' => 'active_partners', 'value' => ['kind' => 'count', 'value' => 2]],
                ['key' => 'pending_partners', 'value' => ['kind' => 'count', 'value' => 2]],
                ['key' => 'licences_expiring', 'value' => ['kind' => 'count', 'value' => 1]]])
            ->where('chips', fn (Collection $chips): bool => $chips->pluck('count', 'key')->all() === ['all' => 6, 'active' => 2, 'pending' => 2, 'licence_expired' => 1])
            ->where('shown', 6)->where('total', 6)
            ->where('directory.rows', function (Collection $rows) use ($joined, $verified, $expiring): bool {
                return $rows->pluck('name')->all() === ['Aline Mukamana', 'Bosco Habimana', 'Claude Niyonzima', 'Diane Uwase', 'Eric Nkusi', 'Fabrice Gatete']
                    && $rows->pluck('standing')->all() === ['pending', 'pending', 'active', 'active', 'licence_expired', 'suspended']
                    && $rows->pluck('licence')->take(3)->all() === [null, 'SYNTHETIC-CPA', 'SYNTHETIC-CPA']
                    && $rows->firstWhere('name', 'Claude Niyonzima') === ['id' => $verified['party']->id, 'name' => 'Claude Niyonzima', 'firm' => null,
                        'licence' => 'SYNTHETIC-CPA', 'district' => null, 'active_engagements' => 1, 'on_time_pct' => null, 'share_mtd' => null,
                        'standing' => 'active', 'frozen' => false, 'link' => ['url' => '/admin/auditors?auditor='.$verified['party']->id, 'method' => 'get']]
                    && $rows->firstWhere('name', 'Diane Uwase')['licence'] === $expiring->state['standing']['licence']
                    && $rows->firstWhere('name', 'Aline Mukamana')['id'] === $joined['party']->id;
            }));

    $this->actingAs($this->officer)->get(route('staff.auditors.index', ['chip' => 'pending', 'q' => 'bosco']))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('search', 'bosco')->where('stats.0.value.value', 1)
            ->where('chips.2', ['key' => 'pending', 'count' => 1, 'link' => ['url' => '/admin/auditors?q=bosco&chip=pending', 'method' => 'get'], 'active' => true])
            ->where('shown', 1)->where('total', 1)->where('directory.rows.0.name', 'Bosco Habimana')
            ->where('directory.rows.0.link.url', '/admin/auditors?q=bosco&chip=pending&auditor='.$waiting['party']->id));
    foreach (['active' => 2, 'licence_expired' => 1] as $chip => $count) {
        $this->actingAs($this->officer)->get(route('staff.auditors.index', ['chip' => $chip]))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('total', $count));
    }
    $this->actingAs($this->officer)->get(route('staff.auditors.index', ['chip' => 'suspended']))->assertSessionHasErrors('chip');

    $this->actingAs($this->officer)->get(route('staff.auditors.index', ['auditor' => $verified['party']->id]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('party.kind', 'auditor')->where('party.name', 'Claude Niyonzima')
            ->where('party.subtitle', $verified['user']->email)->where('party.health', 'active')
            ->where('party.stats', [['key' => 'engagements', 'value' => ['kind' => 'count', 'value' => 1]]])
            ->where('party.list', ['key' => 'engagements', 'rows' => [['id' => $engagement->id, 'title' => 'Isoko Farms', 'tone' => 'blue', 'detail' => ['kind' => 'text', 'value' => 'Huye']]]])
            ->where('party.licence', ['member_id' => null, 'licence' => 'SYNTHETIC-CPA', 'expires_on' => now()->addYear()->format('Y-m-d'),
                'district' => null, 'state' => 'verified', 'review' => null])
            ->where('party.history.0.action', ['code' => 'accreditation.approved', 'label' => 'Licence verified', 'tone' => 'green'])
            ->where('party.history.0.actor', $verified['staff']->name)->where('party.history.0.reason', 'Synthetic reviewed evidence.')
            ->where('party.history.1.action.label', 'Submitted a licence for verification')->where('party.history.1.actor', 'Claude Niyonzima')
            ->where('party.kyc', null)->where('party.actions', []));
    $this->actingAs($this->officer)->get(route('staff.auditors.index', ['auditor' => $joined['party']->id]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('party.health', 'kyc_pending')->where('party.licence', null)->where('party.history', [])
            ->where('party.list.rows', []));
    $this->actingAs($this->officer)->get(route('staff.auditors.index', ['auditor' => auditorDirectoryStanding('Gloria Ineza', 'active', now('Africa/Kigali')->subDay()->format('Y-m-d'))->party_id]))
        ->assertOk()->assertInertia(fn (Assert $page) => $page->where('party.health', 'watch')->where('party.licence.state', 'expired'));
    $this->actingAs($this->officer)->get(route('staff.auditors.index', ['auditor' => strtolower((string) Str::ulid())]))->assertNotFound();
});

test("a partner's 360 reads every accreditation event, and a waiting licence carries verify and reject", function (): void {
    $partner = auditorDirectoryPartner('Diane Uwase');
    $user = $partner['user'];
    $first = AuditorFixture::submit($user);
    app(WithdrawAuditorAccreditation::class)->handle($user->id, 1, 1, $first['data']['submission_id'], (string) Str::uuid());
    $second = AuditorFixture::submit($user, 2);
    AuditorFixture::review($partner['staff'], $partner['party']->id, 3, 'reject', $second['data']['submission_id']);
    $third = AuditorFixture::submit($user, 4);
    AuditorFixture::review($partner['staff'], $partner['party']->id, 5, 'approve', $third['data']['submission_id']);
    app(SetAuditorAvailability::class)->handle($user->id, 1, 6, true, (string) Str::uuid());
    $renewal = app(SubmitAuditorAccreditation::class)->handle($user->id, 1, 7, 'PPC-0412', now()->addYears(2)->format('Y-m-d'),
        'renewal.pdf', "%PDF-1.7\nSynthetic renewal\n%%EOF", (string) Str::uuid(), true);
    AuditorFixture::review($partner['staff'], $partner['party']->id, 8, 'suspend');
    AuditorFixture::review($partner['staff'], $partner['party']->id, 9, 'revoke');

    $this->actingAs($this->officer)->get(route('staff.auditors.index', ['auditor' => $partner['party']->id, 'chip' => 'all']))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('directory.rows.0.standing', 'suspended')->where('party.health', 'distressed')
            ->where('party.history', fn (Collection $history): bool => $history->pluck('action.code')->all() === ['accreditation.revoked', 'accreditation.suspended',
                'accreditation.renewal_submitted', 'accreditation.availability', 'accreditation.approved', 'accreditation.submitted',
                'accreditation.rejected', 'accreditation.submitted', 'accreditation.withdrawn', 'accreditation.submitted'])
            ->where('party.history.0.action', ['code' => 'accreditation.revoked', 'label' => 'Standing revoked', 'tone' => 'red'])
            ->where('party.history.6.action', ['code' => 'accreditation.rejected', 'label' => 'Licence rejected', 'tone' => 'red'])
            ->where('party.licence', ['member_id' => null, 'licence' => 'PPC-0412', 'expires_on' => now()->addYears(2)->format('Y-m-d'), 'district' => null,
                'state' => 'pending', 'review' => ['revision' => 10, 'submission_id' => $renewal['data']['submission_id']]])
            ->where('party.actions', ['verify_licence' => ['url' => '/admin/auditors/'.$partner['party']->id.'/licence/approve', 'method' => 'post'],
                'reject_licence' => ['url' => '/admin/auditors/'.$partner['party']->id.'/licence/reject', 'method' => 'post']]));
});

test('staff verify or reject a waiting licence from the 360 with a reason, and a stale decision is refused there', function (): void {
    $partner = auditorDirectoryPartner('Bosco Habimana');
    $submission = AuditorFixture::submit($partner['user'])['data']['submission_id'];
    $decision = fn (array $extra = []): array => ['request_id' => (string) Str::uuid(), 'expected_revision' => 1, 'submission_id' => $submission,
        'reason' => 'ICPAR register checked today; practising certificate current.', ...$extra];
    $url = fn (string $decision): string => route('staff.auditors.licence.'.$decision, $partner['party']->id);

    $this->actingAs(auditorDirectoryStaff(['analyst']))->post($url('approve'), $decision())->assertForbidden();
    $this->actingAs($this->officer)->post($url('approve'), $decision(['reason' => '']))->assertSessionHasErrors(['reason' => 'Give the reason for this decision.']);
    $this->actingAs($this->officer)->post($url('approve'), $decision(['submission_id' => strtolower((string) Str::ulid())]))
        ->assertSessionHasErrors(['reason' => 'This licence submission is no longer waiting for review.']);
    $this->actingAs($this->officer)->post($url('reject'), $decision(['expected_revision' => 7]))
        ->assertSessionHasErrors(['reason' => "This partner's record changed since you opened it. Review it again."]);

    $approve = $decision();
    $this->actingAs($this->officer)->post($url('approve'), $approve)->assertRedirect(route('staff.auditors.index', ['auditor' => $partner['party']->id]));
    $standing = AuditorProfile::query()->where('party_id', $partner['party']->id)->sole()->state['standing'];
    expect($standing['status'])->toBe('active')->and($standing['licence'])->toBe('SYNTHETIC-CPA')
        ->and($standing['check_reference'])->toBe('admin-console:'.$approve['request_id']);

    $this->actingAs($this->officer)->get(route('staff.auditors.index', ['auditor' => $partner['party']->id]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('party.history.0.reason', 'ICPAR register checked today; practising certificate current.')
            ->where('party.history.0.actor', $this->officer->name)->where('party.actions', []));

    $other = auditorDirectoryPartner('Aline Mukamana');
    $waiting = AuditorFixture::submit($other['user'])['data']['submission_id'];
    $this->actingAs($this->officer)->post(route('staff.auditors.licence.reject', $other['party']->id), [...$decision(), 'submission_id' => $waiting,
        'reason' => 'Member ID not on the ICPAR register.'])->assertRedirect(route('staff.auditors.index', ['auditor' => $other['party']->id]));
    expect(AuditorProfile::query()->where('party_id', $other['party']->id)->sole()->state['submission'])
        ->toBe(['status' => 'rejected', 'id' => $waiting, 'reason' => 'Member ID not on the ICPAR register.']);
    // A rejected first submission leaves no licence to show and nothing to decide.
    $this->actingAs($this->officer)->get(route('staff.auditors.index', ['auditor' => $other['party']->id]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('party.licence', null)->where('party.actions', [])->where('directory.rows.0.licence', null)
            ->where('party.history.0.action.label', 'Licence rejected')->where('party.history.0.reason', 'Member ID not on the ICPAR register.'));
});

test('the network answers over the API with the read ability, and decisions need the verify ability', function (): void {
    $partner = auditorDirectoryPartner('Bosco Habimana');
    $submission = AuditorFixture::submit($partner['user'])['data']['submission_id'];
    $id = $partner['party']->id;

    Sanctum::actingAs($this->officer, ['staff:auditors:read']);
    $this->getJson('/api/v1/staff/auditors?auditor='.$id)->assertOk()
        ->assertJsonPath('data.contract_version', 'staff-auditor-directory-v1')
        ->assertJsonPath('data.nav.auditors.url', '/api/v1/staff/auditors')
        ->assertJsonPath('data.directory.rows.0.link.url', '/api/v1/staff/auditors?auditor='.$id)
        ->assertJsonPath('data.party.licence.review.submission_id', $submission)
        ->assertJsonPath('data.party.actions', []);
    $decision = ['request_id' => (string) Str::uuid(), 'expected_revision' => 1, 'submission_id' => $submission, 'reason' => 'Register checked.'];
    $this->postJson('/api/v1/staff/auditors/'.$id.'/licence/approve', $decision)->assertForbidden();

    Sanctum::actingAs($this->officer, ['staff:auditors:read', 'staff:auditors:verify']);
    $this->getJson('/api/v1/staff/auditors?auditor='.$id)->assertOk()
        ->assertJsonPath('data.party.actions.verify_licence.url', '/api/v1/staff/auditors/'.$id.'/licence/approve');
    $this->postJson('/api/v1/staff/auditors/'.$id.'/licence/approve', $decision)->assertOk()
        ->assertJsonPath('status', 'completed')->assertJsonPath('code', 'ACCREDITATION_REVIEWED')->assertJsonPath('policy_version', 'engineering-2026-09-23.4');

    Sanctum::actingAs($this->officer, ['staff:businesses:read']);
    $this->getJson('/api/v1/staff/auditors')->assertForbidden();
});
