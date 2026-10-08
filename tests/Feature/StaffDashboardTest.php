<?php

declare(strict_types=1);

use App\Application\Identity\ConfigureStaffAccess;
use App\Application\Identity\SaveInvestorVerification;
use App\Application\Identity\SubmitInvestorVerification;
use App\Application\Identity\UploadInvestorVerificationDocument;
use App\Models\Party;
use App\Models\PrimaryHolding;
use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\Support\AuditSealingFixture;
use Tests\Support\DisbursementFixture;
use Tests\Support\PrimaryHoldingFixture;

/** @param  list<string>  $roles */
function boardStaff(array $roles): User
{
    $user = User::factory()->withTwoFactor()->create();
    app(ConfigureStaffAccess::class)->handle($user->id, true, 'Operations Center assignment.', (string) Str::uuid(), $roles);

    return $user;
}

/** A person's own identity submission, sent for review through the participant commands. */
function boardSubmission(User $user): void
{
    $png = "\x89PNG\r\n\x1a\nsynthetic identity image";
    $revision = 0;
    $step = function (array $result) use (&$revision): void {
        expect($result['status'])->toBe('completed');
        $revision++;
    };
    $step(app(SaveInvestorVerification::class)->handle($user->id, $user->context_revision, $revision, 'personal', ['date_of_birth' => '1/5/1990'], (string) Str::uuid()));
    foreach (['id_front', 'id_back'] as $side) {
        $step(app(UploadInvestorVerificationDocument::class)->handle($user->id, $user->context_revision, $revision, $side, $side.'.png', $png, (string) Str::uuid()));
    }
    $step(app(SaveInvestorVerification::class)->handle($user->id, $user->context_revision, $revision, 'document', ['id_type' => 'national_id', 'id_number' => '1 1990 8 0012345 6 78'], (string) Str::uuid()));
    $step(app(UploadInvestorVerificationDocument::class)->handle($user->id, $user->context_revision, $revision, 'selfie', 'selfie.png', $png, (string) Str::uuid()));
    $step(app(SubmitInvestorVerification::class)->handle($user->id, $user->context_revision, $revision, (string) Str::uuid()));
}

/**
 * One campaign whose two purchases issued, with each Holding's issue time.
 *
 * @param  list<string>  $issuedAt
 * @return list<string>
 */
function boardIssued(array $issuedAt): array
{
    ['campaign' => $campaign, 'commitments' => $commitments] = PrimaryHoldingFixture::committed();
    $closing = PrimaryHoldingFixture::issuedClosing($campaign);
    foreach ($commitments as $index => $commitment) {
        PrimaryHoldingFixture::insert($commitment->id, $closing, ['issued_at' => $issuedAt[$index]]);
        PrimaryHoldingFixture::issue($commitment->id, $closing);
    }
    PrimaryHoldingFixture::flushDeferredChecks();

    return array_map(fn ($commitment): string => (string) PrimaryHolding::query()->where('commitment_id', $commitment->id)->sole()->principal, $commitments);
}

