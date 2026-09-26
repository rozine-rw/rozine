<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessApplicationStore;
use App\Application\Business\CreateBusinessApplication;
use App\Application\Business\FindBusinessOperation;
use App\Application\Business\ListBusinessApplications;
use App\Application\Business\SaveBusinessApplication;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Models\BusinessApplication;
use App\Models\BusinessApplicationQuote;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\BusinessApplicationFixture;
use Tests\Support\BusinessQuoteFixture;
use Tests\Support\ConsentFixture;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-09-25T03:00:00Z'));
    $this->withoutVite();
});

it('blocks another unresolved application for the same Business and preserves the recorded refusal', function (): void {
    $fixture = BusinessQuoteFixture::ready();
    $owner = $fixture['audit']['authority']['users'][0];
    BusinessQuoteFixture::submit($fixture, BusinessQuoteFixture::acceptance($fixture));
    $request = (string) Str::uuid();
    $create = fn (): array => app(CreateBusinessApplication::class)->handle($owner->id, 1, $fixture['audit']['business'], 0, $request);
    $refused = $create();
    expect($refused['code'])->toBe('APPLICATION_PENDING_REVIEW')->and($refused['http_status'])->toBe(409)
        ->and($refused['status'])->toBe('rejected')->and($create())->toBe($refused)
        ->and(app(FindBusinessOperation::class)->handle($owner->id, 1, 'create', $request))->toBe($refused);
    $this->assertDatabaseCount('business_applications', 1);
    $this->assertDatabaseCount('business_application_submissions', 1);
    $other = BusinessApplicationFixture::make();
    expect($other['application']->business_id)->not->toBe($fixture['audit']['business']);
});

it('withholds evaluation and submission for a legacy draft while linking the pending application from Home', function (): void {
    $fixture = BusinessQuoteFixture::ready();
    $accepted = BusinessQuoteFixture::acceptance($fixture);
    BusinessQuoteFixture::submit($fixture, $accepted);
    $pendingId = $fixture['application']->id;
    $legacy = BusinessApplication::factory()->create(['business_id' => $fixture['audit']['business'], 'revision' => 1,
        'step' => 'review', 'draft' => BusinessApplicationFixture::fields('12000000'), 'mandate_version' => 1]);
    $prior = BusinessApplicationQuote::query()->where('business_application_id', $pendingId)->firstOrFail();
    $quoteId = (string) Str::ulid();
    $payload = [...$prior->payload, 'quote_id' => $quoteId, 'application_id' => $legacy->id, 'application_revision' => 1];
    BusinessApplicationQuote::factory()->create(['id' => $quoteId, 'business_application_id' => $legacy->id, 'revision' => 1,
        'payload' => $payload, 'sha256' => hash('sha256', app(CanonicalJson::class)->encode($payload))]);
    $legacy->forceFill(['current_quote_id' => $quoteId])->save();
    $fixture['application'] = $legacy;
    $accepted = [...$accepted, 'quote_id' => $quoteId, 'quote_revision' => 1];
    $owner = $fixture['audit']['authority']['users'][0];
    expect(BusinessQuoteFixture::evaluate($fixture, 1)['code'])->toBe('APPLICATION_PENDING_REVIEW')
        ->and(BusinessQuoteFixture::submit($fixture, $accepted)['code'])->toBe('APPLICATION_PENDING_REVIEW')
        ->and(app(SaveBusinessApplication::class)->handle($owner->id, 1, $fixture['audit']['business'], $legacy->id,
            1, BusinessApplicationFixture::fields('12000000'), 'review', (string) Str::uuid())['code'])->toBe('QUOTE_STALE')
        ->and(app(BusinessApplicationStore::class)->page($owner->id, 1, $fixture['audit']['business'], $legacy->id)['allowed_actions'])->toBe(['application.save']);
    $home = app(ListBusinessApplications::class)->handle($owner->id, 1);
    expect($home['entries'][0]['application']['id'])->toBe($pendingId)->and($home['entries'][0]['allowed_actions'])->toBe([])
        ->and($legacy->refresh()->revision)->toBe(1);
    $path = '/business/'.$fixture['audit']['business'].'/applications/'.$legacy->id;
    $pendingPath = '/business/'.$fixture['audit']['business'].'/applications/'.$pendingId;
    $this->actingAs($owner)->getJson('/api/v1'.$path)->assertOk()
        ->assertJsonPath('data.pending_application', ['id' => $pendingId, 'link' => ['url' => '/api/v1'.$pendingPath, 'method' => 'get']]);
    $this->get($path)->assertOk()->assertInertia(fn (Assert $page): Assert => $page
        ->where('pending_application', ['id' => $pendingId, 'link' => ['url' => $pendingPath, 'method' => 'get']]));
    $this->getJson('/api/v1'.$pendingPath)->assertOk()->assertJsonPath('data.pending_application', null);
    $this->assertDatabaseCount('business_application_quotes', 2);
    $this->assertDatabaseCount('business_application_signatures', 1);
    $this->assertDatabaseCount('business_application_submissions', 1);
});

