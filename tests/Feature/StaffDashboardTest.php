<?php

declare(strict_types=1);

use App\Application\Business\Contracts\InvestorDealCatalogue;
use App\Application\Identity\ConfigureStaffAccess;
use App\Application\Identity\Contracts\InvestorDirectoryStore;
use App\Application\Identity\SaveInvestorVerification;
use App\Application\Identity\SubmitInvestorVerification;
use App\Application\Identity\UploadInvestorVerificationDocument;
use App\Application\Operations\Contracts\LedgerJournal;
use App\Application\Operations\Contracts\OperationsDashboardStore;
use App\Application\Operations\Contracts\ReconciliationStore;
use App\Models\Party;
use App\Models\PrimaryHolding;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\Support\AuditSealingFixture;
use Tests\Support\DisbursementFixture;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryHoldingFixture;
use Tests\Support\StaffFixture;

/** @param  list<string>  $roles */
function boardStaff(array $roles): User
{
    $user = User::factory()->withTwoFactor()->create();
    StaffFixture::provision($user, $roles, 'Operations Center assignment.');

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
                ['key' => 'frozen_accounts', 'count' => 0, 'link' => null]])
            ->where('breaks', null)->where('portfolio_health', ['total' => 0, 'healthy' => ['count' => 0, 'pct' => 0], 'watch' => ['count' => 0, 'pct' => 0], 'distressed' => null])->where('activity', null)->where('sector_exposure', [])->where('collections', null)
            ->where('capital_raised', ['from' => null, 'to' => null, 'grain' => 'year', 'bars' => []])
            ->where('funnel', array_map(fn (string $stage): array => ['stage' => $stage, 'count' => in_array($stage, ['submitted', 'matured'], true) ? null : 0, 'width_pct' => 0],
                ['submitted', 'live', 'funded', 'repaying', 'matured', 'failed']))
            ->where('pending_applications', [])
            ->where('treasury', ['invested' => ['currency' => 'RWF', 'amount' => '0'], 'disbursed' => ['currency' => 'RWF', 'amount' => '0'],
                'platform_net' => null, 'paid_to_investors' => null])
            ->where('nav.applications', null)->where('nav.events', null)->where('nav.staff.url', '/admin/staff'));
});

