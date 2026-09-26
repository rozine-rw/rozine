<?php

declare(strict_types=1);

use App\Application\Auditor\AmendAuditReport;
use App\Models\RoleMembership;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\Support\AuditorFixture;
use Tests\Support\AuditSealingFixture as Fixture;
use Tests\Support\BusinessApplicationFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    $this->withoutVite();
});

it('links authorized Auditor home to jobs and profile without exposing those links in another role', function (): void {
    $auditor = AuditorFixture::make();
    $this->actingAs($auditor['user'])->get(route('auditor.home'))->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->where('links.jobs', ['url' => '/auditor/jobs', 'method' => 'get'])
            ->where('links.profile', ['url' => '/auditor/profile', 'method' => 'get']));
    $business = BusinessApplicationFixture::make();
    $this->actingAs($business['authority']['users'][0])->get(route('business.home'))->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->where('links', null)->where('business_applications.entries.0.audit_report', null));
});

it('projects sealed jobs as awaiting co-sign and removes published jobs from active work', function (bool $api): void {
    $fixture = Fixture::ready();
    if ($api) {
        Sanctum::actingAs($fixture['user'], ['auditor:read']);
    } else {
        $this->actingAs($fixture['user']);
    }
    $prefix = $api ? 'api.v1.auditor.' : 'auditor.';
    $read = function () use ($api, $prefix): array {
        $response = $api ? $this->getJson(route($prefix.'jobs.index')) : $this->get(route($prefix.'jobs.index'));
        $response->assertOk();

        return $api ? $response->json('data') : $response->viewData('page')['props'];
    };
    expect($read()['assigned'][0]['status'])->toBe('in_progress');
    Fixture::seal($fixture);
    expect($read()['assigned'][0]['status'])->toBe('awaiting_cosign');
    $path = route($prefix.'reports.show', ['report' => $fixture['report']->id]);
    $response = $api ? $this->getJson($path) : $this->get($path);
    $response->assertOk();
    $page = $api ? $response->json('data') : $response->viewData('page')['props'];
    expect($page['stage']['verification'])->toBe(['url' => ($api ? '/api/v1' : '').'/audit-seals/'.$fixture['report']->id, 'method' => 'get']);
    Fixture::cosign($fixture);
    expect($read()['assigned'])->toBeEmpty();
    $this->travel(25)->hours();
    expect($read()['assigned'])->toBeEmpty();
})->with([false, true]);

it('links Business home only to its own latest unamended sealed report across transports', function (bool $api): void {
    $fixture = Fixture::ready();
    Fixture::seal($fixture);
    $user = $fixture['audit']['authority']['users'][0];
    if ($api) {
        Sanctum::actingAs($user, ['business:read']);
    } else {
        $this->actingAs($user);
    }
    $read = function () use ($api): array {
        $response = $api ? $this->getJson('/api/v1/business') : $this->get('/business');
        $response->assertOk();

        return $api ? $response->json('data.entries') : $response->viewData('page')['props']['business_applications']['entries'];
    };
    $link = ['url' => ($api ? '/api/v1' : '').'/business/'.$fixture['audit']['business'].'/audit-reports/'.$fixture['report']->id, 'method' => 'get'];
    expect($read()[0]['audit_report'])->toBe(['id' => $fixture['report']->id, 'kind' => 'flash', 'status' => 'pending', 'link' => $link]);
    Fixture::cosign($fixture);
    expect($read()[0]['audit_report']['status'])->toBe('published');
    app(AmendAuditReport::class)->handle($fixture['user']->id, 1, $fixture['report']->id, $fixture['report']->refresh()->revision, (string) Str::uuid());
    expect($read()[0]['audit_report'])->toBeNull();
    RoleMembership::query()->where('party_id', $user->party_id)->update(['status' => 'revoked']);
    ($api ? $this->getJson('/api/v1/business') : $this->getJson('/business'))->assertForbidden();
})->with([false, true]);

it('does not expose another Business report from home', function (): void {
    $fixture = Fixture::ready();
    Fixture::seal($fixture);
    $other = BusinessApplicationFixture::make();
    $this->actingAs($other['authority']['users'][0])->get('/business')->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->has('business_applications.entries', 1)
            ->where('business_applications.entries.0.business_id', $other['business']->id)
            ->where('business_applications.entries.0.audit_report', null));
});

it('exposes unit tolerance separately from cash tolerance', function (): void {
    $fixture = Fixture::ready(kind: 'monthly');
    $this->actingAs($fixture['user'])->get(route('auditor.reports.show', ['report' => $fixture['report']->id, 'step' => 'count']))->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->where('stage.stock.tolerance_units', '0')->where('stage.tolerance', 'RWF 0'));
});

it('renders route and model misses as a neutral browser 404 and preserves JSON responses', function (): void {
    Route::middleware('web')->get('/test-missing-model', fn () => User::query()->findOrFail(-1));
    foreach (['/audit-seals/xyz', '/unmatched-test-page', '/test-missing-model'] as $path) {
        $this->withHeader('Accept-Language', 'fr-RW')->get($path)->assertNotFound()->assertHeader('Cache-Control', 'no-store, private')->assertInertia(fn (Assert $page): Assert => $page->component('identity/access-denied')
            ->where('status', 404)->where('code', 'PAGE_NOT_FOUND')->where('auth.user', null)->where('locale', 'fr'));
        $this->getJson($path)->assertNotFound()->assertJsonMissingPath('component');
    }
    $this->get('/api/v1/audit-seals/xyz')->assertNotFound()->assertHeader('Content-Type', 'application/json');
    $this->postJson('/unmatched-test-page')->assertNotFound()->assertJsonMissingPath('component');
});
