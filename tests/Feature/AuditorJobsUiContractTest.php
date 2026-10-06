<?php

declare(strict_types=1);

use App\Models\AuditAssignment;
use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\AuditAssignmentFixture as Fixture;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->freezeTime();
    $this->withoutVite();
});

/**
 * Compares a live page with the fixture that stands for its TypeScript contract, key by key. It
 * descends into objects and into the first record of two non-empty lists of objects; a null on
 * either side, or a list of scalars, ends the comparison at that key.
 *
 * @param  array<array-key, mixed>  $live
 * @param  array<array-key, mixed>  $fixture
 */
function jobsUiSameShape(array $live, array $fixture, string $path = 'props'): void
{
    expect(array_keys($live))->toEqualCanonicalizing(array_keys($fixture), "Keys differ at {$path}");
    foreach ($live as $key => $value) {
        $other = $fixture[$key];
        if (! is_array($value) || ! is_array($other)) {
            continue;
        }
        if (! array_is_list($value) && ! array_is_list($other)) {
            jobsUiSameShape($value, $other, "{$path}.{$key}");
        } elseif ($value !== [] && $other !== [] && is_array($value[0]) && is_array($other[0])) {
            jobsUiSameShape($value[0], $other[0], "{$path}.{$key}.0");
        }
    }
}

/** @return array<string, mixed> */
function jobsUiFixture(string $name): array
{
    /** @var array{component: string, props: array<string, mixed>} $fixture */
    $fixture = json_decode((string) file_get_contents(resource_path("fixtures/ui/{$name}.json")), true, flags: JSON_THROW_ON_ERROR);

    return $fixture['props'];
}

/**
 * The live page's own props, without the props every Inertia page shares, after checking it
 * renders the component the fixture names.
 *
 * @return array<string, mixed>
 */
function jobsUiProps(User $user, string $url, string $fixture): array
{
    /** @var array{component: string} $page */
    $page = json_decode((string) file_get_contents(resource_path("fixtures/ui/{$fixture}.json")), true, flags: JSON_THROW_ON_ERROR);
    $props = [];
    actingAs($user)->get($url)->assertOk()
        ->assertInertia(function (Assert $inertia) use (&$props, $page): Assert {
            $props = $inertia->toArray()['props'];

            return $inertia->component($page['component']);
        });

    return array_diff_key($props, array_flip(['auth', 'name', 'locale', 'errors', 'sidebarOpen', 'nonLiveEnvironment', 'head']));
}

/**
 * One partner with an open Flash offer from one Business and an accepted job at another.
 *
 * @return array{user: User, offer: AuditAssignment, accepted: AuditAssignment}
 */
function jobsUiScene(): array
{
    $fixture = Fixture::make(1);
    $partner = $fixture['partners'][0];
    $offer = Fixture::request($fixture);
    $other = Fixture::make(0);
    Fixture::independence($other['staff'], $other['business'], $partner['party']->id);
    $accepted = Fixture::request($other);
    Fixture::respond($partner['user'], $accepted);

    return ['user' => $partner['user'], 'offer' => $offer, 'accepted' => $accepted->refresh()];
}

/**
 * A command body exactly as the Auditor pages send it.
 *
 * @param  array<string, mixed>  $fields
 * @return array<string, mixed>
 */
function jobsUiCommand(AuditAssignment $assignment, array $fields = []): array
{
    return ['assignment_id' => $assignment->id, 'expected_revision' => $assignment->revision, ...$fields,
        'identity_context_revision' => 1, 'request_id' => (string) Str::uuid()];
}

it('renders live Jobs with the component and prop shape of the Jobs fixture', function (): void {
    $scene = jobsUiScene();
    $props = jobsUiProps($scene['user'], '/auditor/jobs', 'auditor-jobs-live-minimal');

    jobsUiSameShape($props, jobsUiFixture('auditor-jobs-live-minimal'));
    expect($props['eligible'][0]['id'])->toBe($scene['offer']->id)
        ->and($props['assigned'][0]['id'])->toBe($scene['accepted']->id)
        ->and($props['monthly'])->toBeNull()
        ->and($props['pagination'])->toBe(['next' => null])
        ->and(array_column($props['decline_options'], 'code'))->toBe(['unavailable', 'capacity', 'location', 'other'])
        ->and($props['eligible'][0]['actions'])->toBe([
            'accept' => ['url' => '/auditor/jobs/'.$scene['offer']->id.'/accept', 'method' => 'post'],
            'decline' => ['url' => '/auditor/jobs/'.$scene['offer']->id.'/decline', 'method' => 'post'],
            'conflict' => ['url' => '/auditor/jobs/'.$scene['offer']->id.'/conflict', 'method' => 'post'],
        ])
        ->and($props['eligible'][0]['link']['url'])->toBe('/auditor/jobs/'.$scene['offer']->id)
        ->and($props['assigned'][0]['link']['url'])->toBe('/auditor/jobs/'.$scene['accepted']->id)
        ->and($props['links']['operation'])->toBe(['url' => '/auditor/assignment-operations/{request_id}', 'method' => 'get'])
        ->and($props['links']['conflicts'])->toBe(['url' => '/auditor/conflicts', 'method' => 'get']);
});