it('revalidates Review fields after an omitted-step autosave and requote without changing the saved navigation pointer', function (string $missing): void {
    $fixture = BusinessQuoteFixture::ready();
    $owner = $fixture['audit']['authority']['users'][0];
    $fields = BusinessApplicationFixture::fields('12000000');
    if ($missing === 'title') {
        $fields['title'] = '  ';
    } else {
        $fields['use_of_funds'] = [];
    }
    $saved = app(SaveBusinessApplication::class)->handle($owner->id, 1, $fixture['audit']['business'], $fixture['application']->id,
        4, $fields, null, (string) Str::uuid());
    expect($saved['data']['application']['step'])->toBe('review')->and($saved['data']['quote'])->toBeNull();
    expect(BusinessQuoteFixture::evaluate($fixture, 5)['data']['quote']['status'])->toBe('ready');
    $page = app(BusinessApplicationStore::class)->page($owner->id, 1, $fixture['audit']['business'], $fixture['application']->id);
    expect($page['allowed_actions'])->toBe(['application.save', 'application.evaluate']);
    $accepted = BusinessQuoteFixture::acceptance($fixture);
    $request = (string) Str::uuid();
    $refused = BusinessQuoteFixture::submit($fixture, $accepted, revision: 6, request: $request);
    expect($refused['code'])->toBe('APPLICATION_INPUT_INVALID')->and($refused['http_status'])->toBe(422)
        ->and($refused['field_errors'])->toHaveKey($missing)->and($fixture['application']->refresh()->revision)->toBe(6);
    $this->assertDatabaseCount('business_application_signatures', 0);
    $this->assertDatabaseCount('business_application_submissions', 0);
    app(SaveBusinessApplication::class)->handle($owner->id, 1, $fixture['audit']['business'], $fixture['application']->id,
        6, BusinessApplicationFixture::fields('12000000'), null, (string) Str::uuid());
    BusinessQuoteFixture::evaluate($fixture, 7);
    expect(BusinessQuoteFixture::submit($fixture, BusinessQuoteFixture::acceptance($fixture))['code'])->toBe('APPLICATION_SUBMITTED')
        ->and(BusinessQuoteFixture::submit($fixture, $accepted, revision: 6, request: $request))->toBe($refused);
})->with(['title', 'use_of_funds']);

it('advertises signing only after Review and records a recoverable step error for a direct Raise submission', function (): void {
    $fixture = BusinessQuoteFixture::make();
    ConsentFixture::record($fixture['audit']['staff']);
    BusinessQuoteFixture::evaluate($fixture);
    $owner = $fixture['audit']['authority']['users'][0];
    $path = '/api/v1/business/'.$fixture['audit']['business'].'/applications/'.$fixture['application']->id;
    $this->actingAs($owner)->getJson($path)->assertOk()->assertJsonPath('data.step', 'raise')
        ->assertJsonPath('data.allowed_actions', ['application.save', 'application.evaluate']);
    $body = ['identity_context_revision' => 1, 'expected_revision' => 3, 'request_id' => (string) Str::uuid(), ...BusinessQuoteFixture::acceptance($fixture)];
    $refused = $this->postJson($path.'/submit', $body)->assertUnprocessable()->assertJsonPath('code', 'APPLICATION_STEP_INVALID')
        ->assertJsonPath('allowed_actions', [])->json();
    app(SaveBusinessApplication::class)->handle($owner->id, 1, $fixture['audit']['business'], $fixture['application']->id,
        3, BusinessApplicationFixture::fields('12000000'), 'review', (string) Str::uuid());
    $this->getJson($path)->assertOk()->assertJsonPath('data.allowed_actions', ['application.save', 'application.evaluate', 'application.submit']);
    $this->postJson($path.'/submit', $body)->assertUnprocessable()->assertExactJson($refused);
    $this->assertDatabaseCount('business_application_signatures', 0);
});

it('uses the Kigali month for underwriting while storing the actual evaluation instant in UTC', function (string $instant, string $lastMonth, string $repaymentMonth): void {
    $this->travelTo(CarbonImmutable::parse($instant));
    $fixture = BusinessQuoteFixture::make();
    $evaluated = BusinessQuoteFixture::evaluate($fixture);
    $quote = BusinessApplicationQuote::query()->firstOrFail();
    expect($evaluated['data']['quote']['status'])->toBe('ready')
        ->and($quote->payload['calendar'])->toBe(['last_complete_month' => $lastMonth, 'first_repayment_month' => $repaymentMonth])
        ->and($quote->payload['evaluated_at'])->toBe($instant);
    $page = app(BusinessApplicationStore::class)->page($fixture['audit']['authority']['users'][0]->id, 1,
        $fixture['audit']['business'], $fixture['application']->id);
    expect($page['evidence']['period']['through_month'])->toBe($lastMonth)->and($page['evidence']['eligibility']['status'])->toBe('eligible')
        ->and($page['review']['quote']['quote_id'])->toBe($quote->id);
})->with([
    ['2026-09-30T22:00:00Z', '2026-09', '2026-10'],
    ['2026-09-30T23:30:00Z', '2026-09', '2026-10'],
    ['2026-12-31T23:30:00Z', '2026-12', '2027-01'],
]);

it('expires the current quote at the Kigali month boundary while preserving its historical command receipt', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-09-30T21:59:00Z'));
    $fixture = BusinessQuoteFixture::make();
    $request = (string) Str::uuid();
    $receipt = BusinessQuoteFixture::evaluate($fixture, request: $request);
    expect(BusinessQuoteFixture::quote($fixture))->not->toBeNull();
    $this->travelTo(CarbonImmutable::parse('2026-09-30T22:00:00Z'));
    expect(BusinessQuoteFixture::quote($fixture))->toBeNull()
        ->and(BusinessQuoteFixture::evaluate($fixture, request: $request))->toBe($receipt);
});
