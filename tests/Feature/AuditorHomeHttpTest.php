<?php

declare(strict_types=1);

use App\Application\Auditor\ListAuditJobs;
use App\Application\Identity\SelectActiveRole;
use App\Http\Resources\AuditorJobsResource;
use App\Models\Party;
use App\Models\RoleMembership;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\AuditAssignmentFixture;
use Tests\Support\AuditorFixture;
use Tests\Support\AuditSealingFixture;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->withoutVite();
});

/**
 * A live Auditor page's own props, without the props every Inertia page shares, after checking
 * it renders `$component`.
 *
 * @return array<string, mixed>
 */
function auditorHomeProps(User $user, string $url = '/auditor', string $component = 'auditor/home'): array
{
    $props = [];
    actingAs($user)->get($url)->assertOk()
        ->assertInertia(function (Assert $page) use (&$props, $component): Assert {
            $props = $page->toArray()['props'];

            return $page->component($component);
        });

    return array_diff_key($props, array_flip(['auth', 'name', 'locale', 'errors', 'sidebarOpen', 'nonLiveEnvironment', 'head']));
}

/**
 * Compares a live page with the fixture that stands for its TypeScript contract, key by key. A
 * null on either side, or a list, ends the comparison at that key.
 *
 * @param  array<array-key, mixed>  $live
 * @param  array<array-key, mixed>  $fixture
 */
function auditorHomeSameShape(array $live, array $fixture, string $path = ''): void
{
    expect(array_keys($live))->toEqualCanonicalizing(array_keys($fixture), "Keys differ at {$path}");
    foreach ($live as $key => $value) {
        if (is_array($value) && is_array($fixture[$key]) && ! array_is_list($value) && ! array_is_list($fixture[$key])) {
            auditorHomeSameShape($value, $fixture[$key], $path.'.'.$key);
        }
    }
}

it('denies Home to guests, other roles and an Auditor without two-factor authentication', function (): void {
    $this->get(route('auditor.home'))->assertRedirect(route('login'));
    $party = Party::factory()->verified()->create();
    $investor = User::factory()->withTwoFactor()->for($party)->create();
    RoleMembership::factory()->for($party)->active()->create(['role' => 'investor']);
    app(SelectActiveRole::class)->handle($investor->id, 'investor', 0, (string) Str::uuid());
    $this->actingAs($investor)->get(route('auditor.home'))->assertForbidden()
        ->assertInertia(fn (Assert $page): Assert => $page->component('identity/access-denied', false)->where('code', 'ROLE_NOT_AVAILABLE'));
    $this->getJson(route('auditor.home'))->assertForbidden()->assertJsonPath('code', 'ROLE_NOT_AVAILABLE');
    $fixture = AuditorFixture::make();
    $fixture['user']->forceFill(['two_factor_confirmed_at' => null])->save();
    $this->actingAs($fixture['user'])->get(route('auditor.home'))->assertForbidden()
        ->assertInertia(fn (Assert $page): Assert => $page->component('identity/access-denied', false)->where('code', 'MFA_REQUIRED')
            ->missing('auditor')->missing('standing'));
});

it('renders a first-time Auditor Home in the live contract shape with its empty states and real links', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-03T17:00:00Z'));
    $fixture = AuditorFixture::make();
    $fixture['user']->forceFill(['name' => 'Synthetic Partner'])->save();
    $props = auditorHomeProps($fixture['user']);
    /** @var array{props: array<string, mixed>} $minimal */
    $minimal = json_decode((string) file_get_contents(resource_path('fixtures/ui/auditor-home-live-minimal.json')), true, flags: JSON_THROW_ON_ERROR);
    auditorHomeSameShape($props, $minimal['props']);
    expect($props)->toMatchArray([
        'contract_version' => 'auditor-filing-v1', 'identity_context_revision' => 1, 'server_time' => now()->toIso8601String(),
        'allowed_actions' => [], 'engagement' => ['status' => 'unavailable', 'link' => ['url' => '/auditor/engagement', 'method' => 'get']],
        'auditor' => ['name' => 'Synthetic Partner', 'firm' => null, 'accreditation' => null, 'avatar_url' => null, 'since_year' => null],
        'quality_score' => null, 'earned_this_month' => null, 'active_deals' => null, 'licence_expires_on' => null,
        'availability' => ['accepting' => false, 'radius_km' => 30, 'max_active' => 3, 'revision' => 0, 'update' => ['url' => '/auditor/availability', 'method' => 'post']],
        'nearby' => ['count' => 0, 'closest_km' => null], 'in_progress' => [],
        'standing' => ['current' => false, 'reason' => 'ACCREDITATION_REQUIRED', 'on_time_pct' => null, 'avg_variance_pct' => null,
            'variance_flagged' => false, 'jobs_done' => 0, 'clock_expiries' => null],
        'activity' => [], 'wallet' => ['available' => null], 'unread_notifications' => 0,
    ])->and($props['links'])->toBe([
        'home' => ['url' => '/auditor', 'method' => 'get'], 'jobs' => ['url' => '/auditor/jobs', 'method' => 'get'],
        'portfolio' => ['url' => '/auditor/portfolio', 'method' => 'get'], 'profile' => ['url' => '/auditor/profile', 'method' => 'get'],
        'launcher' => ['url' => '/dashboard', 'method' => 'get'], 'conflicts' => ['url' => '/auditor/conflicts', 'method' => 'get'],
        'operation' => ['url' => '/auditor/operations/{request_id}', 'method' => 'get'],
        'statement' => null, 'withdraw' => null, 'notifications' => null,
    ]);
    $this->get('/auditor')->assertHeaderContains('Cache-Control', 'no-store')->assertHeaderContains('Cache-Control', 'private');
    foreach (['jobs' => 'auditor/jobs', 'portfolio' => 'auditor/portfolio', 'profile' => 'auditor/profile', 'conflicts' => 'auditor/conflicts'] as $tab => $component) {
        $this->get($props['links'][$tab]['url'])->assertOk()->assertInertia(fn (Assert $page): Assert => $page->component($component)
            ->where('links.home', $props['links']['home'])->where('links.portfolio', $props['links']['portfolio']));
    }
});

