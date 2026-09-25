<?php

declare(strict_types=1);

use App\Application\Business\EvaluateBusinessApplication;
use App\Application\Business\FindBusinessOperation;
use App\Application\Business\GetBusinessApplicationQuote;
use App\Application\Business\RecordIsolatedBusinessCreditFacts;
use App\Application\Business\SaveBusinessApplication;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Domain\Underwriting\ApplicationUnderwriting;
use App\Models\BusinessApplicationQuote;
use App\Models\BusinessApplicationVersion;
use App\Models\BusinessCreditSnapshot;
use App\Models\RoleMembership;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Support\BusinessApplicationFixture;
use Tests\Support\BusinessAuthorityFixture;
use Tests\Support\BusinessCreditFactsFixture;
use Tests\Support\BusinessQuoteFixture;

it('publishes one immutable replayable quote with a minimal public offer and exact retry lookup', function (): void {
    $fixture = BusinessQuoteFixture::make();
    expect(BusinessQuoteFixture::quote($fixture))->toBeNull();
    $request = (string) Str::uuid();
    $result = BusinessQuoteFixture::evaluate($fixture, request: $request);
    expect($result['code'])->toBe('APPLICATION_EVALUATED')->and($result['revision'])->toBe(3)
        ->and($result['data']['quote']['status'])->toBe('ready')
        ->and($result['data']['quote']['principal']['amount'])->toBe('10800000')
        ->and($result['data']['quote']['offered_principal']['amount'])->toBe('10800000')
        ->and($result['data']['quote']['requested_principal']['amount'])->toBe('12000000')
        ->and($result['data']['quote']['rate_pct'])->toBe('11.1')
        ->and($result['data']['quote']['total']['amount'])->toBe('11998800')
        ->and($result['data']['quote']['schedule'])->toHaveCount(6)
        ->and($result['data']['quote']['schedule'][5]['instalment'])->toBe(6)
        ->and(BusinessQuoteFixture::evaluate($fixture, request: $request))->toBe($result)
        ->and(BusinessQuoteFixture::quote($fixture))->toEqual($result['data']['quote']);
    $quote = BusinessApplicationQuote::query()->firstOrFail();
    $payload = $quote->payload;
    expect(app(ApplicationUnderwriting::class)->evaluate($payload['result']['inputs']))->toBe($payload['result'])
        ->and($payload['application_revision'])->toBe(2)->and($payload['draft']['target'])->toBe('12000000')
        ->and($payload['credit']['sha256'])->toBe(BusinessCreditSnapshot::query()->firstOrFail()->sha256)
        ->and($quote->sha256)->toBe(hash('sha256', app(CanonicalJson::class)->encode($payload)))
        ->and($quote->getRawOriginal('payload'))->not->toContain('first-time-history', 'scorecard')
        ->and($quote->toArray())->not->toHaveKey('payload')
        ->and(json_encode($result['data']['quote'], JSON_THROW_ON_ERROR))->not->toContain('scorecard', 'cfads', 'history', 'credit_source', 'components');
    expect(app(FindBusinessOperation::class)->handle($fixture['audit']['authority']['users'][0]->id, 1, 'evaluate', $request))->toBe($result);
    expect(BusinessApplicationVersion::query()->where('business_application_id', $fixture['application']->id)->where('revision', 3)->firstOrFail()->snapshot['quote_id'] ?? null)->toBe($quote->id);
    $this->assertDatabaseCount('business_application_quotes', 1);
    expect(fn () => BusinessQuoteFixture::evaluate($fixture, accepted: '5000000', request: $request))->toThrow(CommandRejection::class, 'IDEMPOTENCY_CONFLICT');
});

it('keeps the original request and maximum offer when publishing a lower accepted amount', function (): void {
    $fixture = BusinessQuoteFixture::make();
    $first = BusinessQuoteFixture::evaluate($fixture);
    $lower = BusinessQuoteFixture::evaluate($fixture, 3, '5000000');
    expect($lower['revision'])->toBe(4)->and($lower['data']['quote']['quote_revision'])->toBe(2)
        ->and($lower['data']['quote']['principal']['amount'])->toBe('5000000')
        ->and($lower['data']['quote']['offered_principal']['amount'])->toBe('10800000')
        ->and($lower['data']['quote']['requested_principal']['amount'])->toBe('12000000')
        ->and($lower['data']['quote']['interest']['amount'])->toBe('555000')
        ->and($lower['data']['quote']['quote_id'])->not->toBe($first['data']['quote']['quote_id'])
        ->and(BusinessQuoteFixture::quote($fixture))->toEqual($lower['data']['quote']);
    expect(BusinessApplicationQuote::query()->where('revision', 1)->firstOrFail()->payload['result']['capacity']['offer']['principal']['amount'])->toBe('10800000');
    expect(BusinessApplicationQuote::query()->where('revision', 2)->firstOrFail()->payload['accepted_principal'])->toBe('5000000');
});