it('renders the live file summary with the component and prop shape of the file fixture', function (): void {
    $scene = jobsUiScene();
    $props = jobsUiProps($scene['user'], '/auditor/jobs/'.$scene['accepted']->id, 'auditor-file-live-minimal');

    jobsUiSameShape($props, jobsUiFixture('auditor-file-live-minimal'));
    expect($props['job']['state'])->toBe('assigned')
        ->and($props['blocked'])->toBeNull()
        ->and($props['links']['procedure'])->toBeNull()
        ->and($props['links']['close'])->toBe(['url' => '/auditor/jobs', 'method' => 'get']);
});

it('renders the live conflict register and single receipt with the shape of their fixtures', function (): void {
    $scene = jobsUiScene();
    $this->actingAs($scene['user'])->postJson('/auditor/jobs/'.$scene['offer']->id.'/conflict',
        jobsUiCommand($scene['offer'], ['kind' => 'family_or_business', 'reason' => 'My cousin keeps their books.']))->assertOk();

    $register = jobsUiProps($scene['user'], '/auditor/conflicts', 'auditor-conflicts');
    jobsUiSameShape($register, jobsUiFixture('auditor-conflicts'));
    $receipt = jobsUiProps($scene['user'], '/auditor/jobs/'.$scene['offer']->id.'/conflict', 'auditor-conflict-receipt');
    jobsUiSameShape($receipt, jobsUiFixture('auditor-conflict-receipt'));
    expect($receipt['conflicts'])->toHaveCount(1)
        ->and($receipt['conflicts'][0]['assignment_id'])->toBe($scene['offer']->id)
        ->and($receipt['conflicts'][0]['conflict'])->toMatchArray(['kind' => 'family_or_business', 'note' => 'My cousin keeps their books.', 'blocking' => true])
        ->and($receipt['conflicts'][0])->not->toHaveKey('business');
});

it('accepts, declines and declares with the bodies the pages send, landing on each next page', function (): void {
    $scene = jobsUiScene();
    $user = $scene['user'];
    $offer = $scene['offer'];

    $accept = jobsUiCommand($offer);
    $this->actingAs($user)->postJson('/auditor/jobs/'.$offer->id.'/accept', $accept)->assertOk()
        ->assertJsonPath('code', 'ASSIGNMENT_ACCEPTED')->assertJsonPath('data.next.url', '/auditor/jobs/'.$offer->id);
    $this->getJson('/auditor/assignment-operations/'.$accept['request_id'].'?command=assignment.accept')->assertOk()
        ->assertJsonPath('code', 'ASSIGNMENT_ACCEPTED');

    $conflict = jobsUiCommand($offer->refresh(), ['kind' => 'role_tie', 'reason' => 'I prepared their tax returns.']);
    $this->postJson('/auditor/jobs/'.$offer->id.'/conflict', $conflict)->assertOk()
        ->assertJsonPath('code', 'CONFLICT_RECORDED')->assertJsonPath('data.conflict.note', 'I prepared their tax returns.')
        ->assertJsonPath('data.conflict.blocking', true)->assertJsonPath('data.next.url', '/auditor/jobs/'.$offer->id.'/conflict');

    $fixture = Fixture::make(1);
    $declinable = Fixture::request($fixture);
    $decliner = $fixture['partners'][0]['user'];
    $this->actingAs($decliner)->postJson('/auditor/jobs/'.$declinable->id.'/decline',
        jobsUiCommand($declinable, ['reason_code' => 'capacity', 'reason' => '']))->assertOk()
        ->assertJsonPath('code', 'ASSIGNMENT_DECLINED')->assertJsonPath('data.next.url', '/auditor/jobs');
});

it('names the fields the pages show when a declaration or a decline is refused', function (): void {
    $fixture = Fixture::make(1);
    $offer = Fixture::request($fixture);
    $this->actingAs($fixture['partners'][0]['user']);

    $this->postJson('/auditor/jobs/'.$offer->id.'/conflict', jobsUiCommand($offer, ['kind' => 'other', 'reason' => '']))
        ->assertUnprocessable()->assertJsonPath('errors.reason', ['A concise reason is required.']);
    $this->postJson('/auditor/jobs/'.$offer->id.'/decline', jobsUiCommand($offer, ['reason_code' => 'nope', 'reason' => '']))
        ->assertUnprocessable()->assertJsonPath('errors.reason_code', ['Select a decline reason.']);
});
