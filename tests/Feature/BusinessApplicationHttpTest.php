<?php

declare(strict_types=1);

use App\Application\Business\RecordIsolatedBusinessCreditFacts;
use App\Models\BusinessApplication;
use App\Models\BusinessApplicationQuote;
use App\Models\BusinessApplicationSignature;
use App\Models\BusinessApplicationSubmission;
use App\Models\BusinessApplicationVersion;
use App\Models\RoleMembership;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BusinessApplicationFixture as DraftFixture;
use Tests\Support\BusinessAuthorityFixture;
use Tests\Support\BusinessQuoteFixture as QuoteFixture;
use Tests\Support\ConsentFixture;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-09-25T03:00:00Z'));
    $this->withoutVite();
});

/** @return array{identity_context_revision: int, expected_revision: int, request_id: string} */
function applicationHttpEnvelope(int $revision): array
{
    return ['identity_context_revision' => 1, 'expected_revision' => $revision, 'request_id' => (string) Str::uuid()];
}

it('presents an honest empty draft over web and API without creating financial facts or a quote', function (): void {
    $fixture = DraftFixture::make();
    $user = $fixture['authority']['users'][0];
    $path = '/business/'.$fixture['business']->id.'/applications/'.$fixture['application']->id;
    $web = [];
    $this->actingAs($user)->get($path)->assertOk()->assertHeader('Cache-Control', 'no-store, private')
        ->assertInertia(function (Assert $page) use (&$web): Assert {
            $web = $page->toArray()['props'];

            return $page->component('business/apply')->where('contract_version', 'business-application-v1')->where('step', 'business')
                ->where('quote', null)->where('home', null)->where('acceptance.documents', [])->where('evidence.business.company_code', null)
                ->where('evidence.verified.registry', false)->where('evidence.verified.statements', false)->where('evidence.period', null)
                ->where('evidence.totals.revenue', null)->where('evidence.existing_debt', null)->where('evidence.capacity', null)
                ->where('evidence.rating', null)->where('allowed_actions', ['application.save', 'application.evaluate']);
        });
    Sanctum::actingAs($user, ['business:read']);
    $api = $this->getJson('/api/v1'.$path)->assertOk()->assertHeader('Cache-Control', 'no-store, private')
        ->assertJsonPath('data.allowed_actions', [])->assertJsonPath('data.acceptance.signatures_complete', false)->json('data');
    expect($api['application'])->toBe($web['application'])->and($api['acceptance'])->toBe($web['acceptance'])
        ->and($web['links']['back']['url'])->toBe('/business')->and($web['shell_links']['launcher']['url'])->toBe('/dashboard')
        ->and($web['links']['operation']['url'])->toBe('/business/application-operations/{request_id}')
        ->and($api['actions']['save']['url'])->toBe('/api/v1'.$path.'/save')
        ->and($api['links']['operation']['url'])->toBe('/api/v1/business/application-operations/{request_id}')
        ->and(BusinessApplicationQuote::query()->count())->toBe(0)->and(BusinessApplicationVersion::query()->count())->toBe(1);
});

it('creates and resumes the same application using the supplied next route across transports', function (): void {
    $authority = BusinessAuthorityFixture::make();
    $configured = BusinessAuthorityFixture::configure($authority);
    $business = $configured['data']['business']['id'];
    $path = '/business/'.$business.'/applications';
    $body = applicationHttpEnvelope(0);
    $created = $this->actingAs($authority['users'][0])->postJson($path, $body)->assertOk()->assertJsonPath('code', 'APPLICATION_CREATED')->json();
    $id = $created['data']['application']['id'];
    expect(Str::isUlid($id))->toBeTrue()->and($created['data']['next']['url'])->toBe($path.'/'.$id);
    Sanctum::actingAs($authority['users'][0], ['business:read', 'business:command']);
    $this->postJson('/api/v1'.$path, $body)->assertOk()->assertJsonPath('operation_id', $created['operation_id'])
        ->assertJsonPath('data.next.url', '/api/v1'.$path.'/'.$id);
    $this->postJson('/api/v1'.$path, applicationHttpEnvelope(0))->assertOk()->assertJsonPath('code', 'APPLICATION_RESUMED')
        ->assertJsonPath('data.application.id', $id);
    $this->getJson('/api/v1/business/application-operations/'.$body['request_id'].'?command=create&identity_context_revision=1')->assertOk()
        ->assertJsonPath('operation_id', $created['operation_id']);
    Sanctum::actingAs($authority['users'][0], ['business:read']);
    $this->getJson('/api/v1/business/application-operations/'.$body['request_id'].'?command=create&identity_context_revision=1')->assertOk()
        ->assertJsonPath('operation_id', $created['operation_id'])->assertJsonPath('allowed_actions', []);
    expect(BusinessApplication::query()->count())->toBe(1)->and(BusinessApplicationVersion::query()->count())->toBe(1);
});

