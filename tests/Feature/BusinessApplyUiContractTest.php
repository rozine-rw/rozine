<?php

declare(strict_types=1);

use App\Application\Business\RecordIsolatedBusinessCreditFacts;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\BusinessApplicationFixture as DraftFixture;
use Tests\Support\BusinessAuthorityFixture;
use Tests\Support\BusinessQuoteFixture as QuoteFixture;
use Tests\Support\ConsentFixture;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-09-25T03:00:00Z'));
    $this->withoutVite();
});

/** The keys every operation Resource carries for the pages (resources/js/types/operation.ts). */
const APPLY_UI_OPERATION_KEYS = ['operation_id', 'status', 'code', 'data', 'revision', 'policy_version', 'recorded_at', 'server_time',
    'allowed_actions', 'field_errors'];

/** The snapshot a completed save, evaluate or submit returns (`ApplicationSnapshot` in resources/js/types/business.ts). */
const APPLY_UI_SNAPSHOT_KEYS = ['application', 'quote', 'acceptance', 'submission', 'next'];

/**
 * Compares a live page with the fixture that stands for its TypeScript contract, key by key. It
 * descends into objects and into the first record of two non-empty lists of objects; a null on
 * either side, or a list of scalars, ends the comparison at that key.
 *
 * @param  array<array-key, mixed>  $live
 * @param  array<array-key, mixed>  $fixture
 */
function applyUiSameShape(array $live, array $fixture, string $path = 'props'): void
{
    expect(array_keys($live))->toEqualCanonicalizing(array_keys($fixture), "Keys differ at {$path}");
    foreach ($live as $key => $value) {
        $other = $fixture[$key];
        if (! is_array($value) || ! is_array($other)) {
            continue;
        }
        if (! array_is_list($value) && ! array_is_list($other)) {
            applyUiSameShape($value, $other, "{$path}.{$key}");
        } elseif ($value !== [] && $other !== [] && is_array($value[0]) && is_array($other[0])) {
            applyUiSameShape($value[0], $other[0], "{$path}.{$key}.0");
        }
    }
}

/** @return array<string, mixed> */
function applyUiFixture(string $name): array
{
    /** @var array{component: string, props: array<string, mixed>} $fixture */
    $fixture = json_decode((string) file_get_contents(resource_path("fixtures/ui/{$name}.json")), true, flags: JSON_THROW_ON_ERROR);

    return $fixture['props'];
}

/**
 * The live `business/apply` page's own props, without the props every Inertia page shares.
 *
 * @return array<string, mixed>
 */
function applyUiProps(User $user, string $url): array
{
    $props = [];
    actingAs($user)->get($url)->assertOk()->assertHeader('Cache-Control', 'no-store, private')
        ->assertInertia(function (Assert $inertia) use (&$props): Assert {
            $props = $inertia->toArray()['props'];

            return $inertia->component('business/apply');
        });

    return array_diff_key($props, array_flip(['auth', 'name', 'locale', 'errors', 'sidebarOpen', 'nonLiveEnvironment', 'head']));
}

/**
 * The command envelope exactly as the pages send it.
 *
 * @return array{identity_context_revision: int, expected_revision: int, request_id: string}
 */
function applyUiEnvelope(int $revision): array
{
    return ['identity_context_revision' => 1, 'expected_revision' => $revision, 'request_id' => (string) Str::uuid()];
}

/**
 * The lookup the pages send for a held command: its name and the identity context as the query.
 */
function applyUiLookup(string $requestId, string $command): string
{
    return '/business/application-operations/'.$requestId.'?command='.$command.'&identity_context_revision=1';
}

/**
 * A draft as the Raise step sends it: an autosave carries no `step`.
 *
 * @return array<string, mixed>
 */
function applyUiDraft(string $target = '12000000', ?string $step = null): array
{
    return [...DraftFixture::fields($target), ...($step === null ? [] : ['step' => $step])];
}

/**
 * Checks the evidence window the business step labels: the server's period, and each year's own
 * month count, which together cover exactly the period.
 *
 * @param  array<string, mixed>  $evidence
 */
function applyUiEvidenceWindow(array $evidence): void
{
    expect($evidence['period'])->toHaveKeys(['from_month', 'through_month', 'months'])
        ->and($evidence['period']['from_month'])->toMatch('/^\d{4}-\d{2}$/')
        ->and($evidence['period']['through_month'])->toMatch('/^\d{4}-\d{2}$/')
        ->and(array_sum(array_column($evidence['years'], 'months')))->toBe($evidence['period']['months']);
    foreach ($evidence['years'] as $year) {
        expect($year['months'])->toBeInt()->toBeGreaterThanOrEqual(1)->toBeLessThanOrEqual(12);
    }
}