it('records an invalid accepted principal as a recoverable 422 without replacing the current quote', function (string $principal): void {
    $fixture = BusinessQuoteFixture::make();
    $first = BusinessQuoteFixture::evaluate($fixture);
    $request = (string) Str::uuid();
    $result = BusinessQuoteFixture::evaluate($fixture, 3, $principal, $request);
    expect($result['status'])->toBe('rejected')->and($result['http_status'])->toBe(422)
        ->and($result['field_errors'])->toHaveKey('accepted_principal')
        ->and(BusinessQuoteFixture::evaluate($fixture, 3, $principal, $request))->toBe($result)
        ->and(BusinessQuoteFixture::quote($fixture))->toEqual($first['data']['quote']);
    $this->assertDatabaseCount('business_application_quotes', 1);
})->with(['2995000', '5000001', '12000000', '5.0']);

it('records a refusal without a numeric offer when history is unavailable or restricted', function (string $case): void {
    $fixture = BusinessQuoteFixture::make(false);
    if ($case === 'restricted') {
        BusinessCreditFactsFixture::record($fixture['audit']['staff'], $fixture['audit']['business'],
            facts: [...BusinessCreditFactsFixture::facts(), 'restriction_active' => true]);
    }
    $result = BusinessQuoteFixture::evaluate($fixture);
    expect($result['code'])->toBe('APPLICATION_EVALUATED')->and($result['data']['quote']['status'])->toBe('refused')
        ->and($result['data']['quote']['code'])->toBe($case === 'restricted' ? 'RESTRICTION_ACTIVE' : 'POLICY_INPUT_REQUIRED')
        ->and(array_keys($result['data']['quote']))->toEqualCanonicalizing(['status', 'code', 'message'])
        ->and(BusinessQuoteFixture::quote($fixture))->toEqual($result['data']['quote']);
})->with(['unavailable', 'restricted']);

it('requires an evaluated saved request and records missing-evidence refusal', function (): void {
    $fixture = BusinessApplicationFixture::make();
    $user = $fixture['authority']['users'][0];
    $evaluate = fn (int $revision): array => app(EvaluateBusinessApplication::class)->handle($user->id, 1, $fixture['business']->id, $fixture['application']->id, $revision, null, (string) Str::uuid());
    expect($evaluate(1)['code'])->toBe('APPLICATION_INPUT_INVALID');
    $this->assertDatabaseCount('business_application_quotes', 0);
    BusinessApplicationFixture::save($fixture);
    $result = app(EvaluateBusinessApplication::class)->handle($user->id, 1, $fixture['business']->id, $fixture['application']->id, 2, '5000000', (string) Str::uuid());
    expect($result['data']['quote']['code'])->toBe('UNDERWRITING_EVIDENCE_REQUIRED')
        ->and($result['data']['quote'])->not->toHaveKey('principal')
        ->and(BusinessApplicationQuote::query()->firstOrFail()->payload['accepted_principal'])->toBe('5000000');
});

it('invalidates current offers after source mandate draft or calendar changes while preserving historical receipts', function (string $change): void {
    $fixture = BusinessQuoteFixture::make();
    $request = (string) Str::uuid();
    $first = BusinessQuoteFixture::evaluate($fixture, request: $request);
    $quote = BusinessApplicationQuote::query()->firstOrFail();
    $original = $quote->payload;
    $owner = $fixture['audit']['authority']['users'][0];
    switch ($change) {
        case 'credit':
            BusinessCreditFactsFixture::record($fixture['audit']['staff'], $fixture['audit']['business'], 1,
                facts: [...BusinessCreditFactsFixture::facts(), 'restriction_active' => true]);
            break;
        case 'withdrawal':
            app(RecordIsolatedBusinessCreditFacts::class)->handle($fixture['audit']['staff']->id, $fixture['audit']['business'], 1, null,
                'synthetic:withdrawn', 'Withdraw inaccurate history.', (string) Str::uuid());
            break;
        case 'mandate':
            BusinessAuthorityFixture::configure($fixture['audit']['authority'], 1);
            break;
        case 'draft':
            app(SaveBusinessApplication::class)->handle($owner->id, 1, $fixture['audit']['business'], $fixture['application']->id, 3,
                BusinessApplicationFixture::fields('5000000'), 'raise', (string) Str::uuid());
            break;
        case 'calendar':
            $this->travelTo(now('UTC')->toImmutable()->startOfMonth()->addMonth());
            break;
        case 'auditor':
            $fixture['audit']['partners'][0]['party']->forceFill(['verified_at' => null])->save();
            break;
    }
    expect(BusinessQuoteFixture::quote($fixture))->toBeNull()->and($quote->refresh()->payload)->toBe($original)
        ->and(BusinessQuoteFixture::evaluate($fixture, request: $request))->toBe($first);
})->with(['credit', 'withdrawal', 'mandate', 'draft', 'calendar', 'auditor']);

