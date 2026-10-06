<?php

declare(strict_types=1);

use App\Application\Identity\SelectActiveRole;
use App\Models\Party;
use App\Models\RoleMembership;
use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\AuditAssignmentFixture;
use Tests\Support\AuditEngagementFixture;
use Tests\Support\AuditorFixture;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->freezeTime();
    $this->withoutVite();
});

/**
 * Compares the live agreement page with the fixture that stands for its TypeScript contract
 * (`AuditorEngagementProps`), key by key. It descends into objects and into the first record of two
 * non-empty lists of objects; a null on either side, or a list of scalars, ends the comparison there.
 *
 * @param  array<array-key, mixed>  $live
 * @param  array<array-key, mixed>  $fixture
 */
function engagementUiSameShape(array $live, array $fixture, string $path = 'props'): void
{
    expect(array_keys($live))->toEqualCanonicalizing(array_keys($fixture), "Keys differ at {$path}");
    foreach ($live as $key => $value) {
        $other = $fixture[$key];
        if (! is_array($value) || ! is_array($other)) {
            continue;
        }
        if (! array_is_list($value) && ! array_is_list($other)) {
            engagementUiSameShape($value, $other, "{$path}.{$key}");
        } elseif ($value !== [] && $other !== [] && is_array($value[0]) && is_array($other[0])) {
            engagementUiSameShape($value[0], $other[0], "{$path}.{$key}.0");
        }
    }
}

/**
 * A fixture's props, after checking the page it renders, without the preview-only
 * `preview_outcome` the server never sends.
 *
 * @return array<string, mixed>
 */
function engagementUiFixture(string $name, string $component = 'auditor/engagement'): array
{
    /** @var array{component: string, props: array<string, mixed>} $fixture */
    $fixture = json_decode((string) file_get_contents(resource_path("fixtures/ui/{$name}.json")), true, flags: JSON_THROW_ON_ERROR);
    expect($fixture['component'])->toBe($component);

    return array_diff_key($fixture['props'], ['preview_outcome' => true]);
}

/**
 * A live page's own props, without the props every Inertia page shares, after checking it
 * renders `$component`.
 *
 * @return array<string, mixed>
 */
function engagementUiProps(User $user, string $url = '/auditor/engagement', string $component = 'auditor/engagement'): array
{
    $props = [];
    actingAs($user)->get($url)->assertOk()
        ->assertInertia(function (Assert $inertia) use (&$props, $component): Assert {
            $props = $inertia->toArray()['props'];

            return $inertia->component($component);
        });

    return array_diff_key($props, array_flip(['auth', 'name', 'locale', 'errors', 'sidebarOpen', 'nonLiveEnvironment', 'head']));
}

/**
 * Reads the engagement summary on every live Auditor entry page — the role home, Jobs, the file
 * and the Jobs beneath it, and Profile — checks each against the banner fixture's `engagement`
 * key by key, and returns them.
 *
 * @return array<string, mixed>
 */
function engagementUiSummaries(User $user, string $assignmentId): array
{
    $summary = engagementUiFixture('auditor-jobs-engagement-required', 'auditor/jobs')['engagement'];
    $file = engagementUiProps($user, '/auditor/jobs/'.$assignmentId, 'auditor/file');
    $read = ['home' => engagementUiProps($user, '/auditor', 'identity/role-home')['engagement'],
        'jobs' => engagementUiProps($user, '/auditor/jobs', 'auditor/jobs')['engagement'],
        'file' => $file['engagement'], 'file.jobs' => $file['jobs']['engagement'],
        'profile' => engagementUiProps($user, '/auditor/profile', 'auditor/profile')['engagement']];
    foreach ($read as $page => $live) {
        expect($live)->toBeArray("No engagement summary on {$page}");
        engagementUiSameShape($live, $summary, "{$page}.engagement");
    }

    return $read;
}

/**
 * An acceptance body exactly as the agreement page sends it.
 *
 * @param  array<string, mixed>  $page
 * @return array<string, mixed>
 */
function engagementUiAccept(array $page): array
{
    return ['identity_context_revision' => $page['identity_context_revision'], 'expected_revision' => $page['release']['revision'],
        'release_id' => $page['release']['id'], 'sha256' => $page['release']['sha256'], 'accepted' => true, 'request_id' => (string) Str::uuid()];
}

it('renders terms awaiting acceptance with the prop shape of the synthetic agreement fixture', function (): void {
    $actor = AuditorFixture::make();
    $release = AuditEngagementFixture::release($actor['staff']);
    $props = engagementUiProps($actor['user']);

    engagementUiSameShape($props, engagementUiFixture('auditor-engagement'));
    engagementUiSameShape($props, engagementUiFixture('auditor-engagement-version-conflict'));
    expect($props['release']['id'])->toBe($release->id)
        ->and($props['release']['synthetic'])->toBeTrue()
        ->and($props['release']['documents']['agreed_procedures']['body'])->toBe(AuditEngagementFixture::documents()['agreed_procedures']['body'])
        ->and($props['acceptance'])->toBeNull()
        ->and($props['allowed_actions'])->toBe(['audit.engagement.accept'])
        ->and($props['actions']['accept'])->toBe(['url' => '/auditor/engagement/accept', 'method' => 'post'])
        ->and($props['links']['current'])->toBe(['url' => '/auditor/engagement', 'method' => 'get'])
        ->and($props['links']['operation'])->toBe(['url' => '/auditor/engagement/operations/{request_id}', 'method' => 'get']);
});

