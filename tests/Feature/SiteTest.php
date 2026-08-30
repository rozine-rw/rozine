<?php

use App\Domain\Pulse\PulseUnderwriting;
use App\Enums\PulseSignupType;
use App\Models\PulseSignup;
use Inertia\Testing\AssertableInertia;

/**
 * The details the marketing site collects from an investor, ready to post.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function siteInvestor(array $overrides = []): array
{
    return array_merge([
        'name' => 'Diane Uwase',
        'contact_method' => 'phone',
        'contact' => '250788123456',
        'country' => 'Rwanda',
        'pledge_amount' => 1_500_000,
    ], $overrides);
}

/**
 * The details the marketing site collects from a business, ready to post.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function siteBusiness(array $overrides = []): array
{
    return array_merge([
        'name' => 'Kigali Coffee Roasters',
        'contact_method' => 'phone',
        'contact' => '250722987654',
        'province' => 'Kigali City',
        'district' => 'Gasabo',
        'annual_revenue' => 80_000_000,
        'annual_costs' => 62_000_000,
        'term_months' => 6,
    ], $overrides);
}

test('the site is served at the root of the domain without authentication', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page->component('home'));
});

test('an investor reaching us through the site is recorded against their country', function () {
    $this->post(route('site.investor.store'), siteInvestor())->assertRedirect();

    $signup = PulseSignup::query()->sole();

    expect($signup->type)->toBe(PulseSignupType::Investor)
        ->and($signup->name)->toBe('Diane Uwase')
        ->and($signup->country)->toBe('Rwanda')
        ->and($signup->pledge_amount)->toBe(1_500_000)
        ->and($signup->queue_number)->toBe('#0001');
});

test('the site does not ask an investor for a Rwandan address', function () {
    $this->post(route('site.investor.store'), siteInvestor())->assertRedirect();

    $signup = PulseSignup::query()->sole();

    expect($signup->province)->toBeNull()
        ->and($signup->district)->toBeNull();
});

test('an investor pledge is stored with the return the platform projects for it', function () {
    $this->post(route('site.investor.store'), siteInvestor())->assertRedirect();

    $signup = PulseSignup::query()->sole();

    expect($signup->projected_return)->toBe(PulseUnderwriting::projectedReturn(1_500_000))
        ->and((float) $signup->blended_yield)->toBe(PulseUnderwriting::BLENDED_YIELD);
});

test('an investor must say where they live', function () {
    $this->postJson(route('site.investor.store'), siteInvestor(['country' => '']))
        ->assertJsonValidationErrors('country');
});

test('the site accepts the largest pledge the platform now writes', function () {
    $this->post(route('site.investor.store'), siteInvestor([
        'pledge_amount' => PulseUnderwriting::PLEDGE_MAXIMUM,
    ]))->assertRedirect();

    expect(PulseSignup::query()->sole()->pledge_amount)->toBe(PulseUnderwriting::PLEDGE_MAXIMUM);
});

test('a pledge beyond the largest the platform writes is turned away', function () {
    $this->postJson(route('site.investor.store'), siteInvestor([
        'pledge_amount' => PulseUnderwriting::PLEDGE_MAXIMUM + PulseUnderwriting::PLEDGE_STEP,
    ]))->assertJsonValidationErrors('pledge_amount');
});

test('the same person cannot join the waitlist twice from the site', function () {
    $this->post(route('site.investor.store'), siteInvestor())->assertRedirect();

    $this->postJson(route('site.investor.store'), siteInvestor())
        ->assertJsonValidationErrors('contact');

    expect(PulseSignup::query()->count())->toBe(1);
});

test('an investor who prefers email is reached that way', function () {
    $this->post(route('site.investor.store'), siteInvestor([
        'contact_method' => 'email',
        'contact' => 'Diane@Example.COM',
    ]))->assertRedirect();

    expect(PulseSignup::query()->sole()->contact)->toBe('diane@example.com');
});

test('an investor email has to look like an email', function () {
    $this->postJson(route('site.investor.store'), siteInvestor([
        'contact_method' => 'email',
        'contact' => 'not-an-email',
    ]))->assertJsonValidationErrors('contact');
});

test('a business that prefers email is reached that way', function () {
    $this->post(route('site.business.store'), siteBusiness([
        'contact_method' => 'email',
        'contact' => 'Owner@Kigalicoffee.RW',
    ]))->assertRedirect();

    expect(PulseSignup::query()->sole()->contact)->toBe('owner@kigalicoffee.rw');
});

test('a business email has to look like an email', function () {
    $this->postJson(route('site.business.store'), siteBusiness([
        'contact_method' => 'email',
        'contact' => 'owner@',
    ]))->assertJsonValidationErrors('contact');
});

test('a business reaching us through the site is recorded with the figures it reported', function () {
    $this->post(route('site.business.store'), siteBusiness())->assertRedirect();

    $signup = PulseSignup::query()->sole();

    expect($signup->type)->toBe(PulseSignupType::Business)
        ->and($signup->name)->toBe('Kigali Coffee Roasters')
        ->and($signup->province)->toBe('Kigali City')
        ->and($signup->district)->toBe('Gasabo')
        ->and($signup->annual_revenue)->toBe(80_000_000)
        ->and($signup->annual_costs)->toBe(62_000_000)
        ->and($signup->term_months)->toBe(6);
});

test('a business from the site carries no sizing until an accountant supplies one', function () {
    $this->post(route('site.business.store'), siteBusiness())->assertRedirect();

    $signup = PulseSignup::query()->sole();

    expect($signup->sector)->toBeNull()
        ->and($signup->registered_year)->toBeNull()
        ->and($signup->qualified_amount)->toBeNull();
});

test('a business whose costs swallow its sales cannot ask to borrow', function () {
    $this->postJson(route('site.business.store'), siteBusiness([
        'annual_costs' => 80_000_000,
    ]))->assertJsonValidationErrors('annual_costs');
});

test('a district has to belong to the province it was chosen under', function () {
    $this->postJson(route('site.business.store'), siteBusiness([
        'province' => 'Northern',
        'district' => 'Gasabo',
    ]))->assertJsonValidationErrors('district');
});

test('the site accepts every term the platform writes', function (int $term) {
    $this->post(route('site.business.store'), siteBusiness(['term_months' => $term]))
        ->assertRedirect();

    expect(PulseSignup::query()->sole()->term_months)->toBe($term);
})->with(PulseUnderwriting::TERMS);

test('a term the platform does not write is turned away', function () {
    $this->postJson(route('site.business.store'), siteBusiness(['term_months' => 7]))
        ->assertJsonValidationErrors('term_months');
});