it('shows open offers nearby on Home and as the Jobs badge on every tab that carries it', function (): void {
    $fixture = AuditAssignmentFixture::make(1);
    $offer = AuditAssignmentFixture::request($fixture);
    $partner = $fixture['partners'][0];
    $eligible = auditorHomeProps($partner['user'], '/auditor/jobs', 'auditor/jobs')['eligible'];
    expect($eligible)->toHaveCount(1)->and($eligible[0]['id'])->toBe($offer->id)->and($eligible[0]['distance_km'])->toBeString();
    $props = auditorHomeProps($partner['user']);
    expect($props['nearby'])->toBe(['count' => 1, 'closest_km' => $eligible[0]['distance_km']])
        ->and($props['in_progress'])->toBe([])->and($props['allowed_actions'])->toBe(['availability.update'])
        ->and($props['standing'])->toMatchArray(['current' => true, 'reason' => null])
        ->and($props['licence_expires_on'])->toBe(now()->addYear()->format('Y-m-d'))
        ->and($props['engagement']['status'])->toBe('current')
        ->and($props['availability']['accepting'])->toBeTrue();
    foreach (['/auditor/profile' => 'auditor/profile', '/auditor/engagement' => 'auditor/engagement', '/auditor/portfolio' => 'auditor/portfolio'] as $url => $component) {
        expect(auditorHomeProps($partner['user'], $url, $component)['open_jobs'])->toBe(1);
    }
    $work = app(ListAuditJobs::class)->handle($partner['user']->id, 1);
    expect(AuditorJobsResource::openJobs($work))->toBe(1)
        ->and(AuditorJobsResource::openJobs([...$work, 'next_cursor' => strtolower((string) Str::ulid())]))->toBe(0);
    AuditAssignmentFixture::respond($partner['user'], $offer->refresh());
    $accepted = auditorHomeProps($partner['user']);
    expect($accepted['nearby'])->toBe(['count' => 0, 'closest_km' => null])
        ->and($accepted['in_progress'])->toHaveCount(1)
        ->and($accepted['in_progress'][0])->toMatchArray(['id' => $offer->id, 'state' => 'assigned', 'business' => 'Synthetic business',
            'status' => 'in_progress', 'link' => ['url' => '/auditor/jobs/'.$offer->id, 'method' => 'get']]);
});

it('lists the partner\'s filings as recent activity and counts them as jobs done', function (): void {
    $this->travelTo(now('UTC')->startOfMonth()->addDays(4)->setTime(10, 0));
    $fixture = AuditSealingFixture::ready();
    $props = auditorHomeProps($fixture['user']);
    expect($props['activity'])->toBe([])->and($props['standing']['jobs_done'])->toBe(0)
        ->and($props['in_progress'][0]['status'])->toBe('in_progress');
    $sealed = AuditSealingFixture::seal($fixture);
    $props = auditorHomeProps($fixture['user']);
    expect($props['activity'])->toBe([['kind' => 'report_filed', 'at' => $sealed['data']['sealed']['sealed_at'], 'business' => 'Synthetic business', 'variance_pct' => null]])
        ->and($props['standing']['jobs_done'])->toBe(1)->and($props['in_progress'][0]['status'])->toBe('awaiting_cosign')
        ->and(auditorHomeProps($fixture['user'], '/auditor/profile', 'auditor/profile')['jobs_done'])->toBe(1);
});
