<?php

declare(strict_types=1);

use App\Application\Auditor\Contracts\AuditReportPublicationStore;
use App\Domain\Operations\CommandRejection;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\AuditSealingFixture;
use Tests\Support\BusinessAuthorityFixture as AuthorityFixture;
use Tests\Support\PrimaryReservationFixture;

/*
 * The Business pages that were preview-only: Reports, Profile, Rating and Repayments. Each reads
 * real facts under current Business authority, or sends the empty value its design draws.
 */

beforeEach(function (): void {
    $this->freezeSecond();
    $this->withoutVite();
    $this->props = function (User $user, string $route, array $parameters, string $component): array {
        $props = $this->actingAs($user)->get(route($route, $parameters))->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page->component($component))->viewData('page')['props'];

        return array_diff_key($props, array_flip(['auth', 'name', 'locale', 'errors', 'sidebarOpen', 'nonLiveEnvironment', 'head']));
    };
    $this->business = function (string $kind = 'organization', int $people = 2, string $code = 'COMPANY-001'): array {
        $authority = AuthorityFixture::make($kind, $people, $code, 1);

        return [$authority, AuthorityFixture::configure($authority)['data']['business']['id']];
    };
    $this->link = fn (string $url, string $method = 'get'): array => ['url' => $url, 'method' => $method];
});

dataset('business pages', [
    'reports' => ['business.reports', 'business/reports'],
    'market' => ['business.market', 'business/market'],
    'profile' => ['business.profile', 'business/profile'],
    'rating' => ['business.rating', 'business/rating'],
    'repayments' => ['business.repayments.show', 'business/repayments'],
]);

it('sends a guest to sign in, refuses a person outside the mandate and validates the context', function (string $route, string $component): void {
    [$authority, $business] = ($this->business)();
    [$other] = ($this->business)(code: 'COMPANY-002');

    $this->get(route($route, ['business' => $business]))->assertRedirect(route('login'));
    $this->actingAs($other['users'][0])->get(route($route, ['business' => $business]))->assertNotFound();
    $this->actingAs($authority['users'][0])->getJson(route($route, ['business' => $business, 'identity_context_revision' => -1]))
        ->assertUnprocessable()->assertJsonValidationErrors('identity_context_revision');
    $this->actingAs($authority['users'][0])->get(route($route, ['business' => $business, 'identity_context_revision' => 1]))->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->component($component));
})->with('business pages');

it('opens Profile on the verified registration and mandate, with every design section and nothing editable', function (): void {
    [$authority, $business] = ($this->business)();
    $user = $authority['users'][0];
    $profile = ($this->link)("/business/{$business}/profile");
    $section = fn (string $key): array => ($this->link)("/business/{$business}/profile/{$key}");

    $props = ($this->props)($user, 'business.profile', ['business' => $business], 'business/profile');

    expect($props)->toMatchArray(['business' => ['name' => 'Synthetic business', 'address_line' => 'Gasabo', 'verified' => true, 'rating' => null],
        'section' => 'company', 'landing' => true, 'company' => null, 'provinces' => [], 'linked' => null, 'legal' => null, 'actions' => ['save_company' => null],
        'security' => ['two_factor' => false]])
        ->and(array_diff_key($props['registration'], ['people' => true]))->toBe(['name' => 'Synthetic business', 'company_code' => 'COMPANY-001',
            'industry' => 'Retail', 'district' => 'Gasabo', 'established_year' => 2020, 'signatories_required' => 1])
        ->and($props['registration']['people'])->toEqualCanonicalizing([
            ['name' => 'Verified person 0', 'roles' => ['controller', 'owner', 'signatory'], 'signatory' => true],
            ['name' => 'Verified person 1', 'roles' => ['controller', 'owner', 'signatory'], 'signatory' => false]])
        ->and($props['team'])->toEqualCanonicalizing([
            ['name' => 'Verified person 0', 'roles' => ['controller', 'owner', 'signatory'], 'permissions' => $props['team'][0]['permissions'], 'signatory' => true],
            ['name' => 'Verified person 1', 'roles' => ['controller', 'owner', 'signatory'], 'permissions' => $props['team'][1]['permissions'], 'signatory' => false]])
        ->and(array_merge(...array_column($props['team'], 'permissions')))->toContain('business.view')
        ->and($props['links'])->toBe(['home' => ($this->link)("/business/{$business}"), 'reports' => ($this->link)("/business/{$business}/reports"),
            'market' => ($this->link)("/business/{$business}/market"), 'profile' => $profile, 'launcher' => ($this->link)('/dashboard'), 'back' => $profile,
            'sections' => ['company' => $section('company'), 'security' => $section('security'), 'permissions' => $section('permissions'),
                'linked' => $section('linked'), 'support' => $section('support'), 'terms' => $section('terms'), 'privacy' => $section('privacy')],
            'security_settings' => ($this->link)('/settings/security'), 'sign_out' => ($this->link)('/logout', 'post')]);

    foreach (['company', 'security', 'permissions', 'linked', 'support', 'terms', 'privacy'] as $key) {
        expect(($this->props)($user, 'business.profile', ['business' => $business, 'section' => $key], 'business/profile'))
            ->toMatchArray(['section' => $key, 'landing' => false]);
    }
    $this->actingAs($user)->get("/business/{$business}/profile/billing")->assertNotFound();

    $user->forceFill(['two_factor_secret' => encrypt('secret'), 'two_factor_confirmed_at' => now()])->save();
    expect(($this->props)($user->refresh(), 'business.profile', ['business' => $business, 'section' => 'security'], 'business/profile')['security'])
        ->toBe(['two_factor' => true]);
});