it('renders a just-created draft with the shape of the live-minimal fixture and a sole trader’s null company code', function (): void {
    $fixture = DraftFixture::make();
    $business = $fixture['business']->id;
    $application = $fixture['application']->id;
    $path = "/business/{$business}/applications/{$application}";
    $props = applyUiProps($fixture['authority']['users'][0], $path);

    applyUiSameShape($props, applyUiFixture('business-apply-live-minimal'));
    expect(Str::isUlid($props['business_id']))->toBeTrue()
        ->and(Str::isUlid($props['application']['id']))->toBeTrue()
        ->and($props['contract_version'])->toBe('business-application-v1')
        ->and($props['step'])->toBe('business')
        ->and($props['quote'])->toBeNull()
        ->and($props['submission'])->toBeNull()
        ->and($props['home'])->toBeNull()
        ->and($props)->not->toHaveKey('preview_outcome')
        ->and($props['application'])->toBe(['id' => $application, 'revision' => 1, 'title' => '', 'target' => null,
            'term_months' => null, 'use_of_funds' => [], 'story' => ''])
        ->and($props['evidence']['business']['company_code'])->toBeNull()
        ->and($props['evidence']['verified'])->toBe(['registry' => false, 'statements' => false])
        ->and($props['evidence']['period'])->toBeNull()
        ->and($props['evidence']['years'])->toBe([])
        ->and($props['evidence']['debt_verified'])->toBeFalse()
        ->and($props['evidence']['eligibility'])->toHaveKeys(['status', 'code', 'message'])
        ->and($props['acceptance']['documents'])->toBe([])
        ->and($props['acceptance']['disclosures'])->toBe([])
        ->and($props['acceptance']['fee_on_approval'])->toBe(['currency' => 'RWF', 'amount' => '0'])
        ->and($props['allowed_actions'])->toBe(['application.save', 'application.evaluate'])
        ->and($props['links'])->toBe([
            'close' => ['url' => '/business', 'method' => 'get'],
            'back' => ['url' => '/business', 'method' => 'get'],
            'operation' => ['url' => '/business/application-operations/{request_id}', 'method' => 'get'],
        ])
        ->and($props['shell_links'])->toBe(['home' => ['url' => '/business', 'method' => 'get'],
            'launcher' => ['url' => '/dashboard', 'method' => 'get'], 'reports' => null, 'profile' => null])
        ->and($props['actions'])->toBe([
            'save' => ['url' => "{$path}/save", 'method' => 'post'],
            'evaluate' => ['url' => "{$path}/evaluate", 'method' => 'post'],
            'submit' => ['url' => "{$path}/submit", 'method' => 'post'],
        ]);
});

it('renders a saved Raise draft with its evidence window, and the earlier view by view_step', function (): void {
    $fixture = QuoteFixture::make();
    $user = $fixture['audit']['authority']['users'][0];
    $path = '/business/'.$fixture['audit']['business'].'/applications/'.$fixture['application']->id;
    $props = applyUiProps($user, $path);

    applyUiSameShape($props, applyUiFixture('business-apply-live'));
    applyUiEvidenceWindow($props['evidence']);
    expect($props['step'])->toBe('raise')
        ->and($props['quote'])->toBeNull()
        ->and($props['evidence']['period']['months'])->toBe(36)
        ->and($props['evidence']['eligibility'])->toBe(['status' => 'eligible'])
        ->and($props['evidence']['business']['company_code'])->toBeNull()
        ->and($props['evidence']['verified'])->toBe(['registry' => false, 'statements' => true])
        ->and($props['evidence']['debt_verified'])->toBeFalse()
        ->and($props['evidence']['existing_debt'])->toBe(['currency' => 'RWF', 'amount' => '0'])
        ->and($props['links']['back'])->toBe(['url' => "{$path}?view_step=business", 'method' => 'get']);

    $earlier = applyUiProps($user, "{$path}?view_step=business");
    applyUiSameShape($earlier, $props);
    expect($earlier['step'])->toBe('business')
        ->and($earlier['links']['back'])->toBe(['url' => '/business', 'method' => 'get'])
        ->and($earlier['application'])->toBe($props['application'])
        ->and($fixture['application']->refresh()->step)->toBe('raise');
});

