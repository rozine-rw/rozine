<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Business\SaveBusinessApplication;
use App\Application\Identity\ConfigureStaffAccess;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Models\BusinessApplication;
use App\Models\BusinessApplicationRelease;
use App\Models\BusinessApplicationSubmission;
use App\Models\StaffAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\Support\AuditSealingFixture;
use Tests\Support\BusinessCreditFactsFixture;
use Tests\Support\BusinessQuoteFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    $this->withoutVite();
});

afterEach(function (): void {
    foreach ([BusinessApplication::class, BusinessApplicationSubmission::class, BusinessApplicationRelease::class] as $model) {
        Event::forget('eloquent.retrieved: '.$model);
    }
});

it('projects the same retained queue and current release gates through web and API', function (): void {
    $fixture = AuditSealingFixture::ready();
    AuditSealingFixture::seal($fixture);
    AuditSealingFixture::cosign($fixture);
    $staff = $fixture['audit']['staff'];
    $id = $fixture['application']->id;
    $this->actingAs($staff);
    $web = $this->get(route('staff.applications.index', ['application' => $id]))->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->component('admin/applications')
            ->where('contract_version', 'staff-applications-v1')->has('applications', 1)
            ->where('applications.0.id', $id)->where('applications.0.requested.amount', '12000000')
            ->where('applications.0.rate_pct', BusinessApplicationSubmission::query()->where('business_application_id', $id)->firstOrFail()->payload['review']['quote']['rate_pct'])
            ->where('applications.0.state', 'submitted')->where('applications.0.capacity_used_pct', null)
            ->where('applications.0.approve_link', null)->where('tabs.0.count', 1)->where('tabs.1.count', 0)
            ->where('nav.today.url', '/admin/dashboard')->where('nav.businesses.url', '/admin/businesses')->where('nav.ledger', null)->where('policy', [])
            ->where('review.release.allowed_actions', ['application.release'])->where('review.capacity.outstanding', null)
            ->where('review.links.business', null)->where('review.audit.state', null)->where('review.factors', [])
            ->missing('review.acceptance')->missing('review.actor_user_id')->missing('review.application.draft')->missing('roles'));
    $props = $web->inertiaPage()['props'];
    $this->get(route('staff.applications.index'), ['X-Inertia' => 'true', 'X-Inertia-Version' => $web->inertiaPage()['version']])
        ->assertOk()->assertHeader('X-Inertia', 'true')->assertJsonPath('component', 'admin/applications')->assertJsonPath('props.review', null);
    Sanctum::actingAs($staff, ['staff:applications:read', 'staff:applications:review']);
    $api = $this->getJson(route('api.v1.staff.applications.index', ['application' => $id]))->assertOk();
    foreach (['viewer', 'badges', 'search', 'active_tab', 'policy', 'pagination', 'server_time'] as $field) {
        $api->assertJsonPath('data.'.$field, $props[$field]);
    }
    foreach (['id', 'business', 'sector', 'note_title', 'requested', 'term_months', 'rate_pct', 'rating', 'submitted_at', 'state', 'decision'] as $field) {
        $api->assertJsonPath('data.applications.0.'.$field, $props['applications'][0][$field]);
    }
    $api->assertJsonPath('data.review.release.actions.release.url', route('api.v1.staff.applications.release', ['application' => $id], false));
    $this->getJson($api->json('data.applications.0.link.url'))->assertOk()->assertJsonPath('data.review.id', $id);
    $this->getJson($api->json('data.review.links.close.url'))->assertOk()->assertJsonPath('data.review', null);
    $this->getJson(route('api.v1.staff.applications.show', ['application' => $id]))
        ->assertOk()->assertJsonPath('data.release', $api->json('data.review.release'));
    Sanctum::actingAs($staff, ['staff:applications:read']);
    $this->getJson(route('api.v1.staff.applications.index', ['application' => $id]))->assertOk()
        ->assertJsonPath('data.review.release.allowed_actions', [])->assertJsonPath('data.review.release.actions.release', null);
});