it('opens Market with its shell links only, as no secondary-market read exists', function (): void {
    [$authority, $business] = ($this->business)();

    expect(($this->props)($authority['users'][0], 'business.market', ['business' => $business], 'business/market'))
        ->toBe(['links' => ['home' => ($this->link)("/business/{$business}"), 'reports' => ($this->link)("/business/{$business}/reports"),
            'market' => ($this->link)("/business/{$business}/market"), 'profile' => ($this->link)("/business/{$business}/profile"),
            'launcher' => ($this->link)('/dashboard')]]);
});

it('shows a sole trader without a company code, and the published rating once a raise is listed', function (): void {
    [$authority, $business] = ($this->business)('person', 1);

    expect(($this->props)($authority['users'][0], 'business.profile', ['business' => $business], 'business/profile')['registration'])
        ->toMatchArray(['company_code' => null, 'signatories_required' => 1, 'people' => [['name' => 'Verified person 0', 'roles' => ['controller', 'owner', 'signatory'], 'signatory' => true]]]);

    $campaign = PrimaryReservationFixture::campaign();
    $rating = $campaign->payload['public_evidence']['rating'];

    expect(($this->props)(User::query()->findOrFail($campaign->actor_user_id), 'business.profile', ['business' => $campaign->business_id], 'business/profile')['business']['rating'])
        ->toBe(['band' => $rating['band'], 'score' => $rating['score']]);
});

it('opens Rating over the real Home: pending before a published rating, then the published one, and computes nothing', function (): void {
    [$authority, $business] = ($this->business)();
    $user = $authority['users'][0];

    $home = ($this->props)($user, 'business.show', ['business' => $business], 'business/home');
    $props = ($this->props)($user, 'business.rating', ['business' => $business], 'business/rating');

    expect($props)->toBe(['home' => $home, 'rating' => null, 'refusal' => null, 'drift' => null, 'factors' => null, 'sizing' => null, 'financials' => null,
        'links' => ['close' => ($this->link)("/business/{$business}"), 'raise' => null]])
        ->and($home['links']['rating'])->toBe(($this->link)("/business/{$business}/rating"));

    $campaign = PrimaryReservationFixture::campaign();
    $rating = $campaign->payload['public_evidence']['rating'];
    $published = ($this->props)(User::query()->findOrFail($campaign->actor_user_id), 'business.rating', ['business' => $campaign->business_id], 'business/rating');

    expect($published['rating'])->toBe(['band' => $rating['band'], 'score' => $rating['score']])
        ->and($published['home']['rating'])->toBe($published['rating']);
});

it('opens Repayments on its empty state over the real Home while no note is servicing, offering only a top-up', function (): void {
    [$authority, $business] = ($this->business)();
    $user = $authority['users'][0];

    $home = ($this->props)($user, 'business.show', ['business' => $business], 'business/home');
    $props = ($this->props)($user, 'business.repayments.show', ['business' => $business], 'business/repayments');

    expect($props)->toMatchArray(['contract_version' => 'business-servicing-v1', 'identity_context_revision' => 1, 'allowed_actions' => [],
        'note' => null, 'servicing' => null, 'schedule' => [], 'ladder' => null, 'pay' => null, 'receipt' => null, 'recent' => [], 'home' => $home,
        'actions' => ['pay' => null]])
        ->and((array) $props['bases'])->toBe([])
        ->and($props['server_time'])->toBe(now()->toIso8601String())
        ->and($props['shell_links'])->toBe(['home' => ($this->link)("/business/{$business}"), 'launcher' => ($this->link)('/dashboard'),
            'reports' => ($this->link)("/business/{$business}/reports"), 'market' => ($this->link)("/business/{$business}/market"), 'profile' => ($this->link)("/business/{$business}/profile")])
        ->and($props['links'])->toBe(['close' => ($this->link)("/business/{$business}"), 'top_up' => ($this->link)("/business/{$business}/wallet?kind=deposit"),
            'operation' => ($this->link)("/business/{$business}/repayment-operations/{request_id}?command=repayment.pay&identity_context_revision=1")]);
});