it('renders a ready review with its legal text and a submit capability', function (): void {
    $ready = QuoteFixture::ready();
    $path = '/business/'.$ready['audit']['business'].'/applications/'.$ready['application']->id;
    $review = applyUiProps($ready['audit']['authority']['users'][0], $path);
    $shape = applyUiFixture('business-apply-review');

    applyUiSameShape($review, [...$shape, 'home' => null]);
    expect($review['step'])->toBe('review')
        ->and($review['quote']['status'])->toBe('ready')
        ->and($review['quote']['reserve'])->toBeNull()
        ->and(array_column($review['acceptance']['documents'], 'kind'))->toEqualCanonicalizing(['terms', 'privacy'])
        ->and($review['acceptance']['disclosures'])->not->toBe([])
        ->and($review['acceptance']['required_signatures'])->toBe(1)
        ->and($review['allowed_actions'])->toBe(['application.save', 'application.evaluate', 'application.submit'])
        ->and($review['links']['back'])->toBe(['url' => "{$path}?view_step=raise", 'method' => 'get']);
});

it('renders a review with no published legal text as the honest unavailable state, without a submit capability', function (): void {
    $bare = QuoteFixture::make();
    $owner = $bare['audit']['authority']['users'][0];
    QuoteFixture::evaluate($bare);
    $barePath = '/business/'.$bare['audit']['business'].'/applications/'.$bare['application']->id;
    $raise = applyUiProps($owner, $barePath);
    actingAs($owner)->postJson("{$barePath}/save", [...applyUiEnvelope($raise['application']['revision']), ...applyUiDraft(step: 'review')])
        ->assertOk()->assertJsonPath('code', 'APPLICATION_SAVED');
    $unavailable = applyUiProps($owner, $barePath);

    applyUiSameShape($unavailable, [...applyUiFixture('business-apply-no-legal'), 'quote' => null]);
    expect($unavailable['step'])->toBe('review')
        ->and($unavailable['quote']['status'])->toBe('ready')
        ->and($unavailable['acceptance']['documents'])->toBe([])
        ->and($unavailable['acceptance']['disclosures'])->toBe([])
        ->and($unavailable['allowed_actions'])->not->toContain('application.submit');
});

it('answers the create, save and evaluate the pages send with the operation and snapshot shapes they read', function (): void {
    $authority = BusinessAuthorityFixture::make();
    $business = BusinessAuthorityFixture::configure($authority)['data']['business']['id'];
    $create = applyUiEnvelope(0);
    $created = actingAs($authority['users'][0])->postJson("/business/{$business}/applications", $create)->assertOk()
        ->assertJsonPath('code', 'APPLICATION_CREATED')->json();
    expect(array_keys($created))->toContain(...APPLY_UI_OPERATION_KEYS)
        ->and($created['data']['next']['url'])->toBe("/business/{$business}/applications/".$created['data']['application']['id']);
    $this->getJson(applyUiLookup($create['request_id'], 'create'))->assertOk()->assertJsonPath('operation_id', $created['operation_id']);
    actingAs($authority['users'][0])->postJson("/business/{$business}/applications", applyUiEnvelope(0))->assertOk()
        ->assertJsonPath('code', 'APPLICATION_RESUMED')->assertJsonPath('data.next', $created['data']['next']);

    $fixture = QuoteFixture::make();
    $user = $fixture['audit']['authority']['users'][0];
    $path = '/business/'.$fixture['audit']['business'].'/applications/'.$fixture['application']->id;
    $page = applyUiProps($user, $path);
    $save = actingAs($user)->postJson("{$path}/save", [...applyUiEnvelope($page['application']['revision']), ...applyUiDraft()])
        ->assertOk()->assertJsonPath('code', 'APPLICATION_SAVED')->json();
    expect(array_keys($save))->toContain(...APPLY_UI_OPERATION_KEYS)
        ->and(array_keys($save['data']))->toEqualCanonicalizing(APPLY_UI_SNAPSHOT_KEYS)
        ->and($save['data']['next'])->toBe(['url' => $path, 'method' => 'get']);
    applyUiSameShape($save['data']['application'], $page['application'], 'save.data.application');
    applyUiSameShape($save['data']['acceptance'], $page['acceptance'], 'save.data.acceptance');
    expect($fixture['application']->refresh()->step)->toBe('raise');

    $evaluate = [...applyUiEnvelope($save['revision']), 'target' => '12000000', 'term_months' => 6, 'evidence_version' => $page['evidence']['version']];
    $evaluated = $this->postJson("{$path}/evaluate", $evaluate)->assertOk()->assertJsonPath('code', 'APPLICATION_EVALUATED')->json();
    expect(array_keys($evaluated['data']))->toEqualCanonicalizing(APPLY_UI_SNAPSHOT_KEYS);
    applyUiSameShape($evaluated['data']['quote'], applyUiFixture('business-apply-live')['quote'], 'evaluate.data.quote');
    expect($evaluated['data']['quote']['offered_principal'])->toHaveKeys(['currency', 'amount']);

    $this->postJson("{$path}/evaluate", [...$evaluate, ...applyUiEnvelope($evaluated['revision']), 'term_months' => 3])
        ->assertConflict()->assertJsonPath('code', 'QUOTE_STALE');
});