it('moves released applications to approved and preserves that history when current gates fail', function (): void {
    $fixture = AuditSealingFixture::ready();
    AuditSealingFixture::seal($fixture);
    AuditSealingFixture::cosign($fixture);
    $staff = $fixture['audit']['staff'];
    $id = $fixture['application']->id;
    app(BusinessCampaignStore::class)->release($staff->id, $id, 0, 'Reviewed current evidence.', (string) Str::uuid());
    BusinessCreditFactsFixture::record($staff, $fixture['audit']['business'], 1,
        facts: [...BusinessCreditFactsFixture::facts(), 'restriction_active' => true]);
    $this->actingAs($staff)->getJson(route('api.v1.staff.applications.index'))->assertOk()
        ->assertJsonCount(0, 'data.applications')->assertJsonPath('data.tabs.0.count', 0)->assertJsonPath('data.tabs.1.count', 1);
    $this->getJson(route('api.v1.staff.applications.index', ['tab' => 'approved', 'application' => $id]))->assertOk()
        ->assertJsonPath('data.applications.0.state', 'approved')->assertJsonPath('data.review.state', 'approved')
        ->assertJsonPath('data.review.release.state', 'released')->assertJsonPath('data.review.release.allowed_actions', [])
        ->assertJsonPath('data.review.release.gates.0.cause', 'RESTRICTION_ACTIVE');
});

it('paginates submitted applications without exposing drafts and preserves filters and return position', function (): void {
    $first = BusinessQuoteFixture::ready();
    BusinessQuoteFixture::submit($first, BusinessQuoteFixture::acceptance($first));
    $second = BusinessQuoteFixture::ready();
    BusinessQuoteFixture::submit($second, BusinessQuoteFixture::acceptance($second));
    $draft = BusinessQuoteFixture::ready();
    $this->actingAs($first['audit']['staff']);
    DB::enableQueryLog();
    $page = $this->getJson(route('api.v1.staff.applications.index', ['limit' => 1, 'search' => 'equipment']))->assertOk()
        ->assertJsonCount(1, 'data.applications')->assertJsonPath('data.applications.0.id', $second['application']->id)
        ->assertJsonPath('data.tabs.0.count', 2);
    expect(collect(DB::getQueryLog())->filter(fn (array $query): bool => str_contains($query['query'], 'from "business_application_submissions"'))->count())->toBe(1);
    DB::disableQueryLog();
    $next = $page->json('data.pagination.next.url');
    expect($next)->toContain('search=equipment', 'limit=1', 'tab=pending');
    $last = $this->getJson($next)->assertOk()->assertJsonPath('data.applications.0.id', $first['application']->id)
        ->assertJsonPath('data.pagination.next', null)->assertJsonPath('data.tabs.0.count', 2);
    $opened = $this->getJson($last->json('data.applications.0.link.url'))->assertOk()->assertJsonPath('data.review.id', $first['application']->id);
    expect($opened->json('data.review.links.close.url'))->toBe($next);
    $this->getJson(route('api.v1.staff.applications.index', ['search' => $first['application']->id]))->assertOk()
        ->assertJsonCount(1, 'data.applications')->assertJsonPath('data.applications.0.id', $first['application']->id);
    $this->getJson(route('api.v1.staff.applications.index', ['search' => 'missing']))->assertOk()->assertJsonCount(0, 'data.applications')
        ->assertJsonPath('data.tabs.0.count', 0)->assertJsonPath('data.pagination.next', null);
    foreach ([$draft['application']->id, (string) Str::ulid()] as $hidden) {
        $this->getJson(route('api.v1.staff.applications.index', ['application' => $hidden]))->assertNotFound();
    }
});

