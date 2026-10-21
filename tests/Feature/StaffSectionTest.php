<?php

declare(strict_types=1);

use App\Application\Identity\ConfigureStaffAccess;
use App\Application\Operations\EngineSettings;
use App\Application\Operations\PlatformPolicies;
use App\Http\Controllers\StaffSectionController;
use App\Models\BusinessCampaign;
use App\Models\BusinessProfile;
use App\Models\Party;
use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\PrimaryIssuanceFixture;

/** @param  list<string>  $roles */
function sectionStaff(array $roles): User
{
    $user = User::factory()->withTwoFactor()->create();
    app(ConfigureStaffAccess::class)->handle($user->id, true, 'Console section assignment.', (string) Str::uuid(), $roles);

    return $user;
}

test('every design section opens the console frame with its empty state for any staff member', function (string $slug, string $section): void {
    $this->actingAs(sectionStaff(['analyst']))->get(route('staff.sections.show', ['section' => $slug]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/section')->where('section', $section)->where('search', '')
            ->where('viewer.role', 'analyst')->where('badges', ['applications' => null, 'disbursements' => null])
            ->where("nav.{$section}.url", '/admin/'.$slug)->where('nav.investors', null)->where('nav.payments', null)
            ->where('nav.compliance.url', '/admin/compliance')
            ->where('nav.businesses.url', '/admin/businesses')->where('nav.auditors', null));
})->with(fn (): array => array_map(fn (string $slug): array => [$slug, StaffSectionController::SECTIONS[$slug]],
    array_combine(array_keys(StaffSectionController::SECTIONS), array_keys(StaffSectionController::SECTIONS))));

test('Policies lists the rules the domain enforces under the design tabs, and other sections list none', function (): void {
    $rules = PlatformPolicies::rules();
    $this->actingAs(sectionStaff(['analyst']))->get('/admin/policies')->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/section')->where('section', 'policies')->has('rules', count($rules))
            ->where('rules.0', ['tab' => 'fees', 'key' => 'platform_fee', 'value' => '3%', 'version' => 'brs-2.0-fee-r01']));
    $values = array_column($rules, 'value', 'key');

    expect(array_intersect_key($values, array_flip(['target_dscr', 'rate_band', 'min_principal', 'max_principal', 'tenors',
        'first_issue_months', 'repeat_issue_months', 'minimum_age', 'complaint_resolve', 'withdrawal_max'])))->toBe([
            'target_dscr' => '1.50×', 'rate_band' => '10–15%', 'min_principal' => 'RWF 3,000,000', 'max_principal' => 'RWF 100,000,000',
            'tenors' => '3–6', 'first_issue_months' => '36', 'repeat_issue_months' => '12', 'minimum_age' => '18',
            'complaint_resolve' => '48 h', 'withdrawal_max' => 'RWF 2,000,000'])
        ->and(array_values(array_unique(array_column($rules, 'tab'))))->toBe(['fees', 'underwriting', 'business', 'kyc', 'penalties', 'limits']);
    $this->actingAs(sectionStaff(['analyst']))->get('/admin/risk')->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('rules', []));
});

test('Finance shows the fee revenue the ledger holds to whoever may read the ledger, and nothing to anyone else', function (): void {
    PrimaryIssuanceFixture::close('2026-10-09T22:30:00+00:00');
    $money = fn (string $amount): array => ['currency' => 'RWF', 'amount' => $amount];

    $this->actingAs(sectionStaff(['treasury']))->get('/admin/finance')->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/section')->where('section', 'finance')
            ->where('figures', ['revenue' => $money('194400'), 'primary' => $money('194400')])
            ->where('streams', [['key' => 'platform_fee', 'paid_by' => 'business', 'collected' => $money('194400')],
                ['key' => 'audit_partner_share', 'paid_by' => 'business', 'collected' => $money('129600')],
                ['key' => 'deposit_fees', 'paid_by' => 'investor', 'collected' => $money('0')]]));
    $this->actingAs(sectionStaff(['analyst']))->get('/admin/finance')->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('section', 'finance')->missing('figures')->missing('streams'));
});

test('Engines and Ratings show underwriters the engine settings and the scorecard, and nothing to anyone else', function (): void {
    $approver = sectionStaff(['approver']);
    $this->actingAs($approver)->get('/admin/engines')->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('section', 'engines')->where('engine', EngineSettings::engines()));
    $this->actingAs($approver)->get('/admin/ratings')->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('section', 'ratings')->where('engine', EngineSettings::scorecard()));
    $this->actingAs($approver)->get('/admin/risk')->assertOk()->assertInertia(fn (Assert $page) => $page->where('engine', []));
    $this->actingAs(sectionStaff(['analyst']))->get('/admin/engines')->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('section', 'engines')->where('engine', []));

    $engines = array_column(EngineSettings::engines(), 'value', 'key');
    $scorecard = array_column(EngineSettings::scorecard(), 'value', 'key');
    expect($engines)->toBe(['target_dscr' => '1.50×', 'minimum_dscr' => '1.25×', 'repeat_monthly_dscr' => '1.35×',
        'min_principal' => 'RWF 3,000,000', 'max_principal' => 'RWF 100,000,000', 'issuance_unit' => 'RWF 5,000',
        'first_issue_months' => '36', 'repeat_issue_months' => '12', 'tenors' => '3–6', 'rate_band' => '10–15%',
        'platform_fee' => '3%', 'cpa_share' => '40%'])
        ->and($scorecard)->toBe(['coverage' => '30% · 1.00×–1.50×', 'cfads_margin' => '20% · 5%–25%', 'nocf_stability' => '20% · 10%–60%',
            'positive_months' => '10% · 50%–100%', 'history_depth' => '5% · 6–18', 'conduct' => '15%', 'adverse_conduct_cap' => '2.9',
            'default_cap' => '1.9', 'strong' => '≥ 4.0', 'stable' => '≥ 3.0', 'weak' => '≥ 2.0', 'distressed' => '< 2.0'])
        ->and(array_values(array_unique(array_column(EngineSettings::scorecard(), 'tab'))))->toBe(['weights', 'bands']);
});

