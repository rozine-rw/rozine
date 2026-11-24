<?php

declare(strict_types=1);

use App\Application\Pulse\RegisterSiteWaitlist;
use App\Enums\PulseContactMethod;
use App\Enums\PulseSignupType;
use App\Models\PulseSignup;

/**
 * What the landing page waitlist form posts.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function landingWaitlist(array $overrides = []): array
{
    return array_merge([
        'role' => 'investor',
        'name' => 'Diane Uwase',
        'contact' => '+250 788 123 456',
    ], $overrides);
}

test('an investor joins the waitlist with a name and a phone number', function () {
    $response = $this->postJson(route('site.waitlist.store'), landingWaitlist());

    $response->assertCreated()
        ->assertJsonPath('queue_number', '#0001')
        ->assertJsonPath('position', 1)
        ->assertJsonPath('referral_link', route('site.join', ['ref' => 'diane0001']));

    $signup = PulseSignup::query()->sole();

    expect($signup->type)->toBe(PulseSignupType::Investor)
        ->and($signup->name)->toBe('Diane Uwase')
        ->and($signup->contact_method)->toBe(PulseContactMethod::Phone)
        ->and($signup->contact)->toBe('+250788123456')
        ->and($signup->referral_code)->toBe('diane0001')
        ->and($signup->referred_by)->toBeNull()
        ->and($signup->pledge_amount)->toBeNull()
        ->and($signup->province)->toBeNull();
});

test('a business joins the waitlist with its name and an email', function () {
    $this->postJson(route('site.waitlist.store'), landingWaitlist([
        'role' => 'business',
        'name' => 'Kigali Coffee Roasters',
        'contact' => ' owner@kcr.rw ',
    ]))->assertCreated()->assertJsonPath('queue_number', '#0001');

    $signup = PulseSignup::query()->sole();

    expect($signup->type)->toBe(PulseSignupType::Business)
        ->and($signup->contact_method)->toBe(PulseContactMethod::Email)
        ->and($signup->contact)->toBe('owner@kcr.rw')
        ->and($signup->referral_code)->toBe('kigali0001b')
        ->and($signup->annual_revenue)->toBeNull();
});

test('investors and businesses each count their own places in the queue', function () {
    $this->postJson(route('site.waitlist.store'), landingWaitlist())->assertJsonPath('position', 1);
    $this->postJson(route('site.waitlist.store'), landingWaitlist(['contact' => 'b@example.com']))->assertJsonPath('position', 2);
    $this->postJson(route('site.waitlist.store'), landingWaitlist([
        'role' => 'business',
        'name' => 'Diane Stores',
        'contact' => 'c@example.com',
    ]))->assertJsonPath('position', 1);

    expect(PulseSignup::query()->pluck('referral_code')->unique())->toHaveCount(3);
});

test('a signup that arrived through a shared link keeps the code it came with', function () {
    $this->postJson(route('site.waitlist.store'), landingWaitlist());

    $this->postJson(route('site.waitlist.store'), landingWaitlist([
        'name' => 'Eric',
        'contact' => 'eric@example.com',
        'ref' => 'diane0001',
    ]))->assertCreated();

    expect(PulseSignup::query()->where('contact', 'eric@example.com')->sole()->referred_by)->toBe('diane0001');
});

test('a referral code that matches nobody is dropped rather than refused', function () {
    $this->postJson(route('site.waitlist.store'), landingWaitlist(['ref' => 'nobody42']))->assertCreated();

    expect(PulseSignup::query()->sole()->referred_by)->toBeNull();
});

test('a referral code with anything but letters and digits is refused', function () {
    $this->postJson(route('site.waitlist.store'), landingWaitlist(['ref' => '<script>']))
        ->assertJsonValidationErrors('ref');
});

test('the waitlist asks for a name in the words of the audience joining', function (string $role, string $message) {
    $this->postJson(route('site.waitlist.store'), landingWaitlist(['role' => $role, 'name' => ' ']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name' => $message]);
})->with([
    ['investor', 'Add your name.'],
    ['business', 'Add your business name.'],
]);

test('the waitlist needs a phone number or an email it can use', function (string $contact) {
    $this->postJson(route('site.waitlist.store'), landingWaitlist(['contact' => $contact]))
        ->assertJsonValidationErrors(['contact' => 'Add a valid phone number or email.']);

    expect(PulseSignup::query()->count())->toBe(0);
})->with(['', '12345', 'not an email', 'someone@', '+2507881234567890']);

test('only investors and businesses can join', function () {
    $this->postJson(route('site.waitlist.store'), landingWaitlist(['role' => 'auditor']))
        ->assertJsonValidationErrors('role');
});

test('the same phone number cannot join twice, however it is spaced', function () {
    $this->postJson(route('site.waitlist.store'), landingWaitlist())->assertCreated();

    $this->postJson(route('site.waitlist.store'), landingWaitlist(['contact' => '+250788123456']))
        ->assertJsonValidationErrors(['contact' => 'This phone number is already on the waitlist.']);
});

test('the same email cannot join twice', function () {
    $this->postJson(route('site.waitlist.store'), landingWaitlist(['contact' => 'diane@example.com']))->assertCreated();

    $this->postJson(route('site.waitlist.store'), landingWaitlist(['contact' => 'diane@example.com', 'role' => 'business']))
        ->assertJsonValidationErrors(['contact' => 'This email is already on the waitlist.']);
});

test('a shared link opens the landing page at the waitlist with its code', function () {
    $this->get(route('site.join', ['ref' => 'diane0001']))
        ->assertRedirect(route('home', ['ref' => 'diane0001']).'#join');
});

test('a shared link with a malformed code still opens the waitlist', function () {
    $this->get('/join?ref=%3Cx%3E')->assertRedirect(route('home').'#join');
});

test('a referral code starts with the first name in plain letters', function (string $name, string $stem) {
    expect(RegisterSiteWaitlist::referralStem($name))->toBe($stem);
})->with([
    ['Diane Uwase', 'diane'],
    ['  Émilie  Mukamana', 'emilie'],
    ["O'Neil & Sons", 'oneil'],
    ['!!!', 'rozine'],
    ['Abcdefghijklmnopqrstuvwxyz', 'abcdefghijklmnopqrstuvwx'],
]);

test('the waitlist is rate limited', function () {
    foreach (range(1, 10) as $attempt) {
        $this->postJson(route('site.waitlist.store'), landingWaitlist(['contact' => "person{$attempt}@example.com"]));
    }

    $this->postJson(route('site.waitlist.store'), landingWaitlist(['contact' => 'late@example.com']))
        ->assertTooManyRequests();
});