it('records one signature of two, replays a recorded refusal by lookup, then submits without a note', function (): void {
    $ready = QuoteFixture::ready(2);
    [$first, $second] = $ready['audit']['authority']['users'];
    $path = '/business/'.$ready['audit']['business'].'/applications/'.$ready['application']->id;
    $acceptance = QuoteFixture::acceptance($ready);
    $page = applyUiProps($first, $path);

    $signed = actingAs($first)->postJson("{$path}/submit", [...applyUiEnvelope($page['application']['revision']), ...$acceptance])->assertOk()
        ->assertJsonPath('code', 'APPLICATION_SIGNATURE_RECORDED')->assertJsonPath('data.submission', null)
        ->assertJsonPath('data.acceptance.signatures_complete', false)->json();
    expect(array_keys($signed['data']))->toEqualCanonicalizing(APPLY_UI_SNAPSHOT_KEYS)
        ->and($signed['allowed_actions'])->not->toContain('application.submit')
        ->and(array_column($signed['data']['acceptance']['signers'], 'state'))->toBe(['signed', 'pending']);
    $waiting = applyUiProps($first, $path);
    expect($waiting['step'])->toBe('review')->and($waiting['allowed_actions'])->not->toContain('application.submit');

    $refused = [...applyUiEnvelope($signed['revision']), ...$acceptance, 'terms' => false];
    $receipt = actingAs($second)->postJson("{$path}/submit", $refused)->assertUnprocessable()
        ->assertJsonPath('code', 'APPLICATION_ACCEPTANCE_REQUIRED')->json();
    expect($receipt['errors'])->toHaveKey('terms')->and($receipt['field_errors'])->toBe($receipt['errors']);
    $this->getJson(applyUiLookup($refused['request_id'], 'submit'))->assertUnprocessable()
        ->assertJsonPath('operation_id', $receipt['operation_id'])->assertJsonPath('errors', $receipt['errors']);

    $submitted = $this->postJson("{$path}/submit", [...applyUiEnvelope($signed['revision']), ...$acceptance])->assertOk()
        ->assertJsonPath('code', 'APPLICATION_SUBMITTED')->assertJsonPath('data.submission.note_id', null)->json();
    expect($submitted['data']['next'])->toBe(['url' => $path, 'method' => 'get']);
    $done = applyUiProps($second, $path);
    applyUiSameShape($done, [...applyUiFixture('business-apply-submitted'), 'home' => null, 'shell_links' => $done['shell_links']]);
    expect($done['step'])->toBe('submitted')->and($done['allowed_actions'])->toBe([])
        ->and($done['submission']['application_id'])->toBe($ready['application']->id);
});

it('recovers a receipt that later facts overtook with empty capabilities, while the page reads the current facts', function (): void {
    $fixture = QuoteFixture::make();
    ConsentFixture::record($fixture['audit']['staff']);
    $user = $fixture['audit']['authority']['users'][0];
    $path = '/business/'.$fixture['audit']['business'].'/applications/'.$fixture['application']->id;
    $page = applyUiProps($user, $path);
    $evaluate = [...applyUiEnvelope($page['application']['revision']), 'target' => '12000000', 'term_months' => 6,
        'evidence_version' => $page['evidence']['version']];
    $receipt = actingAs($user)->postJson("{$path}/evaluate", $evaluate)->assertOk()->json();
    app(RecordIsolatedBusinessCreditFacts::class)->handle($fixture['audit']['staff']->id, $fixture['audit']['business'], 1, null,
        'synthetic:withdrawn', 'Withdraw inaccurate synthetic facts.', (string) Str::uuid());

    $recovered = $this->getJson(applyUiLookup($evaluate['request_id'], 'evaluate'))->assertOk()->json();
    expect($recovered['data'])->toBe($receipt['data'])
        ->and($recovered['recorded_at'])->toBe($receipt['recorded_at'])
        ->and($recovered['allowed_actions'])->toBe([]);

    /* The receipt still names a ready quote; the page's current facts no longer do. */
    $current = applyUiProps($user, $path);
    expect($recovered['data']['quote']['status'])->toBe('ready')
        ->and($current['quote'])->toBeNull()
        ->and($current['evidence']['eligibility']['status'])->toBe('ineligible');
});
