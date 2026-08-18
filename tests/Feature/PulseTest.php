<?php

use App\Enums\PulseContactMethod;
use App\Enums\PulseSector;
use App\Enums\PulseSignupType;
use App\Models\PulseSignup;
use App\Support\PulseUnderwriting;
use Inertia\Testing\AssertableInertia;

/**
 * The details and the five figures a business pre-qualifies on, ready to post.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function businessSignup(array $overrides = []): array
{
    return array_merge([
        'name' => 'GreenLeaf Agro',
        'contact_method' => 'phone',
        'contact' => '0788 123 456',
        'province' => 'Northern',
        'district' => 'Musanze',
        'annual_revenue' => 120_000_000,
        'annual_costs' => 90_000_000,
        'sector' => 'Logistics',
        'registered_year' => 2018,
        'term_months' => 12,
    ], $overrides);
}

test('the home route shows the pulse waitlist without authentication', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('pulse')
        ->has('districts.Kigali City')
        ->where('traction.pledged', 0)
        ->where('traction.investors', 0)
        ->where('traction.businesses', 0)
    );
});

test('traction reported on the waitlist grows with the signups recorded', function () {
    PulseSignup::factory()->investor()->create(['pledge_amount' => 250_000]);
    PulseSignup::factory()->business()->create(['qualified_amount' => 90_000_000]);

    $response = $this->get(route('home'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('traction.pledged', 250_000)
        ->where('traction.investors', 1)
        ->where('traction.businesses', 1)
        ->where('traction.average_loan', 90_000_000)
    );
});

test('the businesses an investor can back are the ones that pre-qualified', function () {
    PulseSignup::factory()->business()->create([
        'name' => 'Kivu Coffee Co.',
        'district' => 'Karongi',
        'listed' => true,
        'term_months' => 12,
        'flat_rate' => 14.1,
        'rating_band' => 'Stable',
        'rating_score' => 3.4,
    ]);
    PulseSignup::factory()->investor()->create();

    $response = $this->get(route('home'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('listings', 1)
        ->where('listings.0.name', 'Kivu Coffee Co.')
        ->where('listings.0.initial', 'K')
        ->where('listings.0.term', '12mo')
        ->where('listings.0.yield', '14.1%')
        ->where('listings.0.rating_band', 'Stable')
        ->where('listings.0.rating_score', '3.4')
    );
});

test('a business that did not consent is listed without its name', function () {
    PulseSignup::factory()->business()->create([
        'name' => 'Kivu Coffee Co.',
        'district' => 'Karongi',
        'listed' => false,
    ]);

    $response = $this->get(route('home'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('listings.0.name', 'Business in Karongi')
        ->where('listings.0.initial', 'K')
    );
});

test('the newest businesses are listed first and the list is capped', function () {
    PulseSignup::factory()->business()->count(16)->sequence(fn ($sequence) => [
        'name' => "Business {$sequence->index}",
        'listed' => true,
        'created_at' => now()->subDays(20 - $sequence->index),
    ])->create();

    $response = $this->get(route('home'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('listings', 14)
        ->where('listings.0.name', 'Business 15')
        ->where('listings.13.name', 'Business 2')
    );
});

test('traction averages come from the businesses that have been sized', function () {
    PulseSignup::factory()->business()->create([
        'qualified_amount' => 80_000_000,
        'flat_rate' => 13.0,
        'rating_score' => 3.0,
        'term_months' => 6,
    ]);
    PulseSignup::factory()->business()->create([
        'qualified_amount' => 100_000_000,
        'flat_rate' => 14.0,
        'rating_score' => 4.0,
        'term_months' => 12,
    ]);

    $response = $this->get(route('home'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('traction.average_loan', 90_000_000)
        ->where('traction.average_yield', 13.5)
        ->where('traction.average_rating', 3.5)
        ->where('traction.average_term', 9)
    );
});

test('a business that sized under the smallest loan is not offered to investors', function () {
    PulseSignup::factory()->waitlisted()->create(['listed' => true]);

    $response = $this->get(route('home'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('listings', 0)
        ->where('traction.businesses', 1)
        ->where('traction.average_loan', null)
    );
});

test('there is no average to report until a business has been sized', function () {
    $response = $this->get(route('home'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('traction.average_loan', null)
        ->where('traction.average_yield', null)
        ->where('traction.average_rating', null)
        ->where('traction.average_term', null)
    );
});

test('a business records whether it consented to being listed', function () {
    $this->postJson(route('pulse.business.store'), businessSignup(['listed' => true]))->assertOk();

    expect(PulseSignup::query()->sole()->listed)->toBeTrue();
});

test('a business is not listed unless it says so', function () {
    $this->postJson(route('pulse.business.store'), businessSignup())->assertOk();

    expect(PulseSignup::query()->sole()->listed)->toBeFalse();
});

test('an investor can pledge', function () {
    $response = $this->postJson(route('pulse.investor.store'), [
        'name' => 'Diane Uwase',
        'contact_method' => 'phone',
        'contact' => '0788 123 456',
        'province' => 'Kigali City',
        'district' => 'Gasabo',
        'pledge_amount' => 500_000,
    ]);

    $response->assertOk();
    $response->assertJsonPath('queue_number', '#0001');
    $response->assertJsonPath('traction.pledged', 500_000);

    $signup = PulseSignup::query()->sole();

    expect($signup->type)->toBe(PulseSignupType::Investor)
        ->and($signup->contact_method)->toBe(PulseContactMethod::Phone)
        ->and($signup->pledge_amount)->toBe(500_000)
        ->and($signup->projected_return)->toBe(565_000)
        ->and($signup->blended_yield)->toBe(13.0);
});

test('an email already on the waitlist is turned away', function () {
    PulseSignup::factory()->investor()->create(['contact' => 'diane@example.com']);

    $response = $this->postJson(route('pulse.investor.store'), [
        'name' => 'Diane Uwase',
        'contact_method' => 'email',
        'contact' => 'diane@example.com',
        'province' => 'Kigali City',
        'district' => 'Gasabo',
        'pledge_amount' => 500_000,
    ]);

    $response->assertStatus(422);
    $response->assertJsonPath('errors.contact.0', 'This email is already in the waitlist.');

    expect(PulseSignup::query()->count())->toBe(1);
});

test('a phone number already on the waitlist is turned away', function () {
    PulseSignup::factory()->investor()->create([
        'contact_method' => PulseContactMethod::Phone,
        'contact' => '0788123456',
    ]);

    $response = $this->postJson(route('pulse.investor.store'), [
        'name' => 'Diane Uwase',
        'contact_method' => 'phone',
        'contact' => '0788123456',
        'province' => 'Kigali City',
        'district' => 'Gasabo',
        'pledge_amount' => 500_000,
    ]);

    $response->assertStatus(422);
    $response->assertJsonPath('errors.contact.0', 'This phone number is already in the waitlist.');
});

test('a contact is matched however it is punctuated or cased', function () {
    PulseSignup::factory()->investor()->create([
        'contact_method' => PulseContactMethod::Phone,
        'contact' => '0788123456',
    ]);
    PulseSignup::factory()->investor()->create(['contact' => 'diane@example.com']);

    $phone = $this->postJson(route('pulse.investor.store'), [
        'name' => 'Diane Uwase',
        'contact_method' => 'phone',
        'contact' => '0788 123 456',
        'province' => 'Kigali City',
        'district' => 'Gasabo',
        'pledge_amount' => 500_000,
    ]);

    $email = $this->postJson(route('pulse.investor.store'), [
        'name' => 'Diane Uwase',
        'contact_method' => 'email',
        'contact' => '  Diane@Example.com ',
        'province' => 'Kigali City',
        'district' => 'Gasabo',
        'pledge_amount' => 500_000,
    ]);

    $phone->assertJsonValidationErrors('contact');
    $email->assertJsonValidationErrors('contact');

    expect(PulseSignup::query()->count())->toBe(2);
});

test('a contact already pledging cannot also pre-qualify a business', function () {
    PulseSignup::factory()->investor()->create([
        'contact_method' => PulseContactMethod::Phone,
        'contact' => '0788123456',
    ]);

    $response = $this->postJson(route('pulse.business.store'), businessSignup([
        'contact' => '0788123456',
    ]));

    $response->assertStatus(422);
    $response->assertJsonPath('errors.contact.0', 'This phone number is already in the waitlist.');
});

test('a number given in international form folds onto its local form', function () {
    $this->postJson(route('pulse.investor.store'), [
        'name' => 'Diane Uwase',
        'contact_method' => 'phone',
        'contact' => '+250 788 123 456',
        'province' => 'Kigali City',
        'district' => 'Gasabo',
        'pledge_amount' => 500_000,
    ])->assertOk();

    expect(PulseSignup::query()->sole()->contact)->toBe('0788123456');

    $this->postJson(route('pulse.investor.store'), [
        'name' => 'Someone Else',
        'contact_method' => 'phone',
        'contact' => '0788123456',
        'province' => 'Kigali City',
        'district' => 'Gasabo',
        'pledge_amount' => 500_000,
    ])->assertJsonValidationErrors('contact');
});

test('a subscriber number given without its leading zero gains one', function () {
    $this->postJson(route('pulse.investor.store'), [
        'name' => 'Diane Uwase',
        'contact_method' => 'phone',
        'contact' => '788 123 456',
        'province' => 'Kigali City',
        'district' => 'Gasabo',
        'pledge_amount' => 500_000,
    ])->assertOk();

    expect(PulseSignup::query()->sole()->contact)->toBe('0788123456');
});

test('a pledge with no contact at all is turned away', function () {
    $response = $this->postJson(route('pulse.investor.store'), [
        'name' => 'Diane Uwase',
        'contact_method' => 'phone',
        'contact' => '   ',
        'province' => 'Kigali City',
        'district' => 'Gasabo',
        'pledge_amount' => 500_000,
    ]);

    $response->assertJsonValidationErrors('contact');

    expect(PulseSignup::query()->count())->toBe(0);
});

test('a number that is not Rwandan is left as the digits it was given as', function () {
    $this->postJson(route('pulse.investor.store'), [
        'name' => 'Diane Uwase',
        'contact_method' => 'phone',
        'contact' => '+254 712 345 678',
        'province' => 'Kigali City',
        'district' => 'Gasabo',
        'pledge_amount' => 500_000,
    ])->assertOk();

    expect(PulseSignup::query()->sole()->contact)->toBe('254712345678');
});

test('a pledge is rejected when the details do not hold up', function () {
    $response = $this->postJson(route('pulse.investor.store'), [
        'name' => 'D',
        'contact_method' => 'email',
        'contact' => 'not-an-email',
        'province' => 'Kigali City',
        'district' => 'Musanze',
        'pledge_amount' => 1_000,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name', 'contact', 'district', 'pledge_amount']);

    expect(PulseSignup::query()->count())->toBe(0);
});

test('a phone number needs at least nine digits', function () {
    $response = $this->postJson(route('pulse.investor.store'), [
        'name' => 'Diane Uwase',
        'contact_method' => 'phone',
        'contact' => '078 812',
        'province' => 'Kigali City',
        'district' => 'Gasabo',
        'pledge_amount' => 500_000,
    ]);

    $response->assertJsonValidationErrors('contact');
});

test('a business claims a pass on the figures it reported', function () {
    $this->travelTo(now()->setDate(2026, 7, 29));

    $response = $this->postJson(route('pulse.business.store'), businessSignup());

    $response->assertOk();
    $response->assertJsonPath('queue_number', '#0001');
    $response->assertJsonPath('loan_number', '#1');

    $signup = PulseSignup::query()->sole();

    expect($signup->type)->toBe(PulseSignupType::Business)
        ->and($signup->annual_revenue)->toBe(120_000_000)
        ->and($signup->annual_costs)->toBe(90_000_000)
        ->and($signup->sector)->toBe(PulseSector::Logistics)
        ->and($signup->registered_year)->toBe(2018)
        ->and($signup->score)->toBe(77.5)
        ->and($signup->term_months)->toBe(12)
        ->and($signup->qualified_amount)->toBe(20_900_000)
        ->and($signup->flat_rate)->toBe(14.41)
        ->and($signup->rating_band)->toBe('Stable')
        ->and($signup->rating_score)->toBe(3.9);
});

test('a business is sized on the server, whatever the browser claims', function () {
    $this->postJson(route('pulse.business.store'), businessSignup([
        'qualified_amount' => 900_000_000,
        'score' => 92,
        'flat_rate' => 10,
        'rating_band' => 'Strong',
    ]))->assertOk();

    $signup = PulseSignup::query()->sole();

    expect($signup->qualified_amount)->toBe(
        PulseUnderwriting::qualifiedAmount(120_000_000, 90_000_000, $signup->score, 12)
    )->and($signup->rating_band)->toBe('Stable');
});

test('a business whose costs swallow its revenue cannot pre-qualify', function () {
    $response = $this->postJson(route('pulse.business.store'), businessSignup([
        'annual_revenue' => 90_000_000,
        'annual_costs' => 90_000_000,
    ]));

    $response->assertStatus(422);
    $response->assertJsonPath(
        'errors.annual_costs.0',
        'Your costs have to be lower than your revenue to pre-qualify.'
    );

    expect(PulseSignup::query()->count())->toBe(0);
});

test('a business too small to size is turned away', function () {
    $response = $this->postJson(route('pulse.business.store'), businessSignup([
        'annual_revenue' => 14_000_000,
        'annual_costs' => 4_000_000,
    ]));

    $response->assertJsonValidationErrors('annual_revenue');
});

test('a business sizing under the smallest loan still takes a place in the queue', function () {
    $response = $this->postJson(route('pulse.business.store'), businessSignup([
        'annual_revenue' => 16_000_000,
        'annual_costs' => 15_400_000,
        'term_months' => 3,
    ]));

    $response->assertOk();

    expect(PulseSignup::query()->sole()->qualified_amount)
        ->toBeLessThan(PulseUnderwriting::MIN_LOAN);
});

test('a pledge above the largest loan Rozine writes is turned away', function () {
    $response = $this->postJson(route('pulse.investor.store'), [
        'name' => 'Diane Uwase',
        'contact_method' => 'phone',
        'contact' => '0788 123 456',
        'province' => 'Kigali City',
        'district' => 'Gasabo',
        'pledge_amount' => PulseUnderwriting::MAX_LOAN + 5_000,
    ]);

    $response->assertJsonValidationErrors('pledge_amount');
});

test('figures a business could not have reported are rejected', function (array $overrides, string $field) {
    $this->postJson(route('pulse.business.store'), businessSignup($overrides))
        ->assertJsonValidationErrors($field);
})->with([
    'an unknown sector' => [['sector' => 'Cryptocurrency'], 'sector'],
    'a year before the register' => [['registered_year' => 1995], 'registered_year'],
    'a year still to come' => [['registered_year' => 2099], 'registered_year'],
    'a term nobody offers' => [['term_months' => 7], 'term_months'],
    'costs of nothing at all' => [['annual_costs' => 0], 'annual_costs'],
]);

test('queue numbers follow the signups already taken for that side', function () {
    PulseSignup::factory()->investor()->count(3)->create();

    $response = $this->postJson(route('pulse.investor.store'), [
        'name' => 'Diane Uwase',
        'contact_method' => 'email',
        'contact' => 'diane@example.com',
        'province' => 'Kigali City',
        'district' => 'Gasabo',
        'pledge_amount' => 500_000,
    ]);

    $response->assertJsonPath('queue_number', '#0004');
});
