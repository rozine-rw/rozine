<?php

declare(strict_types=1);

use App\Application\Identity\SelectActiveRole;
use App\Domain\Auditor\AuditReportWindow;
use App\Models\AuditAssignment;
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
 * The live Portfolio's own props, without the props every Inertia page shares.
 *
 * @param  array<string, mixed>  $query
 * @return array<string, mixed>
 */
function auditorPortfolioProps(User $user, array $query = []): array
{
    $props = [];
    actingAs($user)->get(route('auditor.portfolio.index', $query))->assertOk()
        ->assertHeaderContains('Cache-Control', 'no-store')->assertHeaderContains('Cache-Control', 'private')
        ->assertInertia(function (Assert $page) use (&$props): Assert {
            $props = $page->toArray()['props'];

            return $page->component('auditor/portfolio');
        });

    return array_diff_key($props, array_flip(['auth', 'name', 'locale', 'errors', 'sidebarOpen', 'nonLiveEnvironment', 'head']));
}

/**
 * The report filters as the live page sends them, with their counts.
 *
 * @return list<array{key: string, count: int, link: array{url: string, method: string}}>
 */
function auditorPortfolioFilters(int $all, int $awaiting, int $published): array
{
    return [['key' => 'all', 'count' => $all, 'link' => ['url' => '/auditor/portfolio', 'method' => 'get']],
        ['key' => 'awaiting_cosign', 'count' => $awaiting, 'link' => ['url' => '/auditor/portfolio?filter=awaiting_cosign', 'method' => 'get']],
        ['key' => 'published', 'count' => $published, 'link' => ['url' => '/auditor/portfolio?filter=published', 'method' => 'get']]];
}

it('denies the Portfolio to guests, other roles, an Auditor without two-factor authentication and a withdrawn role', function (): void {
    $this->get(route('auditor.portfolio.index'))->assertRedirect(route('login'));
    $party = Party::factory()->verified()->create();
    $investor = User::factory()->withTwoFactor()->for($party)->create();
    RoleMembership::factory()->for($party)->active()->create(['role' => 'investor']);
    app(SelectActiveRole::class)->handle($investor->id, 'investor', 0, (string) Str::uuid());
    $this->actingAs($investor)->get(route('auditor.portfolio.index'))->assertForbidden()
        ->assertInertia(fn (Assert $page): Assert => $page->component('identity/access-denied', false)->where('code', 'ROLE_NOT_AVAILABLE'));
    $this->getJson(route('auditor.portfolio.index'))->assertForbidden()->assertJsonPath('code', 'ROLE_NOT_AVAILABLE');
    $fixture = AuditorFixture::make();
    $fixture['user']->forceFill(['two_factor_confirmed_at' => null])->save();
    $this->actingAs($fixture['user'])->get(route('auditor.portfolio.index'))->assertForbidden()
        ->assertInertia(fn (Assert $page): Assert => $page->component('identity/access-denied', false)->where('code', 'MFA_REQUIRED')->missing('reports'));
    $withdrawn = AuditorFixture::make();
    RoleMembership::query()->where('party_id', $withdrawn['party']->id)->update(['status' => 'revoked']);
    $this->actingAs($withdrawn['user'])->get(route('auditor.portfolio.index'))->assertForbidden()
        ->assertInertia(fn (Assert $page): Assert => $page->component('identity/access-denied', false)->where('code', 'ROLE_MEMBERSHIP_REQUIRED')
            ->missing('reports')->missing('conflicts'));
});