it('accepts with the body the page sends, recovers by request_id alone and then renders the accepted fixture shape', function (): void {
    $actor = AuditorFixture::make();
    AuditEngagementFixture::release($actor['staff']);
    $page = engagementUiProps($actor['user']);
    $body = engagementUiAccept($page);

    $receipt = $this->postJson($page['actions']['accept']['url'], $body)->assertOk()
        ->assertJsonPath('status', 'completed')->assertJsonPath('code', 'AUDIT_ENGAGEMENT_ACCEPTED');
    $this->getJson(str_replace('{request_id}', $body['request_id'], $page['links']['operation']['url']))->assertOk()
        ->assertJsonPath('operation_id', $receipt->json('operation_id'))->assertJsonPath('data.acceptance', $receipt->json('data.acceptance'));

    $accepted = engagementUiProps($actor['user']);
    engagementUiSameShape($accepted, engagementUiFixture('auditor-engagement-accepted'));
    expect($accepted['acceptance'])->toEqual($receipt->json('data.acceptance'))
        ->and($accepted['allowed_actions'])->toBe([])
        ->and($accepted['actions']['accept'])->toBeNull();
});

it('renders no usable terms with the prop shape of the unavailable fixture', function (): void {
    $actor = AuditorFixture::make();

    engagementUiSameShape(engagementUiProps($actor['user']), engagementUiFixture('auditor-engagement-unavailable'));
});

it('refuses a stale release as a recorded version conflict, and the page then offers the replacement', function (): void {
    $actor = AuditorFixture::make();
    AuditEngagementFixture::release($actor['staff']);
    $stale = engagementUiProps($actor['user']);
    AuditEngagementFixture::release($actor['staff'], 1, 'active', 'synthetic-terms-2');
    $body = engagementUiAccept($stale);

    $this->postJson($stale['actions']['accept']['url'], $body)->assertStatus(409)
        ->assertJsonPath('code', 'AUDIT_ENGAGEMENT_VERSION_CONFLICT')->assertJsonPath('status', 'rejected');
    $this->getJson(str_replace('{request_id}', $body['request_id'], $stale['links']['operation']['url']))->assertStatus(409)
        ->assertJsonPath('code', 'AUDIT_ENGAGEMENT_VERSION_CONFLICT');

    $fresh = engagementUiProps($actor['user']);
    engagementUiSameShape($fresh, engagementUiFixture('auditor-engagement-version-conflict'));
    expect($fresh['release']['revision'])->toBe(2)
        ->and($fresh['release']['version'])->toBe('synthetic-terms-2')
        ->and($fresh['allowed_actions'])->toBe(['audit.engagement.accept']);
});

it('carries the live engagement summary the banners read on every Auditor entry page', function (): void {
    $fixture = AuditAssignmentFixture::make(1);
    $assignment = AuditAssignmentFixture::request($fixture);
    $user = $fixture['partners'][0]['user'];
    $link = engagementUiProps($user)['links']['current'];
    $expect = fn (string $status): array => array_fill_keys(['home', 'jobs', 'file', 'file.jobs', 'profile'], ['status' => $status, 'link' => $link]);

    expect(engagementUiSummaries($user, $assignment->id))->toBe($expect('current'));

    $replacement = AuditEngagementFixture::release($fixture['staff'], 1, 'active', 'synthetic-terms-2');
    expect(engagementUiSummaries($user, $assignment->id))->toBe($expect('required'));
    $offer = engagementUiProps($user, '/auditor/jobs', 'auditor/jobs')['eligible'][0];
    expect($offer['id'])->toBe($assignment->id)
        ->and($offer['allowed_actions'])->not->toContain('assignment.accept')
        ->and($offer['allowed_actions'])->toContain('assignment.decline', 'conflict.declare');

    AuditEngagementFixture::accept($user, $replacement);
    expect(engagementUiSummaries($user, $assignment->id))->toBe($expect('current'));

    AuditEngagementFixture::release($fixture['staff'], 2, 'withdrawn');
    expect(engagementUiSummaries($user, $assignment->id))->toBe($expect('unavailable'));
});

it('renders no engagement summary on a role home that is not the Auditor\'s', function (): void {
    $party = Party::factory()->verified()->create();
    $user = User::factory()->withTwoFactor()->for($party)->create();
    RoleMembership::factory()->for($party)->active()->create(['role' => 'business']);
    app(SelectActiveRole::class)->handle($user->id, 'business', 0, (string) Str::uuid());

    $props = engagementUiProps($user, '/business', 'identity/role-home');
    expect($props)->toHaveKey('engagement')
        ->and($props['engagement'])->toBeNull()
        ->and(engagementUiFixture('role-home-business', 'identity/role-home')['engagement'])->toBeNull()
        ->and(engagementUiFixture('role-home-auditor-engagement-required', 'identity/role-home')['engagement'])
        ->toBe(['status' => 'required', 'link' => ['url' => '/preview/auditor-engagement', 'method' => 'get']]);
});
