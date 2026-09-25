<?php

declare(strict_types=1);

use App\Application\Identity\SelectActiveRole;
use App\Models\RoleMembership;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\BusinessAuthorityFixture as AuthorityFixture;
use Tests\Support\BusinessQuoteFixture as QuoteFixture;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-09-25T03:00:00Z'));
    $this->withoutVite();
});

/**
 * Compares a live value with the fixture record that stands for its TypeScript type, key by key,
 * descending into objects and into the first record of two non-empty lists of objects.
 *
 * @param  array<array-key, mixed>  $live
 * @param  array<array-key, mixed>  $fixture
 */
function roleHomeUiSameShape(array $live, array $fixture, string $path): void
{
    expect(array_keys($live))->toEqualCanonicalizing(array_keys($fixture), "Keys differ at {$path}");
    foreach ($live as $key => $value) {
        $other = $fixture[$key];
        if (! is_array($value) || ! is_array($other)) {
            continue;
        }
        if (! array_is_list($value) && ! array_is_list($other)) {
            roleHomeUiSameShape($value, $other, "{$path}.{$key}");
        } elseif ($value !== [] && $other !== [] && is_array($value[0]) && is_array($other[0])) {
            roleHomeUiSameShape($value[0], $other[0], "{$path}.{$key}.0");
        }
    }
}

/** @return array<string, mixed> */
function roleHomeUiFixture(): array
{
    /** @var array{component: string, props: array<string, mixed>} $fixture */
    $fixture = json_decode((string) file_get_contents(resource_path('fixtures/ui/role-home-business.json')), true, flags: JSON_THROW_ON_ERROR);

    return $fixture['props'];
}

/**
 * The live role landing page's own props, without the props every Inertia page shares.
 *
 * @return array<string, mixed>
 */
function roleHomeUiProps(User $user, string $url): array
{
    $props = [];
    actingAs($user)->get($url)->assertOk()->assertInertia(function (Assert $inertia) use (&$props): Assert {
        $props = $inertia->toArray()['props'];

        return $inertia->component('identity/role-home');
    });

    return array_diff_key($props, array_flip(['auth', 'name', 'locale', 'errors', 'sidebarOpen', 'nonLiveEnvironment', 'head']));
}

it('lists none, draft, submitted and view-only businesses in the shape the entry list reads', function (): void {
    $ready = QuoteFixture::ready();
    QuoteFixture::submit($ready, QuoteFixture::acceptance($ready));
    $authority = $ready['audit']['authority'];
    $user = $authority['users'][0];
    $none = AuthorityFixture::configure(AuthorityFixture::relatedOrganization($authority), 1)['data']['business']['id'];
    $draftAuthority = AuthorityFixture::relatedOrganization($authority);
    $draft = AuthorityFixture::configure($draftAuthority, 1)['data']['business']['id'];
    $created = actingAs($user)->postJson("/business/{$draft}/applications",
        ['identity_context_revision' => 1, 'expected_revision' => 0, 'request_id' => (string) Str::uuid()])->assertOk()->json();
    $viewOnly = AuthorityFixture::relatedOrganization($authority);
    $viewOnly['terms']['people'] = array_map(fn (array $person): array => $person['party_id'] === $authority['people'][0]->id
        ? [...$person, 'permissions' => ['business.view']] : $person, $viewOnly['terms']['people']);
    $viewOnlyId = AuthorityFixture::configure($viewOnly, 1)['data']['business']['id'];

    $props = roleHomeUiProps($user, '/business');
    $fixture = roleHomeUiFixture();
    roleHomeUiSameShape($props, $fixture, 'props');
    $entries = array_column($props['business_applications']['entries'], null, 'business_id');
    $shapes = $fixture['business_applications']['entries'];

    expect(array_keys($entries))->toEqualCanonicalizing([$ready['audit']['business'], $none, $draft, $viewOnlyId])
        ->and($props['role'])->toBe('business')
        ->and($props['business_applications']['identity_context_revision'])->toBe($props['identity']['context_revision'])
        ->and($props['business_applications']['operation'])->toBe(['url' => '/business/application-operations/{request_id}', 'method' => 'get'])
        ->and($props['business_applications']['pagination'])->toBe(['next' => null]);

    roleHomeUiSameShape($entries[$none], $shapes[0], 'none');
    expect($entries[$none]['application'])->toBeNull()
        ->and($entries[$none]['allowed_actions'])->toBe(['application.create'])
        ->and($entries[$none]['actions']['create'])->toBe(['url' => "/business/{$none}/applications", 'method' => 'post']);

    roleHomeUiSameShape($entries[$draft], $shapes[1], 'draft');
    expect($entries[$draft]['application'])->toMatchArray(['id' => $created['data']['application']['id'], 'status' => 'draft', 'step' => 'business',
        'link' => $created['data']['next']]);

    roleHomeUiSameShape($entries[$ready['audit']['business']], $shapes[2], 'submitted');
    expect($entries[$ready['audit']['business']]['application'])->toMatchArray(['id' => $ready['application']->id, 'status' => 'submitted',
        'step' => 'submitted']);

    roleHomeUiSameShape($entries[$viewOnlyId], $shapes[3], 'view-only');
    expect($entries[$viewOnlyId]['allowed_actions'])->toBe([])
        ->and($entries[$viewOnlyId]['actions']['create'])->toBeNull()
        ->and($entries[$viewOnlyId]['application'])->toBeNull()
        ->and(json_encode($props['business_applications'], JSON_THROW_ON_ERROR))->not->toContain('wallet', 'capital', 'company_code', 'story');
});

it('follows the complete next-page link and gives every other role no entries', function (): void {
    $ready = QuoteFixture::ready();
    $authority = $ready['audit']['authority'];
    $user = $authority['users'][0];
    AuthorityFixture::configure(AuthorityFixture::relatedOrganization($authority), 1);

    $first = roleHomeUiProps($user, '/business?limit=1');
    $next = $first['business_applications']['pagination']['next'];
    expect($first['business_applications']['entries'])->toHaveCount(1)
        ->and($next['method'])->toBe('get')
        ->and($next['url'])->toStartWith('/business?before=');
    $second = roleHomeUiProps($user, $next['url']);
    expect($second['business_applications']['entries'])->toHaveCount(1)
        ->and($second['business_applications']['entries'][0]['business_id'])->not->toBe($first['business_applications']['entries'][0]['business_id']);

    RoleMembership::factory()->for($authority['people'][0])->active()->create(['role' => 'investor']);
    app(SelectActiveRole::class)->handle($user->id, 'investor', $first['identity']['context_revision'], (string) Str::uuid());
    $investor = roleHomeUiProps($user, '/investor');
    expect($investor['role'])->toBe('investor')->and($investor['business_applications'])->toBeNull();
});