it('advances to Review only with the same current ready quote and complete draft', function (): void {
    $fixture = BusinessQuoteFixture::make();
    $user = $fixture['audit']['authority']['users'][0];
    $save = fn (int $revision, string $target = '12000000'): array => app(SaveBusinessApplication::class)->handle($user->id, 1,
        $fixture['audit']['business'], $fixture['application']->id, $revision, BusinessApplicationFixture::fields($target), 'review', (string) Str::uuid());
    expect($save(2)['code'])->toBe('QUOTE_STALE');
    $quote = BusinessQuoteFixture::evaluate($fixture)['data']['quote'];
    expect($save(3, '5000000')['code'])->toBe('QUOTE_STALE');
    $result = $save(3);
    expect($result['code'])->toBe('APPLICATION_SAVED')->and($result['data']['application']['step'])->toBe('review')
        ->and($result['revision'])->toBe(4)->and(BusinessQuoteFixture::quote($fixture))->toEqual($quote);
    BusinessCreditFactsFixture::record($fixture['audit']['staff'], $fixture['audit']['business'], 1,
        facts: [...BusinessCreditFactsFixture::facts(), 'restriction_active' => true]);
    expect($save(4)['code'])->toBe('QUOTE_STALE');
});

it('retains current authority and scope on quote reads evaluations and historical lookup', function (): void {
    $fixture = BusinessQuoteFixture::make();
    $owner = $fixture['audit']['authority']['users'][0];
    $other = BusinessApplicationFixture::make();
    $request = (string) Str::uuid();
    BusinessQuoteFixture::evaluate($fixture, request: $request);
    $reader = app(GetBusinessApplicationQuote::class);
    expect(fn () => $reader->handle($other['authority']['users'][0]->id, 1, $fixture['audit']['business'], $fixture['application']->id))->toThrow(CommandRejection::class, 'BUSINESS_NOT_FOUND')
        ->and(fn () => $reader->handle($owner->id, 1, $fixture['audit']['business'], $other['application']->id))->toThrow(CommandRejection::class, 'APPLICATION_NOT_FOUND')
        ->and(fn () => $reader->handle($owner->id, 0, $fixture['audit']['business'], $fixture['application']->id))->toThrow(IdentityViolation::class, 'ACTIVE_ROLE_REVISION_CONFLICT');
    RoleMembership::query()->where('party_id', $owner->party_id)->where('role', 'business')->update(['status' => 'revoked']);
    expect(fn () => BusinessQuoteFixture::quote($fixture))->toThrow(IdentityViolation::class, 'ROLE_MEMBERSHIP_REQUIRED')
        ->and(fn () => BusinessQuoteFixture::evaluate($fixture, request: $request))->toThrow(IdentityViolation::class, 'ROLE_MEMBERSHIP_REQUIRED')
        ->and(fn () => app(FindBusinessOperation::class)->handle($owner->id, 1, 'evaluate', $request))->toThrow(IdentityViolation::class, 'ROLE_MEMBERSHIP_REQUIRED');
});

it('protects quote originals and application ownership in PostgreSQL', function (): void {
    $fixture = BusinessQuoteFixture::make();
    BusinessQuoteFixture::evaluate($fixture);
    $quote = BusinessApplicationQuote::query()->firstOrFail();
    foreach (['update', 'delete'] as $mutation) {
        expect(fn () => DB::transaction(fn () => $mutation === 'update'
            ? DB::table('business_application_quotes')->where('id', $quote->id)->update(['sha256' => str_repeat('0', 64)])
            : DB::table('business_application_quotes')->where('id', $quote->id)->delete()))->toThrow(QueryException::class, 'Business application quotes are immutable');
    }
    $other = BusinessApplicationFixture::make();
    expect(fn () => DB::transaction(fn () => $other['application']->forceFill(['current_quote_id' => $quote->id])->save()))
        ->toThrow(QueryException::class, 'application_current_quote_owner');
});