test('frozen accounts counts frozen operators and opens Staff & Roles once one is frozen', function (): void {
    $frozen = boardStaff(['analyst']);
    app(ConfigureStaffAccess::class)->handle($frozen->id, false, 'Left the team.', (string) Str::uuid(), ['analyst']);

    $this->actingAs(boardStaff(['treasury']))->get(route('staff.dashboard'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('attention.4', ['key' => 'frozen_accounts', 'count' => 1, 'link' => ['url' => '/admin/staff', 'method' => 'get']]));
});

test('the open reconciliation breaks show, oldest first and unowned, to whoever may view reconciliation', function (): void {
    $this->freezeSecond();
    $this->actingAs(boardStaff(['treasury']))->get(route('staff.dashboard'))->assertOk()->assertInertia(fn (Assert $page) => $page->where('breaks', []));

    InvestorWalletFixture::policy(maximum: null);
    $investor = InvestorWalletFixture::ready();
    InvestorWalletFixture::settle(InvestorWalletFixture::deposit($investor, '20000')['data']['intent_id'], eventId: 'evt-mismatch', amount: '25000');
    $this->travel(3)->days();

    $breaks = [];
    $this->actingAs(boardStaff(['compliance']))->get(route('staff.dashboard'))->assertOk()
        ->assertInertia(function (Assert $page) use (&$breaks): void {
            $breaks = $page->toArray()['props']['breaks'];
        });
    expect($breaks)->toHaveCount(1)->and($breaks[0])->toMatchArray(['reference' => 'evt-mismatch',
        'description' => 'Investor deposit reported successful for another amount, currency or environment, so the ledger never posted it',
        'gap' => ['currency' => 'RWF', 'amount' => '25000'], 'age_days' => 3, 'owner' => null, 'actions' => []])
        ->and($breaks[0]['opened_at'])->toBeString();

    $this->actingAs(boardStaff(['analyst']))->get(route('staff.dashboard'))->assertOk()->assertInertia(fn (Assert $page) => $page->where('breaks', null));
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
            ->where('badges', ['applications' => 0, 'disbursements' => 0])
            ->where('sector_exposure', [['sector' => (string) DB::table('business_profiles')->value('profile->industry'),
                'outstanding' => ['currency' => 'RWF', 'amount' => $total], 'notes' => 1, 'at_risk' => false]])
            ->where('portfolio_health', ['total' => 1, 'healthy' => ['count' => 1, 'pct' => 100], 'watch' => ['count' => 0, 'pct' => 0], 'distressed' => null]));

    // The Business's newest monthly report is on watch: so is its note, and its sector is at risk.
    $deals = $this->createMock(InvestorDealCatalogue::class);
    $deals->method('updates')->willReturn([['status' => 'watch'], ['status' => 'healthy']]);
    app()->instance(InvestorDealCatalogue::class, $deals);
    $this->actingAs($viewer)->get(route('staff.dashboard'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('sector_exposure.0.at_risk', true)
            ->where('portfolio_health', ['total' => 1, 'healthy' => ['count' => 0, 'pct' => 0], 'watch' => ['count' => 1, 'pct' => 100], 'distressed' => null]));

    // Kigali is UTC+2: 22:30 UTC on 1 March is already 2 March there.
    $window = fn (string $from, ?string $to = null): array => $this->actingAs($viewer)->get(route('staff.dashboard', array_filter(['from' => $from, 'to' => $to])))
        ->assertOk()->inertiaPage()['props']['capital_raised'];
    $labels = fn (array $chart): array => array_column($chart['bars'], 'label');
    expect($window('2027-03-02T00:00', '2027-03-02T12:00'))->toMatchArray(['grain' => 'hour', 'from' => '2027-03-02T00:00:00+02:00', 'to' => '2027-03-02T12:00:00+02:00'])
        ->and($labels($window('2027-03-02T00:00', '2027-03-02T12:00')))->toBe(['02/03 00:00'])
        ->and($labels($window('2027-02-20T00:00', '2027-03-10T00:00')))->toBe(['02/03'])
        ->and($window('2027-01-01T00:00', '2028-06-30T00:00')['grain'])->toBe('month')
        ->and($window('2027-01-01T00:00', '2028-06-30T00:00')['bars'])->toBe([
            ['label' => '01/2027', 'amount' => ['currency' => 'RWF', 'amount' => $principals[0]], 'height_pct' => 100],
            ['label' => '03/2027', 'amount' => ['currency' => 'RWF', 'amount' => $principals[1]], 'height_pct' => 100]])
        ->and($window('2020-01-01T00:00', '2030-01-01T00:00')['grain'])->toBe('year')
        ->and($labels($window('2027-03-01T00:00')))->toBe(['03/2027'])
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

test('the queues count applications waiting, submissions awaiting review and disbursements automatic payment has not queued', function (): void {
    $fixture = AuditSealingFixture::ready();
    AuditSealingFixture::seal($fixture);
    AuditSealingFixture::cosign($fixture);
    $application = $fixture['application'];
    boardSubmission(User::factory()->create(['name' => 'Aline Uwase', 'party_id' => Party::factory()]));
    DisbursementFixture::funded();

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

test('portfolio health reads each Business\'s newest report once, and puts every note it issued on watch with it', function (): void {
    $note = fn (string $campaign, string $business): array => ['campaign_id' => $campaign, 'business_id' => $business, 'state' => 'repaying',
        'live_at' => '2026-10-01T08:00:00+00:00'];
    app()->instance(OperationsDashboardStore::class, Mockery::mock(OperationsDashboardStore::class, [
        'noteStates' => [$note('01K000000000000000000000N1', '01K000000000000000000000B1'), $note('01K000000000000000000000N2', '01K000000000000000000000B1'),
            $note('01K000000000000000000000N3', '01K000000000000000000000B2')],
        'figures' => ['capital_raised' => '0', 'active_businesses' => 2, 'disbursements_waiting' => 0, 'disbursed' => '0',
            'notes' => ['live' => 0, 'funded' => 0, 'repaying' => 3, 'failed' => 0]],
        'sectorExposure' => [['sector' => 'Agriculture', 'principal' => '0', 'notes' => 2, 'campaigns' => ['01K000000000000000000000N1', '01K000000000000000000000N2']],
            ['sector' => 'Retail', 'principal' => '0', 'notes' => 1, 'campaigns' => ['01K000000000000000000000N3']]],
        'capitalRaised' => []]));
    $deals = $this->createMock(InvestorDealCatalogue::class);
    $deals->expects($this->exactly(2))->method('updates')
        ->willReturnCallback(fn (string $campaign): array => [['status' => $campaign === '01K000000000000000000000N3' ? 'healthy' : 'watch']]);
    app()->instance(InvestorDealCatalogue::class, $deals);

    $this->actingAs(boardStaff(['analyst']))->get(route('staff.dashboard'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('sector_exposure.0.at_risk', true)->where('sector_exposure.1.at_risk', false)
            ->where('portfolio_health', ['total' => 3, 'healthy' => ['count' => 1, 'pct' => 33], 'watch' => ['count' => 2, 'pct' => 67], 'distressed' => null]));
});

test('the Payments badge counts ready, held and legacy awaiting disbursements, never one already queued', function (): void {
    ['campaign' => $legacy] = PrimaryHoldingFixture::committed();
    PrimaryHoldingFixture::awaitingApproval($legacy);
    DisbursementFixture::funded();
    ['disbursement' => $held] = DisbursementFixture::funded();
    DisbursementFixture::command(DisbursementFixture::staff(['compliance']), $held, 'hold', 0);
    DisbursementFixture::paid();
    $badge = fn (string $role) => $this->actingAs(boardStaff([$role]))->get(route('staff.dashboard'))->assertOk()->inertiaPage()['props']['badges']['disbursements'];

    expect($badge('treasury'))->toBe(3)->and($badge('analyst'))->toBeNull();
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

test('a capital chart partial reload reads only the chart while retaining staff authorization', function (): void {
    $viewer = boardStaff(['superadmin']);
    $version = $this->actingAs($viewer)->get(route('staff.dashboard'))->assertOk()->inertiaPage()['version'];
    $store = $this->createMock(OperationsDashboardStore::class);
    $store->expects($this->once())->method('capitalRaised')->with('day', '2027-03-01T22:00:00+00:00', '2027-03-10T22:00:00+00:00')
        ->willReturn([['label' => '02/03', 'amount' => '5000', 'height_pct' => 100]]);
    foreach (['figures', 'noteStates', 'sectorExposure'] as $method) {
        $store->expects($this->never())->method($method);
    }
    app()->instance(OperationsDashboardStore::class, $store);
    app()->instance(InvestorDirectoryStore::class, Mockery::mock(InvestorDirectoryStore::class));
    app()->instance(ReconciliationStore::class, Mockery::mock(ReconciliationStore::class));
    app()->instance(LedgerJournal::class, Mockery::mock(LedgerJournal::class));

    $headers = ['X-Inertia' => 'true', 'X-Inertia-Partial-Component' => 'admin/today', 'X-Inertia-Partial-Data' => 'capital_raised', 'X-Inertia-Version' => $version];
    $this->actingAs($viewer)->get(route('staff.dashboard', ['from' => '2027-03-02T00:00', 'to' => '2027-03-11T00:00']), $headers)
        ->assertOk()->assertJsonPath('component', 'admin/today')
        ->assertJsonPath('props.capital_raised', ['from' => '2027-03-02T00:00:00+02:00', 'to' => '2027-03-11T00:00:00+02:00', 'grain' => 'day',
            'bars' => [['label' => '02/03', 'amount' => ['currency' => 'RWF', 'amount' => '5000'], 'height_pct' => 100]]])
        ->assertJsonMissingPath('props.breaks')->assertJsonMissingPath('props.activity')->assertJsonMissingPath('props.kpis');

    app(ConfigureStaffAccess::class)->handle($viewer->id, false, 'Access revoked.', (string) Str::uuid(), ['superadmin']);
    $this->actingAs($viewer)->get(route('staff.dashboard'), $headers)->assertForbidden();
    $headers['X-Inertia-Partial-Data'] = 'unknown';
    $this->actingAs($viewer)->get(route('staff.dashboard'), $headers)->assertForbidden();
});