it('executes save evaluate lower selection review submit and immutable lookup with a real scoped bearer token', function (): void {
    $fixture = QuoteFixture::make();
    ConsentFixture::record($fixture['audit']['staff']);
    $user = $fixture['audit']['authority']['users'][0];
    $token = $user->createToken('Synthetic application HTTP test', ['business:read', 'business:command'])->plainTextToken;
    $this->withToken($token);
    $path = '/api/v1/business/'.$fixture['audit']['business'].'/applications/'.$fixture['application']->id;
    $page = $this->getJson($path)->assertOk()->assertJsonPath('data.evidence.period.months', 36)
        ->assertJsonPath('data.evidence.totals.revenue.amount', '144000000')->assertJsonPath('data.evidence.years.0.months', 4)
        ->assertJsonPath('data.evidence.existing_debt.amount', '0')->assertJsonPath('data.evidence.debt_verified', false)
        ->assertJsonPath('data.evidence.eligibility.status', 'eligible')->json('data');
    $save = $this->postJson($path.'/save', [...applicationHttpEnvelope(2), ...DraftFixture::fields('12000000')])->assertOk()
        ->assertJsonPath('data.quote', null)->assertJsonPath('data.acceptance.required_signatures', 1)->json();
    $evaluate = [...applicationHttpEnvelope($save['revision']), 'target' => '12000000', 'term_months' => 6, 'evidence_version' => $page['evidence']['version']];
    $quote = $this->postJson($path.'/evaluate', $evaluate)->assertOk()->assertJsonPath('code', 'APPLICATION_EVALUATED')
        ->assertJsonPath('data.quote.principal.amount', '10800000')->assertJsonPath('data.quote.rate_pct', '11.1')
        ->assertJsonPath('data.quote.rate_basis.band', 'strong')->assertJsonPath('data.quote.reserve', null)
        ->assertJsonPath('allowed_actions', ['application.save', 'application.evaluate', 'application.submit'])->json();
    $lower = $this->postJson($path.'/evaluate', [...$evaluate, ...applicationHttpEnvelope($quote['revision']), 'accepted_principal' => '5000000'])
        ->assertOk()->assertJsonPath('data.quote.principal.amount', '5000000')->assertJsonPath('data.quote.offered_principal.amount', '10800000')
        ->assertJsonPath('data.quote.total.amount', '5555000')->json();
    $review = $this->postJson($path.'/save', [...applicationHttpEnvelope($lower['revision']), ...DraftFixture::fields('12000000'), 'step' => 'review'])
        ->assertOk()->assertJsonPath('data.quote.quote_id', $lower['data']['quote']['quote_id'])->json();
    $this->getJson($path)->assertOk()->assertJsonPath('data.step', 'review')->assertJsonCount(2, 'data.acceptance.documents');
    $submit = [...applicationHttpEnvelope($review['revision']), ...QuoteFixture::acceptance($fixture)];
    $receipt = $this->postJson($path.'/submit', $submit)->assertOk()->assertJsonPath('code', 'APPLICATION_SUBMITTED')
        ->assertJsonPath('allowed_actions', [])->assertJsonPath('data.acceptance.signatures_complete', true)
        ->assertJsonPath('data.submission.note_id', null)->json();
    $submitted = $this->getJson($path)->assertOk()->assertJsonPath('data.step', 'submitted')->json('data');
    $this->travel(1)->minutes();
    $lookup = '/api/v1/business/application-operations/'.$submit['request_id'].'?command=submit&identity_context_revision=1';
    $recovered = $this->getJson($lookup)->assertOk()->assertJsonPath('data', $receipt['data'])->json();
    expect($recovered['recorded_at'])->toBe($receipt['recorded_at'])->and($recovered['server_time'])->not->toBe($receipt['server_time']);
    $this->postJson($path.'/submit', $submit)->assertOk()->assertJsonPath('operation_id', $receipt['operation_id']);
    app(RecordIsolatedBusinessCreditFacts::class)->handle($fixture['audit']['staff']->id, $fixture['audit']['business'], 1, null,
        'synthetic:withdrawn', 'Withdraw inaccurate synthetic facts.', (string) Str::uuid());
    $this->travel(35)->days();
    $this->getJson($path)->assertOk()->assertJsonPath('data.evidence', $submitted['evidence'])
        ->assertJsonPath('data.quote', $submitted['quote'])->assertJsonPath('data.acceptance', $submitted['acceptance']);
    expect(BusinessApplicationSignature::query()->count())->toBe(1)->and(BusinessApplicationSubmission::query()->count())->toBe(1)
        ->and(json_encode($submitted, JSON_THROW_ON_ERROR))->not->toContain('engine_score', 'credit_source_reference', 'actor_user_id', 'source_hashes', 'instalment_conduct');
});