test('every staff member opens the Operations Center; participants and guests do not', function (): void {
    $this->actingAs(boardStaff(['analyst']))->get(route('staff.dashboard'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/today')->where('contract_version', 'staff-dashboard-v1')
            ->where('viewer.role', 'analyst')->where('nav.today.url', '/admin/dashboard')->where('search', ''));
    $this->actingAs(User::factory()->create(['party_id' => Party::factory()]))->get(route('staff.dashboard'))->assertForbidden();
    auth()->logout();
    $this->get(route('staff.dashboard'))->assertRedirect(route('login'));
});

test('an empty platform shows zero figures, empty queues and every untracked figure as not tracked', function (): void {
    $this->actingAs(boardStaff(['analyst']))->get(route('staff.dashboard'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('badges', ['applications' => null, 'disbursements' => null])
            ->where('kpis', [['key' => 'capital_raised', 'value' => ['kind' => 'money', 'value' => ['currency' => 'RWF', 'amount' => '0']]],
                ['key' => 'active_businesses', 'value' => ['kind' => 'count', 'value' => 0]], ['key' => 'verified_investors', 'value' => ['kind' => 'count', 'value' => 0]],
                ['key' => 'outstanding_notes', 'value' => ['kind' => 'count', 'value' => 0]], ['key' => 'treasury_position', 'value' => null],
                ['key' => 'default_rate', 'value' => null], ['key' => 'secondary_volume', 'value' => null]])
            // An analyst cannot review applications, so the queue is not read at all.
            ->where('attention', [['key' => 'applications_pending', 'count' => null, 'link' => null], ['key' => 'kyc_awaiting', 'count' => 0, 'link' => null],
                ['key' => 'notes_late', 'count' => null, 'link' => null], ['key' => 'notes_default_risk', 'count' => null, 'link' => null],
                ['key' => 'frozen_accounts', 'count' => null, 'link' => null]])
            ->where('breaks', null)->where('portfolio_health', null)->where('activity', null)->where('sector_exposure', null)->where('collections', null)
            ->where('capital_raised', ['from' => null, 'to' => null, 'grain' => 'year', 'bars' => []])
            ->where('funnel', array_map(fn (string $stage): array => ['stage' => $stage, 'count' => in_array($stage, ['submitted', 'matured'], true) ? null : 0, 'width_pct' => 0],
                ['submitted', 'live', 'funded', 'repaying', 'matured', 'failed']))
            ->where('pending_applications', [])
            ->where('treasury', ['invested' => ['currency' => 'RWF', 'amount' => '0'], 'disbursed' => ['currency' => 'RWF', 'amount' => '0'],
                'platform_net' => null, 'paid_to_investors' => null])
            ->where('nav.applications', null)->where('nav.events', null)->where('nav.staff.url', '/admin/staff'));
});

test('issued notes fill capital raised, outstanding notes, verified Investors and the treasury, bucketed by issue time', function (): void {
    $principals = boardIssued(['2027-01-31T09:00:00Z', '2027-03-01T22:30:00Z']);
    $total = (string) array_sum($principals);
    $viewer = boardStaff(['superadmin']);

    $this->actingAs($viewer)->get(route('staff.dashboard'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('kpis.0.value.value.amount', $total)->where('kpis.1.value.value', 1)->where('kpis.2.value.value', 2)->where('kpis.3.value.value', 1)
            ->where('funnel.3', ['stage' => 'repaying', 'count' => 1, 'width_pct' => 100])->where('funnel.1', ['stage' => 'live', 'count' => 0, 'width_pct' => 0])
            ->where('treasury.invested.amount', $total)->where('treasury.disbursed.amount', $total)
            ->where('capital_raised', ['from' => null, 'to' => null, 'grain' => 'year',
                'bars' => [['label' => '2027', 'amount' => ['currency' => 'RWF', 'amount' => $total], 'height_pct' => 100]]])
            ->where('attention.1.link', ['url' => '/admin/investors?chip=pending', 'method' => 'get'])
            ->where('attention.0.link', ['url' => '/admin/applications', 'method' => 'get'])
            ->where('badges', ['applications' => 0, 'disbursements' => 0]));

    // Kigali is UTC+2: 22:30 UTC on 1 March is already 2 March there.
    $window = fn (string $from, ?string $to = null): array => $this->actingAs($viewer)->get(route('staff.dashboard', array_filter(['from' => $from, 'to' => $to])))
        ->assertOk()->inertiaPage()['props']['capital_raised'];
    $labels = fn (array $chart): array => array_column($chart['bars'], 'label');
    expect($window('2027-03-02T00:00', '2027-03-02T12:00'))->toMatchArray(['grain' => 'hour', 'from' => '2027-03-02T00:00:00+02:00', 'to' => '2027-03-02T12:00:00+02:00'])
        ->and($labels($window('2027-03-02T00:00', '2027-03-02T12:00')))->toBe(['02 00:00'])
        ->and($labels($window('2027-02-20T00:00', '2027-03-10T00:00')))->toBe(['03-02'])
        ->and($window('2027-01-01T00:00', '2028-06-30T00:00')['grain'])->toBe('month')
        ->and($window('2027-01-01T00:00', '2028-06-30T00:00')['bars'])->toBe([
            ['label' => '2027-01', 'amount' => ['currency' => 'RWF', 'amount' => $principals[0]], 'height_pct' => 100],
            ['label' => '2027-03', 'amount' => ['currency' => 'RWF', 'amount' => $principals[1]], 'height_pct' => 100]])
        ->and($window('2020-01-01T00:00', '2030-01-01T00:00')['grain'])->toBe('year')
        ->and($labels($window('2027-03-01T00:00')))->toBe(['2027-03'])
        ->and($window('2027-03-03T00:00', '2027-03-04T00:00')['bars'])->toBe([]);
    $this->actingAs($viewer)->get(route('staff.dashboard', ['from' => '1 March']))->assertSessionHasErrors('from');
});

test('the lifecycle places each published campaign where it sits now', function (): void {
    PrimaryHoldingFixture::committed(fund: false);
    PrimaryHoldingFixture::committed();
    ['campaign' => $failed] = PrimaryHoldingFixture::committed();
    PrimaryHoldingFixture::issuedClosing($failed, [], 'failed_closing');
    PrimaryHoldingFixture::flushDeferredChecks();

    $this->actingAs(boardStaff(['analyst']))->get(route('staff.dashboard'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('kpis.3.value.value', 2)
            ->where('funnel', [['stage' => 'submitted', 'count' => null, 'width_pct' => 0], ['stage' => 'live', 'count' => 1, 'width_pct' => 100],
                ['stage' => 'funded', 'count' => 1, 'width_pct' => 100], ['stage' => 'repaying', 'count' => 0, 'width_pct' => 0],
                ['stage' => 'matured', 'count' => null, 'width_pct' => 0], ['stage' => 'failed', 'count' => 1, 'width_pct' => 100]]));
});

test('the queues count applications waiting, submissions awaiting review and disbursements awaiting a checker', function (): void {
    $fixture = AuditSealingFixture::ready();
    AuditSealingFixture::seal($fixture);
    AuditSealingFixture::cosign($fixture);
    $application = $fixture['application'];
    boardSubmission(User::factory()->create(['name' => 'Aline Uwase', 'party_id' => Party::factory()]));
    ['disbursement' => $disbursement] = DisbursementFixture::funded();
    DisbursementFixture::command(DisbursementFixture::staff(['treasury']), $disbursement, 'authorize', 0);

    $this->actingAs(boardStaff(['approver']))->get(route('staff.dashboard'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('badges', ['applications' => 1, 'disbursements' => 1])
            ->where('attention.0', ['key' => 'applications_pending', 'count' => 1, 'link' => ['url' => '/admin/applications', 'method' => 'get']])
            ->where('attention.1', ['key' => 'kyc_awaiting', 'count' => 1, 'link' => null])
            ->where('funnel.0', ['stage' => 'submitted', 'count' => 1, 'width_pct' => 100])
            ->has('pending_applications', 1)->where('pending_applications.0.id', $application->id)
            ->where('pending_applications.0.requested', ['currency' => 'RWF', 'amount' => '12000000'])
            ->where('pending_applications.0.link', ['url' => '/admin/applications?application='.$application->id, 'method' => 'get'])
            ->where('pending_applications.0.business', fn (string $name): bool => $name !== ''));
    // Without applications.review the queue is never read: no count, no funnel figure and no rows.
    foreach (['compliance', 'analyst', 'treasury'] as $role) {
        $this->actingAs(boardStaff([$role]))->get(route('staff.dashboard'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('badges.applications', null)
                ->where('attention.0', ['key' => 'applications_pending', 'count' => null, 'link' => null])
                ->where('funnel.0', ['stage' => 'submitted', 'count' => null, 'width_pct' => 0])
                ->where('pending_applications', []));
    }
    $this->actingAs(boardStaff(['compliance']))->get(route('staff.dashboard'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('badges.disbursements', 1)->where('attention.1.link.url', '/admin/investors?chip=pending')
            ->where('nav.events.url', '/admin/activity'));
});

test('the API mirror withholds pending application details from staff without applications.review', function (): void {
    $fixture = AuditSealingFixture::ready();
    AuditSealingFixture::seal($fixture);
    AuditSealingFixture::cosign($fixture);

    Sanctum::actingAs(boardStaff(['compliance']), ['staff:dashboard:read']);
    $this->getJson('/api/v1/staff/dashboard')->assertOk()
        ->assertJsonPath('data.pending_applications', [])
        ->assertJsonPath('data.attention.0.count', null)
        ->assertJsonPath('data.funnel.0.count', null)
        ->assertJsonMissing(['id' => $fixture['application']->id]);

    Sanctum::actingAs(boardStaff(['approver']), ['staff:dashboard:read']);
    $this->getJson('/api/v1/staff/dashboard')->assertOk()
        ->assertJsonPath('data.pending_applications.0.id', $fixture['application']->id)
        ->assertJsonPath('data.attention.0.count', 1);
});

test('the Operations Center answers over the API with the read ability and links API routes', function (): void {
    $viewer = boardStaff(['superadmin']);

    Sanctum::actingAs($viewer, ['staff:dashboard:read']);
    $this->getJson('/api/v1/staff/dashboard')->assertOk()
        ->assertJsonPath('data.contract_version', 'staff-dashboard-v1')
        ->assertJsonPath('data.nav.today.url', '/api/v1/staff/dashboard')
        ->assertJsonPath('data.attention.1.link.url', '/api/v1/staff/investors?chip=pending');
    Sanctum::actingAs($viewer, ['staff:investors:read']);
    $this->getJson('/api/v1/staff/dashboard')->assertForbidden();
});