it('renders an empty Portfolio in the live contract shape with its empty states and real links', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-03T17:00:00Z'));
    $fixture = AuditorFixture::make();
    $props = auditorPortfolioProps($fixture['user']);
    /** @var array{props: array<string, mixed>} $minimal */
    $minimal = json_decode((string) file_get_contents(resource_path('fixtures/ui/auditor-portfolio-live-minimal.json')), true, flags: JSON_THROW_ON_ERROR);
    expect(array_keys($props))->toEqualCanonicalizing(array_keys($minimal['props']))
        ->and(array_column($props['filters'], 'key'))->toBe(array_column($minimal['props']['filters'], 'key'))
        ->and($props)->toBe([
            'contract_version' => 'auditor-filing-v1', 'identity_context_revision' => 1, 'server_time' => now()->toIso8601String(), 'allowed_actions' => [],
            'reports' => [], 'filter' => 'all', 'filters' => auditorPortfolioFilters(0, 0, 0),
            'conflicts' => ['files' => [], 'record' => [], 'declare' => ['url' => '/auditor/jobs/{assignment}/conflict', 'method' => 'post']],
            'owed' => [], 'owed_complete' => true, 'outcome' => null, 'open_jobs' => 0,
            'links' => ['home' => ['url' => '/auditor', 'method' => 'get'], 'jobs' => ['url' => '/auditor/jobs', 'method' => 'get'],
                'portfolio' => ['url' => '/auditor/portfolio', 'method' => 'get'], 'profile' => ['url' => '/auditor/profile', 'method' => 'get'],
                'launcher' => ['url' => '/dashboard', 'method' => 'get'], 'conflicts' => ['url' => '/auditor/conflicts', 'method' => 'get'],
                'operation' => ['url' => '/auditor/assignment-operations/{request_id}', 'method' => 'get']],
        ]);
});

it('lists sealed reports as awaiting co-sign until published, with exact filter counts', function (): void {
    $this->travelTo(now('UTC')->startOfMonth()->addDays(4)->setTime(10, 0));
    $fixture = AuditSealingFixture::ready();
    $assignment = AuditAssignment::query()->whereKey($fixture['report']->assignment_id)->firstOrFail();
    $draft = auditorPortfolioProps($fixture['user']);
    expect($draft['reports'])->toBe([])->and($draft['filters'])->toBe(auditorPortfolioFilters(0, 0, 0))
        ->and($draft['conflicts']['files'])->toBe([['id' => $assignment->id, 'revision' => $assignment->revision,
            'business' => 'Synthetic business', 'note_id' => null, 'allowed_actions' => ['conflict.declare']]])
        ->and($draft['owed'])->toBe([['id' => $assignment->id, 'business' => 'Synthetic business', 'district' => 'Gasabo', 'kind' => 'flash',
            'due_at' => $assignment->state['complete_by'], 'link' => ['url' => '/auditor/jobs/'.$assignment->id, 'method' => 'get']]]);
    $sealed = AuditSealingFixture::seal($fixture)['data']['sealed'];
    $report = ['id' => $fixture['report']->id, 'business' => 'Synthetic business', 'kind' => 'flash', 'month' => null, 'district' => 'Gasabo',
        'filed_on' => $sealed['sealed_at'], 'due_on' => $assignment->state['complete_by'], 'status' => 'awaiting_cosign', 'late_days' => null,
        'rejection' => null, 'link' => ['url' => '/auditor/reports/'.$fixture['report']->id, 'method' => 'get']];
    expect($report['due_on'])->toBeString();
    $props = auditorPortfolioProps($fixture['user']);
    expect($props['reports'])->toBe([$report])->and($props['filters'])->toBe(auditorPortfolioFilters(1, 1, 0))
        ->and($props['owed'])->toBe([]);
    expect(auditorPortfolioProps($fixture['user'], ['filter' => 'published']))->toMatchArray(['filter' => 'published', 'reports' => [], 'filters' => auditorPortfolioFilters(1, 1, 0)]);
    $this->get($report['link']['url'])->assertOk()->assertInertia(fn (Assert $page): Assert => $page->component('auditor/audit'));
    expect(AuditSealingFixture::cosign($fixture)['code'])->toBe('REPORT_PUBLISHED');
    $published = [...$report, 'status' => 'published'];
    expect(auditorPortfolioProps($fixture['user'], ['filter' => 'published'])['reports'])->toBe([$published])
        ->and(auditorPortfolioProps($fixture['user'], ['filter' => 'awaiting_cosign']))->toMatchArray(['filter' => 'awaiting_cosign', 'reports' => []])
        ->and(auditorPortfolioProps($fixture['user'], ['filter' => 'late']))->toMatchArray(['filter' => 'all', 'reports' => [$published], 'filters' => auditorPortfolioFilters(1, 0, 1)])
        ->and(auditorPortfolioProps($fixture['user'], ['filter' => ['published']])['filter'])->toBe('all')
        ->and(auditorPortfolioProps(AuditorFixture::make()['user'])['reports'])->toBe([]);
});