it('keeps earlier view links read only and preserves the saved pointer during autosave', function (): void {
    $fixture = QuoteFixture::ready();
    Sanctum::actingAs($fixture['audit']['authority']['users'][0], ['business:read', 'business:command']);
    $path = '/api/v1/business/'.$fixture['audit']['business'].'/applications/'.$fixture['application']->id;
    $before = BusinessApplicationVersion::query()->count();
    $this->getJson($path.'?view_step=raise')->assertOk()->assertJsonPath('data.step', 'raise')
        ->assertJsonPath('data.links.back.url', $path.'?view_step=business');
    $this->getJson($path.'?view_step=business')->assertOk()->assertJsonPath('data.step', 'business');
    expect($fixture['application']->refresh()->step)->toBe('review')->and(BusinessApplicationVersion::query()->count())->toBe($before);
    $this->postJson($path.'/save', [...applicationHttpEnvelope(4), ...DraftFixture::fields('8000000')])->assertOk()->assertJsonPath('data.quote', null);
    $this->getJson($path)->assertOk()->assertJsonPath('data.step', 'review')->assertJsonPath('data.quote', null);
    $this->getJson($path.'?view_step=invalid')->assertUnprocessable();
    $fresh = DraftFixture::make();
    Sanctum::actingAs($fresh['authority']['users'][0], ['business:read']);
    $this->getJson('/api/v1/business/'.$fresh['business']->id.'/applications/'.$fresh['application']->id.'?view_step=review')
        ->assertOk()->assertJsonPath('data.step', 'business');
});

it('records stale evaluation echoes without using any client financial facts', function (string $field, mixed $value): void {
    $fixture = QuoteFixture::make();
    Sanctum::actingAs($fixture['audit']['authority']['users'][0], ['business:read', 'business:command']);
    $path = '/api/v1/business/'.$fixture['audit']['business'].'/applications/'.$fixture['application']->id;
    $page = $this->getJson($path)->assertOk()->json('data');
    $body = [...applicationHttpEnvelope(2), 'target' => '12000000', 'term_months' => 6, 'evidence_version' => $page['evidence']['version'], $field => $value];
    $receipt = $this->postJson($path.'/evaluate', $body)->assertConflict()->assertJsonPath('code', 'QUOTE_STALE')->json();
    $this->getJson('/api/v1/business/application-operations/'.$body['request_id'].'?command=evaluate&identity_context_revision=1')->assertConflict()
        ->assertJsonPath('operation_id', $receipt['operation_id']);
    expect(BusinessApplicationQuote::query()->count())->toBe(0)->and($fixture['application']->refresh()->revision)->toBe(2);
})->with([['target', '3000000'], ['term_months', 12], ['evidence_version', 'old-evidence']]);

it('keeps historical receipts while removing stale capabilities and preserving current page state', function (): void {
    $fixture = QuoteFixture::make();
    ConsentFixture::record($fixture['audit']['staff']);
    Sanctum::actingAs($fixture['audit']['authority']['users'][0], ['business:read', 'business:command']);
    $path = '/api/v1/business/'.$fixture['audit']['business'].'/applications/'.$fixture['application']->id;
    $page = $this->getJson($path)->json('data');
    $body = [...applicationHttpEnvelope(2), 'target' => '12000000', 'term_months' => 6, 'evidence_version' => $page['evidence']['version']];
    $receipt = $this->postJson($path.'/evaluate', $body)->assertOk()->json();
    app(RecordIsolatedBusinessCreditFacts::class)->handle($fixture['audit']['staff']->id, $fixture['audit']['business'], 1, null,
        'synthetic:withdrawn', 'Withdraw inaccurate synthetic facts.', (string) Str::uuid());
    $this->getJson('/api/v1/business/application-operations/'.$body['request_id'].'?command=evaluate&identity_context_revision=1')->assertOk()
        ->assertJsonPath('data', $receipt['data'])->assertJsonPath('allowed_actions', []);
    $this->getJson($path)->assertOk()->assertJsonPath('data.quote', null)->assertJsonPath('data.evidence.eligibility.code', 'POLICY_INPUT_REQUIRED');
    $this->postJson($path.'/save', [...applicationHttpEnvelope(3), ...DraftFixture::fields('5000000')])->assertOk();
    $this->postJson($path.'/evaluate', $body)->assertOk()->assertJsonPath('data', $receipt['data'])->assertJsonPath('allowed_actions', []);
    $this->getJson($path)->assertOk()->assertJsonPath('data.application.target.amount', '5000000');
});