it('rejects corrupted or misbound quote payloads before presenting them', function (string $fault): void {
    $fixture = BusinessQuoteFixture::make();
    BusinessQuoteFixture::evaluate($fixture);
    $event = 'eloquent.retrieved: '.BusinessApplicationQuote::class;
    Event::listen($event, function (BusinessApplicationQuote $quote) use ($fault): void {
        if ($fault === 'digest') {
            $quote->forceFill(['sha256' => str_repeat('0', 64)]);
        } else {
            $payload = [...$quote->payload, 'quote_id' => 'another-quote'];
            $quote->forceFill(['payload' => $payload, 'sha256' => hash('sha256', app(CanonicalJson::class)->encode($payload))]);
        }
    });
    try {
        expect(fn () => BusinessQuoteFixture::quote($fixture))->toThrow(RuntimeException::class, 'APPLICATION_QUOTE_INTEGRITY_FAILED');
    } finally {
        Event::forget($event);
    }
})->with(['digest', 'binding']);

it('allows incomplete descriptive fields in a quote but requires them for Review', function (): void {
    $fixture = BusinessQuoteFixture::make();
    $user = $fixture['audit']['authority']['users'][0];
    $fields = [...BusinessApplicationFixture::fields('12000000'), 'title' => '', 'use_of_funds' => []];
    $action = app(SaveBusinessApplication::class);
    expect($action->handle($user->id, 1, $fixture['audit']['business'], $fixture['application']->id, 2, $fields, 'raise', (string) Str::uuid())['code'])->toBe('APPLICATION_SAVED');
    expect(BusinessQuoteFixture::evaluate($fixture, 3)['data']['quote']['status'])->toBe('ready');
    $result = $action->handle($user->id, 1, $fixture['audit']['business'], $fixture['application']->id, 4, $fields, 'review', (string) Str::uuid());
    expect($result['http_status'])->toBe(422)->and($result['field_errors'])->toHaveKeys(['title', 'use_of_funds'])
        ->and($fixture['application']->refresh()->step)->toBe('raise')->and($fixture['application']->revision)->toBe(4);
});

it('preserves affordability and exposure refusals through the quote projection', function (string $case): void {
    $fixture = BusinessQuoteFixture::make();
    if ($case === 'affordability') {
        app(SaveBusinessApplication::class)->handle($fixture['audit']['authority']['users'][0]->id, 1, $fixture['audit']['business'], $fixture['application']->id, 2,
            BusinessApplicationFixture::fields('20000000'), 'raise', (string) Str::uuid());
    } else {
        $schedule = [];
        $first = now('UTC')->toImmutable()->startOfMonth();
        for ($index = 0; $index < 6; $index++) {
            $schedule[$first->addMonths($index)->format('Y-m')] = '0';
        }
        BusinessCreditFactsFixture::record($fixture['audit']['staff'], $fixture['audit']['business'], 1,
            facts: [...BusinessCreditFactsFixture::facts(), 'obligations' => [['id' => 'platform:existing', 'principal' => '15000000', 'service_by_month' => $schedule]]]);
    }
    $result = BusinessQuoteFixture::evaluate($fixture, $case === 'affordability' ? 3 : 2, $case === 'affordability' ? '3000000' : null);
    expect($result['data']['quote']['status'])->toBe('refused')
        ->and($result['data']['quote']['code'])->toBe($case === 'affordability' ? 'DSCR_BELOW_CUTOFF' : 'CAPACITY_BELOW_MINIMUM')
        ->and($result['data']['quote'])->not->toHaveKey('principal');
})->with(['affordability', 'exposure']);

it('rolls back a failed publication together with its quote pointer version and journal outcome', function (): void {
    $fixture = BusinessQuoteFixture::make();
    $event = 'eloquent.created: '.BusinessApplicationQuote::class;
    Event::listen($event, function (): never {
        throw new RuntimeException('Synthetic publication failure.');
    });
    try {
        expect(fn () => BusinessQuoteFixture::evaluate($fixture))->toThrow(RuntimeException::class, 'Synthetic publication failure.');
    } finally {
        Event::forget($event);
    }
    $this->assertDatabaseCount('business_application_quotes', 0);
    $this->assertDatabaseCount('business_application_versions', 2);
    $this->assertDatabaseMissing('command_operations', ['command' => 'application.evaluate']);
    expect($fixture['application']->refresh()->revision)->toBe(2)->and($fixture['application']->current_quote_id)->toBeNull();
});

it('never treats a synthetic historical factory quote as current borrowing authority', function (): void {
    $fixture = BusinessApplicationFixture::make();
    $quote = BusinessApplicationQuote::factory()->create(['business_application_id' => $fixture['application']->id]);
    $fixture['application']->forceFill(['current_quote_id' => $quote->id])->save();
    expect(app(GetBusinessApplicationQuote::class)->handle($fixture['authority']['users'][0]->id, 1, $fixture['business']->id, $fixture['application']->id))->toBeNull();
});