it('treats SQL pattern characters in queue searches as literal text', function (string $search, string $matchingTitle, string $otherTitle): void {
    $fixtures = [];
    foreach ([$matchingTitle, $otherTitle] as $title) {
        $fixture = BusinessQuoteFixture::ready();
        $application = $fixture['application'];
        $owner = $fixture['audit']['authority']['users'][0];
        $fields = [...$application->draft, 'title' => $title];
        app(SaveBusinessApplication::class)->handle($owner->id, 1, $fixture['audit']['business'], $application->id,
            $application->revision, $fields, 'raise', (string) Str::uuid());
        BusinessQuoteFixture::evaluate($fixture, $application->refresh()->revision);
        app(SaveBusinessApplication::class)->handle($owner->id, 1, $fixture['audit']['business'], $application->id,
            $application->refresh()->revision, $fields, 'review', (string) Str::uuid());
        BusinessQuoteFixture::submit($fixture, BusinessQuoteFixture::acceptance($fixture));
        $fixtures[] = $fixture;
    }
    $id = $fixtures[0]['application']->id;
    $this->actingAs($fixtures[0]['audit']['staff'])->getJson(route('api.v1.staff.applications.index', ['search' => $search]))
        ->assertOk()->assertJsonCount(1, 'data.applications')->assertJsonPath('data.applications.0.id', $id)
        ->assertJsonPath('data.tabs.0.count', 1)->assertJsonPath('data.tabs.1.count', 0)->assertJsonPath('data.search', $search);
})->with([
    'percent' => ['100%', 'Equipment at 100% capacity', 'Equipment at 1000 capacity'],
    'underscore' => ['a_b', 'Equipment a_b purchase', 'Equipment axb purchase'],
    'backslash' => ['a\\b', 'Equipment a\\b purchase', 'Equipment ab purchase'],
]);

it('accepts either ULID case for search selection and pagination', function (): void {
    $first = BusinessQuoteFixture::ready();
    BusinessQuoteFixture::submit($first, BusinessQuoteFixture::acceptance($first));
    $second = BusinessQuoteFixture::ready();
    BusinessQuoteFixture::submit($second, BusinessQuoteFixture::acceptance($second));
    $id = $first['application']->id;
    $this->actingAs($first['audit']['staff']);
    foreach ([strtolower($id), strtoupper($id)] as $input) {
        $this->getJson(route('api.v1.staff.applications.index', ['search' => $input, 'application' => $input]))->assertOk()
            ->assertJsonCount(1, 'data.applications')->assertJsonPath('data.applications.0.id', $id)
            ->assertJsonPath('data.review.id', $id)->assertJsonStructure(['data' => ['review' => ['release' => ['state', 'gates']]]]);
        $this->get(route('staff.applications.index', ['application' => $input]))->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page->where('review.id', $id));
    }
    $cursor = strtoupper($second['application']->id);
    $page = $this->getJson(route('api.v1.staff.applications.index', ['before' => $cursor, 'limit' => 1, 'application' => strtoupper($id)]))
        ->assertOk()->assertJsonCount(1, 'data.applications')->assertJsonPath('data.applications.0.id', $id)
        ->assertJsonPath('data.pagination.next', null);
    $this->getJson($page->json('data.review.links.close.url'))->assertOk()->assertJsonPath('data.applications.0.id', $id);
});

it('validates bounded queue queries', function (array $query, string $field): void {
    $user = User::factory()->withTwoFactor()->create();
    app(ConfigureStaffAccess::class)->handle($user->id, true, 'Queue review.', (string) Str::uuid(), ['approver']);
    $this->actingAs($user)->getJson(route('api.v1.staff.applications.index', $query))->assertUnprocessable()->assertJsonValidationErrors($field);
})->with([
    [['tab' => 'under_review'], 'tab'], [['limit' => 0], 'limit'], [['limit' => 51], 'limit'], [['limit' => 'bad'], 'limit'],
    [['before' => 'bad'], 'before'], [['application' => 'bad'], 'application'], [['search' => str_repeat('a', 121)], 'search'], [['search' => ['bad']], 'search'],
]);