it('returns recoverable field errors for domain refusals and keeps malformed envelopes outside the journal', function (): void {
    $fixture = DraftFixture::make();
    Sanctum::actingAs($fixture['authority']['users'][0], ['business:read', 'business:command']);
    $path = '/api/v1/business/'.$fixture['business']->id.'/applications/'.$fixture['application']->id;
    $body = [...applicationHttpEnvelope(1), ...DraftFixture::fields(), 'title' => str_repeat('a', 181)];
    $receipt = $this->postJson($path.'/save', $body)->assertUnprocessable()->json();
    expect($receipt['errors'])->toBe($receipt['field_errors'])->and($receipt['errors'])->toHaveKey('title');
    $this->getJson('/api/v1/business/application-operations/'.$body['request_id'].'?command=save&identity_context_revision=1')->assertUnprocessable()
        ->assertJsonPath('operation_id', $receipt['operation_id']);
    $this->postJson($path.'/save', [...$body, 'title' => 'Changed body'])->assertConflict();
    $invalid = applicationHttpEnvelope(1);
    $this->postJson($path.'/save', [...$invalid, ...DraftFixture::fields(), 'use_of_funds' => 'invalid'])->assertUnprocessable()->assertJsonMissingPath('operation_id');
    $this->getJson('/api/v1/business/application-operations/'.$invalid['request_id'].'?command=save&identity_context_revision=1')->assertNotFound();
    $this->getJson('/api/v1/business/application-operations/'.Str::uuid().'?command=unknown&identity_context_revision=1')->assertNotFound();
    $this->getJson('/api/v1/business/application-operations/'.Str::uuid().'?command=save')->assertUnprocessable();
});

it('requires each real signatory and records unaccepted consent as a refusal', function (): void {
    $fixture = QuoteFixture::ready(2);
    $path = '/business/'.$fixture['audit']['business'].'/applications/'.$fixture['application']->id;
    $acceptance = QuoteFixture::acceptance($fixture);
    $this->actingAs($fixture['audit']['authority']['users'][0])->postJson($path.'/submit', [...applicationHttpEnvelope(4), ...$acceptance])->assertOk()
        ->assertJsonPath('code', 'APPLICATION_SIGNATURE_RECORDED')->assertJsonPath('data.acceptance.signatures_complete', false)
        ->assertJsonPath('data.submission', null)->assertJsonPath('allowed_actions', ['application.save', 'application.evaluate']);
    $this->actingAs($fixture['audit']['authority']['users'][1])->get($path)->assertOk()->assertInertia(fn (Assert $page): Assert => $page
        ->where('step', 'review')->where('allowed_actions', ['application.save', 'application.evaluate', 'application.submit']));
    $this->postJson($path.'/submit', [...applicationHttpEnvelope(5), ...$acceptance, 'terms' => false])->assertUnprocessable()
        ->assertJsonPath('code', 'APPLICATION_ACCEPTANCE_REQUIRED')->assertJsonValidationErrors('terms');
    $this->postJson($path.'/submit', [...applicationHttpEnvelope(5), ...$acceptance])->assertOk()->assertJsonPath('code', 'APPLICATION_SUBMITTED');
    expect(BusinessApplicationSignature::query()->count())->toBe(2);
});

it('enforces authentication token abilities current identity and application scope before returning private state', function (): void {
    $fixture = DraftFixture::make();
    $user = $fixture['authority']['users'][0];
    $path = '/business/'.$fixture['business']->id.'/applications/'.$fixture['application']->id;
    $this->get($path)->assertRedirect(route('login'));
    $this->getJson('/api/v1'.$path)->assertUnauthorized();
    Sanctum::actingAs($user, []);
    $this->getJson('/api/v1'.$path)->assertForbidden();
    $this->postJson('/api/v1'.$path.'/save', [...applicationHttpEnvelope(1), ...DraftFixture::fields()])->assertForbidden();
    Sanctum::actingAs($user, ['business:read']);
    $this->postJson('/api/v1'.$path.'/save', [...applicationHttpEnvelope(1), ...DraftFixture::fields()])->assertForbidden();
    $this->getJson('/api/v1'.$path.'?identity_context_revision=0')->assertConflict();
    $this->getJson('/api/v1/business/'.$fixture['business']->id.'/applications/'.Str::ulid())->assertNotFound();
    $other = DraftFixture::make();
    $this->getJson('/api/v1/business/'.$fixture['business']->id.'/applications/'.$other['application']->id)->assertNotFound();
    RoleMembership::query()->where('party_id', $fixture['authority']['people'][0]->id)->where('role', 'business')->update(['status' => 'revoked']);
    $this->getJson('/api/v1'.$path)->assertForbidden();
    $this->actingAs($user)->get($path)->assertForbidden()->assertInertia(fn (Assert $page): Assert => $page->component('identity/access-denied'));
});