it('lists no report before the first sealed monthly report, and names no audit-cycle day', function (): void {
    [$authority, $business] = ($this->business)();
    $reports = ($this->link)("/business/{$business}/reports");

    expect(($this->props)($authority['users'][0], 'business.reports', ['business' => $business], 'business/reports'))
        ->toBe(['reports' => ['verified' => [], 'in_audit' => [], 'archived' => []], 'report' => null, 'policy' => null,
            'links' => ['home' => ($this->link)("/business/{$business}"), 'reports' => $reports, 'market' => ($this->link)("/business/{$business}/market"), 'profile' => ($this->link)("/business/{$business}/profile"),
                'launcher' => ($this->link)('/dashboard'), 'close' => $reports]]);
});

it('leaves the pre-listing flash report off Reports', function (): void {
    $fixture = AuditSealingFixture::ready();
    AuditSealingFixture::seal($fixture);
    $business = $fixture['audit']['business'];

    expect(($this->props)($fixture['audit']['authority']['users'][0], 'business.reports', ['business' => $business], 'business/reports')['reports'])
        ->toBe(['verified' => [], 'in_audit' => [], 'archived' => []]);
});

it('lists the latest sealed monthly report in audit until it is published, opening its co-sign page', function (): void {
    $fixture = AuditSealingFixture::ready(kind: 'monthly');
    AuditSealingFixture::seal($fixture);
    $business = $fixture['audit']['business'];
    $user = $fixture['audit']['authority']['users'][0];
    $id = $fixture['report']->id;
    $report = ($this->props)($user, 'business.audit-reports.show', ['business' => $business, 'report' => $id], 'business/audit-cosign')['report'];
    $row = ['id' => $id, 'period' => ['kind' => 'monthly', 'starts_on' => $report['period'].'-01'], 'status' => 'in_audit', 'inflow' => null, 'health' => null,
        'auditor' => $report['auditor']['name'], 'seal_by' => null, 'link' => ($this->link)("/business/{$business}/audit-reports/{$id}")];

    expect(($this->props)($user, 'business.reports', ['business' => $business], 'business/reports')['reports'])
        ->toBe(['verified' => [], 'in_audit' => [$row], 'archived' => []]);

    expect(AuditSealingFixture::cosign($fixture)['status'])->toBe('completed');

    expect(($this->props)($user, 'business.reports', ['business' => $business], 'business/reports')['reports'])
        ->toBe(['verified' => [[...$row, 'status' => 'verified']], 'in_audit' => [], 'archived' => []]);
});

it('does not list a report its own page cannot open', function (): void {
    [$authority, $business] = ($this->business)();
    $this->mock(AuditReportPublicationStore::class, function ($mock): void {
        $mock->shouldReceive('latestForBusiness')->andReturn(['id' => '01K00000000000000000000000', 'kind' => 'monthly', 'status' => 'pending']);
        $mock->shouldReceive('get')->once()->andThrow(new CommandRejection('AUDIT_SIGNATURE_UNAVAILABLE', 503));
    });

    expect(($this->props)($authority['users'][0], 'business.reports', ['business' => $business], 'business/reports')['reports'])
        ->toBe(['verified' => [], 'in_audit' => [], 'archived' => []]);
});

it('sends the Reports and Profile tabs from every live Business page', function (): void {
    $campaign = PrimaryReservationFixture::campaign();
    $user = User::query()->findOrFail($campaign->actor_user_id);
    $business = $campaign->business_id;
    $tabs = ['reports' => ($this->link)("/business/{$business}/reports"), 'market' => ($this->link)("/business/{$business}/market"), 'profile' => ($this->link)("/business/{$business}/profile")];

    expect(($this->props)($user, 'business.campaigns.show', ['business' => $business, 'campaign' => $campaign->id], 'business/campaign')['shell_links'])
        ->toMatchArray($tabs)
        ->and(($this->props)($user, 'business.applications.publish.show', ['business' => $business, 'application' => $campaign->business_application_id], 'business/publish')['shell_links'])
        ->toMatchArray($tabs);
});