it('requires staff permission in addition to API scope and rechecks it on every read', function (): void {
    $url = route('api.v1.staff.applications.index');
    $this->getJson($url)->assertUnauthorized();
    $user = User::factory()->withTwoFactor()->create();
    Sanctum::actingAs($user, ['staff:applications:read']);
    $this->getJson($url)->assertForbidden()->assertJsonPath('code', 'STAFF_ACCESS_REQUIRED');
    app(ConfigureStaffAccess::class)->handle($user->id, true, 'Analyst only.', (string) Str::uuid(), ['analyst']);
    $this->getJson($url)->assertForbidden()->assertJsonPath('code', 'STAFF_PERMISSION_REQUIRED');
    app(ConfigureStaffAccess::class)->handle($user->id, true, 'Approver.', (string) Str::uuid(), ['approver']);
    $this->getJson($url)->assertOk()->assertJsonPath('data.viewer.role', 'approver')->assertJsonPath('data.applications', []);
    Sanctum::actingAs($user, ['staff:applications:review']);
    $this->getJson($url)->assertForbidden();
    Sanctum::actingAs($user, ['staff:applications:read']);
    $user->forceFill(['two_factor_confirmed_at' => null])->save();
    $this->getJson($url)->assertForbidden();
});

it('does not return a queue after staff permission is revoked during the read', function (bool $disabled): void {
    $fixture = BusinessQuoteFixture::ready();
    BusinessQuoteFixture::submit($fixture, BusinessQuoteFixture::acceptance($fixture));
    BusinessApplication::retrieved(function () use ($fixture, $disabled): void {
        StaffAccount::query()->findOrFail($fixture['audit']['staff']->id)->forceFill($disabled ? ['enabled' => false] : ['roles' => ['analyst']])->save();
    });
    $this->actingAs($fixture['audit']['staff'])->getJson(route('api.v1.staff.applications.index'))->assertForbidden();
})->with([true, false]);

it('fails closed for missing or corrupted submitted records', function (string $corruption): void {
    $fixture = BusinessQuoteFixture::ready();
    BusinessQuoteFixture::submit($fixture, BusinessQuoteFixture::acceptance($fixture));
    if ($corruption === 'missing') {
        BusinessApplication::retrieved(function (BusinessApplication $application): void {
            $application->current_submission_id = (string) Str::ulid();
        });
    } else {
        BusinessApplicationSubmission::retrieved(function (BusinessApplicationSubmission $submission) use ($corruption): void {
            $payload = $submission->payload;
            if ($corruption === 'hash') {
                $submission->sha256 = str_repeat('0', 64);
            } else {
                $payload['application_id'] = (string) Str::ulid();
                $submission->payload = $payload;
                $submission->sha256 = hash('sha256', app(CanonicalJson::class)->encode($payload));
            }
        });
    }
    $this->withoutExceptionHandling()->actingAs($fixture['audit']['staff']);
    expect(fn () => $this->getJson(route('api.v1.staff.applications.index')))->toThrow(RuntimeException::class, 'APPLICATION_SUBMISSION_INTEGRITY_FAILED');
})->with(['missing', 'hash', 'binding']);

it('refuses a corrupt release in the approved queue', function (): void {
    $fixture = AuditSealingFixture::ready();
    AuditSealingFixture::seal($fixture);
    AuditSealingFixture::cosign($fixture);
    app(BusinessCampaignStore::class)->release($fixture['audit']['staff']->id, $fixture['application']->id, 0, 'Reviewed.', (string) Str::uuid());
    BusinessApplicationRelease::retrieved(function (BusinessApplicationRelease $release): void {
        $release->sha256 = str_repeat('0', 64);
    });
    $this->withoutExceptionHandling()->actingAs($fixture['audit']['staff']);
    expect(fn () => $this->getJson(route('api.v1.staff.applications.index', ['tab' => 'approved'])))->toThrow(RuntimeException::class, 'APPLICATION_RELEASE_INTEGRITY_FAILED');
});
