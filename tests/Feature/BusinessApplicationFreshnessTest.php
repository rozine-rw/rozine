<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessApplicationStore;
use App\Application\Business\SaveBusinessApplication;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Domain\Underwriting\UnderwritingObservationWindow;
use App\Models\BusinessApplicationQuote;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Support\BusinessApplicationFixture;
use Tests\Support\BusinessCreditFactsFixture;
use Tests\Support\BusinessQuoteFixture;
use Tests\Support\ConsentFixture;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-09-25T03:00:00Z'));
});

it('recalculates and signs from fresh audited evidence at the inclusive 45-day boundary', function (bool $repeat, int $months): void {
    $fixture = BusinessQuoteFixture::make(credit: false, historyMonths: $months);
    BusinessCreditFactsFixture::record($fixture['audit']['staff'], $fixture['audit']['business'], facts: $repeat ? BusinessCreditFactsFixture::repeat() : BusinessCreditFactsFixture::facts());
    ConsentFixture::record($fixture['audit']['staff']);
    $this->travelTo(CarbonImmutable::parse('2026-10-15T21:59:59Z'));
    $result = BusinessQuoteFixture::evaluate($fixture);
    expect($result['data']['quote']['status'])->toBe('ready');
    $quote = BusinessApplicationQuote::query()->firstOrFail();
    expect($quote->payload['calendar'])->toBe(['last_complete_month' => '2026-08', 'first_repayment_month' => '2026-10'])
        ->and($quote->payload['evidence_window'])->toBe(['version' => UnderwritingObservationWindow::VERSION, 'valid_through' => '2026-10-15', 'fresh' => true])
        ->and($quote->payload['result']['inputs']['months'])->toHaveCount($months)
        ->and($quote->sha256)->toBe(hash('sha256', app(CanonicalJson::class)->encode($quote->payload)));
    $owner = $fixture['audit']['authority']['users'][0];
    app(SaveBusinessApplication::class)->handle($owner->id, 1, $fixture['audit']['business'], $fixture['application']->id,
        3, BusinessApplicationFixture::fields('12000000'), 'review', (string) Str::uuid());
    expect(BusinessQuoteFixture::submit($fixture, BusinessQuoteFixture::acceptance($fixture))['code'])->toBe('APPLICATION_SUBMITTED');
    $this->travelTo(CarbonImmutable::parse('2026-10-15T22:00:00Z'));
    expect(BusinessQuoteFixture::review($fixture)['application']['status'])->toBe('submitted')
        ->and(BusinessQuoteFixture::quote($fixture)['quote_id'])->toBe($quote->id);
})->with([[false, 36], [true, 12]]);

it('withdraws signing authority at the 46th Kigali day without changing the historical ready quote', function (): void {
    $fixture = BusinessQuoteFixture::make();
    ConsentFixture::record($fixture['audit']['staff']);
    $owner = $fixture['audit']['authority']['users'][0];
    $this->travelTo(CarbonImmutable::parse('2026-10-15T21:59:59Z'));
    $request = (string) Str::uuid();
    $receipt = BusinessQuoteFixture::evaluate($fixture, request: $request);
    app(SaveBusinessApplication::class)->handle($owner->id, 1, $fixture['audit']['business'], $fixture['application']->id,
        3, BusinessApplicationFixture::fields('12000000'), 'review', (string) Str::uuid());
    $accepted = BusinessQuoteFixture::acceptance($fixture);
    $before = BusinessApplicationQuote::query()->firstOrFail()->getRawOriginal();
    $this->travelTo(CarbonImmutable::parse('2026-10-15T22:00:00Z'));
    expect(BusinessQuoteFixture::quote($fixture))->toBeNull()
        ->and(BusinessQuoteFixture::submit($fixture, $accepted)['code'])->toBe('QUOTE_STALE')
        ->and(BusinessQuoteFixture::evaluate($fixture, request: $request))->toBe($receipt)
        ->and(BusinessApplicationQuote::query()->firstOrFail()->getRawOriginal())->toBe($before);
    $page = app(BusinessApplicationStore::class)->page($owner->id, 1, $fixture['audit']['business'], $fixture['application']->id);
    expect($page['allowed_actions'])->toBe(['application.save', 'application.evaluate'])
        ->and($page['evidence']['period']['through_month'])->toBe('2026-08')
        ->and($page['evidence']['totals']['revenue']['amount'])->toBe('144000000')
        ->and($page['evidence']['eligibility']['code'])->toBe('UNDERWRITING_EVIDENCE_REQUIRED');
    $refused = BusinessQuoteFixture::evaluate($fixture, 4);
    expect($refused['data']['quote']['status'])->toBe('refused')->and($refused['data']['quote']['code'])->toBe('UNDERWRITING_EVIDENCE_REQUIRED')
        ->and(BusinessQuoteFixture::quote($fixture))->toEqual($refused['data']['quote']);
    $this->assertDatabaseCount('business_application_signatures', 0);
    $this->assertDatabaseCount('business_application_submissions', 0);
});

