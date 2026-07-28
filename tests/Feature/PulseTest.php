<?php

use App\Enums\PulseContactMethod;
use App\Enums\PulseSignupType;
use App\Models\PulseSignup;
use App\Support\PulseUnderwriting;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

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
    PulseSignup::factory()->business()->count(14)->sequence(fn ($sequence) => [
        'name' => "Business {$sequence->index}",
        'listed' => true,
        'created_at' => now()->subDays(20 - $sequence->index),
    ])->create();

    $response = $this->get(route('home'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('listings', 12)
        ->where('listings.0.name', 'Business 13')
        ->where('listings.11.name', 'Business 2')
    );
});

test('traction averages come from the businesses that have been sized', function () {
    PulseSignup::factory()->business()->create(['qualified_amount' => 80_000_000, 'flat_rate' => 13.0, 'rating_score' => 3.0]);
    PulseSignup::factory()->business()->create(['qualified_amount' => 100_000_000, 'flat_rate' => 14.0, 'rating_score' => 4.0]);

    $response = $this->get(route('home'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('traction.average_loan', 90_000_000)
        ->where('traction.average_yield', 13.5)
        ->where('traction.average_rating', 3.5)
    );
});

test('there is no average to report until a business has been sized', function () {
    $response = $this->get(route('home'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('traction.average_loan', null)
        ->where('traction.average_yield', null)
        ->where('traction.average_rating', null)
    );
});

test('a business records whether it consented to being listed', function () {
    Storage::fake('local');

    $upload = $this->post(route('pulse.statement.store'), [
        'statement' => UploadedFile::fake()->create('momo-statement.pdf', 120, 'application/pdf'),
    ]);

    $this->postJson(route('pulse.business.store'), [
        'name' => 'GreenLeaf Agro',
        'contact_method' => 'phone',
        'contact' => '0788123456',
        'province' => 'Northern',
        'district' => 'Musanze',
        'statement_path' => $upload->json('statement_path'),
        'term_months' => 12,
        'listed' => true,
    ])->assertOk();

    expect(PulseSignup::query()->sole()->listed)->toBeTrue();
});

test('a business is not listed unless it says so', function () {
    Storage::fake('local');

    $upload = $this->post(route('pulse.statement.store'), [
        'statement' => UploadedFile::fake()->create('momo-statement.pdf', 120, 'application/pdf'),
    ]);

    $this->postJson(route('pulse.business.store'), [
        'name' => 'GreenLeaf Agro',
        'contact_method' => 'phone',
        'contact' => '0788123456',
        'province' => 'Northern',
        'district' => 'Musanze',
        'statement_path' => $upload->json('statement_path'),
        'term_months' => 12,
    ])->assertOk();

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
        ->and($signup->projected_return)->toBe(562_500)
        ->and($signup->blended_yield)->toBe(12.5);
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
    Storage::fake('local');

    PulseSignup::factory()->investor()->create([
        'contact_method' => PulseContactMethod::Phone,
        'contact' => '0788123456',
    ]);

    $upload = $this->post(route('pulse.statement.store'), [
        'statement' => UploadedFile::fake()->create('momo-statement.pdf', 120, 'application/pdf'),
    ]);

    $response = $this->postJson(route('pulse.business.store'), [
        'name' => 'GreenLeaf Agro',
        'contact_method' => 'phone',
        'contact' => '0788123456',
        'province' => 'Northern',
        'district' => 'Musanze',
        'statement_path' => $upload->json('statement_path'),
        'term_months' => 12,
    ]);

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

test('a statement is stored and sized', function () {
    Storage::fake('local');

    $response = $this->post(route('pulse.statement.store'), [
        'statement' => UploadedFile::fake()->create('momo-statement.pdf', 120, 'application/pdf'),
    ]);

    $response->assertOk();

    $path = $response->json('statement_path');

    expect($path)->toStartWith('pulse-statements/')
        ->and($response->json('annual_inflow'))->toBeGreaterThanOrEqual(48_000_000)
        ->and($response->json('annual_inflow'))->toBeLessThanOrEqual(127_000_000);

    Storage::disk('local')->assertExists($path);
    $response->assertSessionHas('pulse.statements');
});

test('a statement that cannot be written is reported back', function () {
    $disk = Mockery::mock(Filesystem::class);
    $disk->shouldReceive('putFile')->once()->andReturnFalse();
    Storage::shouldReceive('disk')->with('local')->andReturn($disk);

    $response = $this->postJson(route('pulse.statement.store'), [
        'statement' => UploadedFile::fake()->create('momo-statement.pdf', 120, 'application/pdf'),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('statement');
});

test('an executable dressed as a statement is rejected', function () {
    Storage::fake('local');

    $response = $this->postJson(route('pulse.statement.store'), [
        'statement' => UploadedFile::fake()->create('payload.php', 10, 'application/x-php'),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('statement');
});

test('a business can claim a pass against a statement it uploaded', function () {
    Storage::fake('local');

    $upload = $this->post(route('pulse.statement.store'), [
        'statement' => UploadedFile::fake()->create('momo-statement.pdf', 120, 'application/pdf'),
    ]);

    $path = $upload->json('statement_path');
    $inflow = $upload->json('annual_inflow');

    $response = $this->postJson(route('pulse.business.store'), [
        'name' => 'GreenLeaf Agro',
        'contact_method' => 'phone',
        'contact' => '0788 123 456',
        'province' => 'Northern',
        'district' => 'Musanze',
        'statement_path' => $path,
        'term_months' => 12,
    ]);

    $response->assertOk();
    $response->assertJsonPath('queue_number', '#0001');
    $response->assertJsonPath('loan_number', '#1');

    $signup = PulseSignup::query()->sole();

    expect($signup->type)->toBe(PulseSignupType::Business)
        ->and($signup->statement_path)->toBe($path)
        ->and($signup->annual_inflow)->toBe($inflow)
        ->and($signup->term_months)->toBe(12)
        ->and($signup->qualified_amount)->toBe(PulseUnderwriting::qualifiedAmount($inflow, 12))
        ->and($signup->flat_rate)->toBe(PulseUnderwriting::flatRate(12))
        ->and($signup->rating_band)->toBe('Stable')
        ->and($signup->rating_score)->toBe(3.4);
});

test('a business cannot claim a pass against a statement it never uploaded', function () {
    $response = $this->postJson(route('pulse.business.store'), [
        'name' => 'GreenLeaf Agro',
        'contact_method' => 'phone',
        'contact' => '0788 123 456',
        'province' => 'Northern',
        'district' => 'Musanze',
        'statement_path' => 'pulse-statements/somebody-elses-file.pdf',
        'term_months' => 12,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('statement_path');

    expect(PulseSignup::query()->count())->toBe(0);
});

test('a business term outside the offered set is rejected', function () {
    Storage::fake('local');

    $upload = $this->post(route('pulse.statement.store'), [
        'statement' => UploadedFile::fake()->create('momo-statement.pdf', 120, 'application/pdf'),
    ]);

    $response = $this->postJson(route('pulse.business.store'), [
        'name' => 'GreenLeaf Agro',
        'contact_method' => 'phone',
        'contact' => '0788 123 456',
        'province' => 'Northern',
        'district' => 'Musanze',
        'statement_path' => $upload->json('statement_path'),
        'term_months' => 7,
    ]);

    $response->assertJsonValidationErrors('term_months');
});

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

test('the underwriting model sizes capacity from cash flow', function () {
    expect(PulseUnderwriting::flatRate(3))->toBe(12.6)
        ->and(PulseUnderwriting::flatRate(12))->toBe(14.1)
        ->and(PulseUnderwriting::rating())->toBe(['band' => 'Stable', 'score' => 3.4])
        ->and(PulseUnderwriting::projectedReturn(500_000))->toBe(562_500)
        ->and(PulseUnderwriting::qualifiedAmount(100_000_000, 12))->toBe(
            (int) round((2.75 * 100_000_000 * 12) / (24 * 1.141))
        );
});