it('names a monthly report by its period and dates it by the monthly report window', function (): void {
    $this->travelTo(now('UTC')->startOfMonth()->addDays(4)->setTime(10, 0));
    $fixture = AuditSealingFixture::ready(kind: 'monthly');
    AuditSealingFixture::seal($fixture);
    $reports = auditorPortfolioProps($fixture['user'])['reports'];
    $period = now('Africa/Kigali')->subMonthNoOverflow()->format('Y-m');
    expect($reports)->toHaveCount(1)->and($reports[0])->toMatchArray(['kind' => 'monthly', 'month' => $period.'-01',
        'due_on' => app(AuditReportWindow::class)->dueAt($period), 'status' => 'awaiting_cosign']);
});

it('never reports an all-clear while an owed verification may lie beyond the first page of assignments', function (): void {
    $fixture = AuditAssignmentFixture::make(1);
    $partner = $fixture['partners'][0];
    $outstanding = AuditAssignmentFixture::request($fixture);
    AuditAssignmentFixture::respond($partner['user'], $outstanding);
    $whole = auditorPortfolioProps($partner['user']);
    expect(array_column($whole['owed'], 'id'))->toBe([$outstanding->id])->and($whole['owed_complete'])->toBeTrue();
    $this->travel(1)->minutes();
    foreach (range(1, 25) as $newer) {
        AuditAssignmentFixture::engagement($partner['party']->id);
    }
    $cut = auditorPortfolioProps($partner['user']);
    expect(array_column($cut['owed'], 'id'))->not->toContain($outstanding->id)->and($cut['owed_complete'])->toBeFalse()
        ->and($cut['links']['jobs'])->toBe(['url' => '/auditor/jobs', 'method' => 'get']);
});

it('declares an interest through the assigned file\'s own command and keeps only a private record of it', function (): void {
    $fixture = AuditAssignmentFixture::make(1);
    $assignment = AuditAssignmentFixture::request($fixture);
    $partner = $fixture['partners'][0];
    AuditAssignmentFixture::respond($partner['user'], $assignment);
    $props = auditorPortfolioProps($partner['user']);
    expect($props['conflicts']['files'])->toHaveCount(1)->and($props['conflicts']['files'][0]['id'])->toBe($assignment->id)
        ->and(array_column($props['owed'], 'id'))->toBe([$assignment->id]);
    $url = str_replace('{assignment}', $assignment->id, $props['conflicts']['declare']['url']);
    $receipt = $this->postJson($url, ['identity_context_revision' => 1, 'expected_revision' => $assignment->refresh()->revision,
        'request_id' => (string) Str::uuid(), 'assignment_id' => $assignment->id, 'kind' => 'family_or_business', 'reason' => 'A private relationship.'])
        ->assertOk()->assertJsonPath('code', 'CONFLICT_RECORDED')->json('data.conflict');
    $props = auditorPortfolioProps($partner['user']);
    expect($props['conflicts']['files'])->toBe([])->and($props['owed'])->toBe([])->and($props['conflicts']['record'])->toBe([[
        'conflict_id' => $receipt['conflict_id'], 'assignment_id' => $assignment->id, 'business' => null, 'note_id' => null,
        'kind' => 'family_or_business', 'declared_on' => $receipt['declared_at'],
    ]]);
});