test('Ratings lists every rated Business and counts them for whoever may view Businesses', function (): void {
    $rated = BusinessCampaign::factory()->create(['payload' => ['title' => 'Seed stock', 'public_evidence' => ['rating' => ['band' => 'stable', 'score' => '3.1']]]]);
    BusinessCampaign::factory()->create(['payload' => ['title' => 'Unrated']]);
    $profile = BusinessProfile::query()->whereKey($rated->business_id)->sole();
    $profile->forceFill(['profile' => [...$profile->profile, 'name' => 'Isoko Farms', 'industry' => 'Agriculture']])->save();

    $this->actingAs(sectionStaff(['analyst']))->get('/admin/ratings')->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('section', 'ratings')->where('counts', ['rated' => 1])
            ->where('book', [['key' => $rated->business_id, 'business' => 'Isoko Farms', 'sector' => 'Agriculture',
                'rating' => ['band' => 'stable', 'score' => '3.1']]])->where('engine', []));
    $this->actingAs(sectionStaff(['treasury']))->get('/admin/risk')->assertOk()
        ->assertInertia(fn (Assert $page) => $page->missing('book')->missing('counts'));
});

test('the frame names the most privileged role, and Payments and Compliance open their live queues for whoever may see them', function (): void {
    $this->actingAs(sectionStaff(['approver']))->get(route('staff.sections.show', ['section' => 'secondary-market']))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('nav.payments.url', '/admin/disbursements'));
    $this->actingAs(sectionStaff(['approver', 'superadmin']))->get(route('staff.sections.show', ['section' => 'secondary-market']))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('viewer.role', 'superadmin')
            ->where('nav.payments.url', '/admin/payments')->where('nav.compliance.url', '/admin/investor-verifications')
            ->where('nav.investors.url', '/admin/investors')
            ->where('nav.businesses.url', '/admin/businesses')->where('nav.auditors.url', '/admin/auditors'));
});

test('Compliance opens the investor identity review queue for compliance officers, and the design page otherwise', function (): void {
    $this->actingAs(sectionStaff(['compliance']))->get(route('staff.sections.show', ['section' => 'secondary-market']))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('nav.compliance.url', '/admin/investor-verifications'));
    $this->actingAs(sectionStaff(['compliance']))->getJson(route('api.v1.staff.investor-verifications.index'))->assertOk()
        ->assertJsonPath('data.nav.compliance.url', '/api/v1/staff/investor-verifications');
    $this->actingAs(sectionStaff(['approver']))->get(route('staff.sections.show', ['section' => 'secondary-market']))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('nav.compliance.url', '/admin/compliance'));
    $this->actingAs(sectionStaff(['compliance']))->get(route('staff.sections.show', ['section' => 'compliance']))
        ->assertRedirect(route('staff.investor-verifications.index'));
});

test('the frame shows the same two-letter initials as every other console page', function (): void {
    $user = sectionStaff(['analyst']);
    $user->forceFill(['name' => 'Erastus Nkurunziza'])->save();
    $this->actingAs($user)->get(route('staff.sections.show', ['section' => 'secondary-market']))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('viewer.initials', 'EN'));
});

test('the Business directory, Audit Partner network, Primary Market and Payment Center are live screens, not pending sections', function (): void {
    expect(StaffSectionController::SECTIONS)->not->toHaveKeys(['businesses', 'auditors', 'primary-market', 'payments']);
    $this->actingAs(sectionStaff(['analyst']))->get('/admin/businesses')->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/parties')->where('kind', 'business'));
    $this->actingAs(sectionStaff(['analyst']))->get('/admin/auditors')->assertForbidden();
});

test('participants and guests cannot open a console section, and unknown sections do not exist', function (): void {
    $this->actingAs(User::factory()->create(['party_id' => Party::factory()]))->get(route('staff.sections.show', ['section' => 'secondary-market']))
        ->assertForbidden()->assertInertia(fn (Assert $page) => $page->component('identity/access-denied')->where('code', 'STAFF_ACCESS_REQUIRED'));
    $this->actingAs(sectionStaff(['analyst']))->get('/admin/everything')->assertNotFound();
    auth()->logout();
    $this->get(route('staff.sections.show', ['section' => 'secondary-market']))->assertRedirect(route('login'));
});

test('Oversight lists the design\'s Reports, which opens its section page from the sidebar and the Audit desk command', function (): void {
    expect(StaffSectionController::SECTIONS)->toHaveKey('reports')->and(array_slice(array_keys(StaffSectionController::SECTIONS), 1, 3))
        ->toBe(['reports', 'risk', 'compliance']);
    $this->actingAs(sectionStaff(['analyst']))->get('/admin/reports')->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/section')->where('section', 'reports')
            ->where('nav.reports', ['url' => '/admin/reports', 'method' => 'get'])
            ->where('nav.policies', ['url' => '/admin/policies', 'method' => 'get']));
    $this->actingAs(sectionStaff(['analyst']))->get(route('staff.dashboard'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/today')
            ->where('nav.reports', ['url' => '/admin/reports', 'method' => 'get'])
            ->where('nav.policies', ['url' => '/admin/policies', 'method' => 'get']));
});