it('requires a fresh quote for the new repayment month while still accepting the prior complete evidence window', function (): void {
    $fixture = BusinessQuoteFixture::make();
    $old = BusinessQuoteFixture::evaluate($fixture);
    $this->travelTo(CarbonImmutable::parse('2026-09-30T22:00:00Z'));
    expect(BusinessQuoteFixture::quote($fixture))->toBeNull();
    $new = BusinessQuoteFixture::evaluate($fixture, 3);
    expect($new['data']['quote']['status'])->toBe('ready')->and($new['data']['quote']['quote_id'])->not->toBe($old['data']['quote']['quote_id']);
    $payload = BusinessApplicationQuote::query()->whereKey($new['data']['quote']['quote_id'])->firstOrFail()->payload;
    expect($payload['calendar'])->toBe(['last_complete_month' => '2026-08', 'first_repayment_month' => '2026-10'])
        ->and($payload['result']['inputs']['months'])->toHaveCount(36);
});

it('selects the latest qualified repeat window from the retained audited baseline', function (): void {
    $fixture = BusinessQuoteFixture::make(credit: false);
    BusinessCreditFactsFixture::record($fixture['audit']['staff'], $fixture['audit']['business'], facts: BusinessCreditFactsFixture::repeat());
    $evaluated = BusinessQuoteFixture::evaluate($fixture);
    expect($evaluated['data']['quote']['status'])->toBe('ready');
    $quote = BusinessApplicationQuote::query()->firstOrFail();
    expect($quote->payload['result']['inputs']['months'])->toHaveCount(12)
        ->and($quote->payload['result']['inputs']['months'][0]['month'])->toBe('2025-09');
    $page = app(BusinessApplicationStore::class)->page($fixture['audit']['authority']['users'][0]->id, 1,
        $fixture['audit']['business'], $fixture['application']->id);
    expect($page['evidence']['period'])->toBe(['from_month' => '2025-09', 'through_month' => '2026-08', 'months' => 12])
        ->and($page['evidence']['totals']['revenue']['amount'])->toBe('48000000');
});

it('requires requoting a pre-freshness-policy quote while preserving the original receipt', function (): void {
    $fixture = BusinessQuoteFixture::make();
    $request = (string) Str::uuid();
    $receipt = BusinessQuoteFixture::evaluate($fixture, request: $request);
    $event = 'eloquent.retrieved: '.BusinessApplicationQuote::class;
    Event::listen($event, function (BusinessApplicationQuote $quote): void {
        $payload = $quote->payload;
        unset($payload['evidence_window']);
        $quote->payload = $payload;
        $quote->sha256 = hash('sha256', app(CanonicalJson::class)->encode($payload));
    });
    try {
        expect(BusinessQuoteFixture::quote($fixture))->toBeNull()
            ->and(BusinessQuoteFixture::evaluate($fixture, request: $request))->toBe($receipt);
    } finally {
        Event::forget($event);
    }
});
