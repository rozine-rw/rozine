<?php

declare(strict_types=1);

use App\Models\BusinessContactVersion;
use App\Models\CommandOperation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\BusinessAuthorityFixture as AuthorityFixture;

/*
 * The Profile's "Company information" (Business.dc.html L1726–1743): a mandate holder with
 * `business.profile.update` keeps the contact details and registered address current, each save an
 * immutable version.
 */

beforeEach(function (): void {
    $this->withoutVite();
    $this->contact = fn (array $fields = []): array => ['request_id' => (string) Str::uuid(), 'email' => 'Hello@Synthetic.rw', 'phone' => '0788123456',
        'province' => 'Northern', 'district' => 'Musanze', 'sector' => 'Muhoza', 'cell' => 'Mpenge', 'street' => 'KN 4 Ave', ...$fields];
    $this->business = function (bool $secondOnlyViews = false): array {
        $authority = AuthorityFixture::make('organization', 2, requiredSignatories: 1);
        if ($secondOnlyViews) {
            $viewer = $authority['people'][1]->id;
            $authority['terms']['people'] = array_map(fn (array $person): array => $person['party_id'] === $viewer
                ? [...$person, 'permissions' => ['business.view']] : $person, $authority['terms']['people']);
        }

        return [$authority, AuthorityFixture::configure($authority)['data']['business']['id']];
    };
});

it('saves the company information as the next version and shows it on Profile', function (): void {
    [$authority, $business] = ($this->business)();
    $user = $authority['users'][0];

    $this->actingAs($user)->put(route('business.profile.company', ['business' => $business]), ($this->contact)())
        ->assertRedirect(route('business.profile', ['business' => $business, 'section' => 'company']))->assertSessionHasNoErrors();
    $this->put(route('business.profile.company', ['business' => $business]), ($this->contact)(['street' => '', 'phone' => '0722000111']))
        ->assertSessionHasNoErrors();

    $versions = BusinessContactVersion::query()->where('business_id', $business)->orderBy('version')->get();
    expect($versions->pluck('version')->all())->toBe([1, 2])
        ->and($versions[0]->payload)->toBe(['email' => 'hello@synthetic.rw', 'phone' => '0788123456', 'province' => 'Northern', 'district' => 'Musanze',
            'sector' => 'Muhoza', 'cell' => 'Mpenge', 'street' => 'KN 4 Ave'])
        ->and($versions[0]->actor_party_id)->toBe($authority['people'][0]->id)
        ->and($versions[0]->sha256)->toHaveLength(64)
        ->and(CommandOperation::query()->where('command', 'business.profile.update')->count())->toBe(2)
        ->and(json_encode(CommandOperation::query()->where('command', 'business.profile.update')->first()?->getAttributes()))->not->toContain('0788123456');

    $this->get(route('business.profile', ['business' => $business]))->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->component('business/profile')
            ->where('company.email', 'hello@synthetic.rw')->where('company.phone', '0722000111')
            ->where('company.address', ['province' => 'Northern', 'district' => 'Musanze', 'sector' => 'Muhoza', 'cell' => 'Mpenge', 'street' => ''])
            ->where('company.certificate', null));

    expect(fn () => BusinessContactVersion::query()->whereKey($versions[0]->id)->update(['sha256' => str_repeat('0', 64)]))
        ->toThrow(QueryException::class);
});

it('answers a repeated request once and refuses the same request id with other details', function (): void {
    [$authority, $business] = ($this->business)();
    $request = ($this->contact)();

    $this->actingAs($authority['users'][0])->put(route('business.profile.company', ['business' => $business]), $request)->assertSessionHasNoErrors();
    $this->put(route('business.profile.company', ['business' => $business]), $request)->assertSessionHasNoErrors();
    $this->put(route('business.profile.company', ['business' => $business]), [...$request, 'phone' => '0788999999'])
        ->assertSessionHasErrors(['email' => 'This save was already sent with different details. Try again.']);

    expect(BusinessContactVersion::query()->where('business_id', $business)->count())->toBe(1);
});

it('refuses details that do not describe a reachable Rwandan business address', function (): void {
    [$authority, $business] = ($this->business)();
    $this->actingAs($authority['users'][0]);

    $this->put(route('business.profile.company', ['business' => $business]), ($this->contact)(['email' => 'not-an-email', 'phone' => '0888123456',
        'province' => 'Northern', 'district' => 'Gasabo', 'sector' => '', 'cell' => str_repeat('c', 81), 'street' => str_repeat('s', 161)]))
        ->assertSessionHasErrors(['email' => 'Enter a valid business e-mail address.', 'phone' => 'Enter a ten-digit phone number starting with 07.',
            'district' => 'Choose a district in the selected province.', 'sector' => 'Enter the sector name.', 'cell' => 'Enter the cell name.',
            'street' => 'Keep the street to 160 characters.']);
    $this->put(route('business.profile.company', ['business' => $business]), ['email' => 'a@b.rw'])->assertSessionHasErrors(['request_id', 'phone']);

    expect(BusinessContactVersion::query()->count())->toBe(0);
});

it('offers and accepts the save only to a holder of business.profile.update', function (): void {
    [$authority, $business] = ($this->business)(true);
    $viewer = $authority['users'][1];

    $this->actingAs($viewer)->get(route('business.profile', ['business' => $business]))->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->where('actions.save_company', null)->where('company.email', ''));
    $this->put(route('business.profile.company', ['business' => $business]), ($this->contact)())
        ->assertSessionHasErrors(['email' => 'You do not have permission to change the company information.']);
    $this->actingAs(AuthorityFixture::make()['users'][0])->put(route('business.profile.company', ['business' => $business]), ($this->contact)())
        ->assertSessionHasErrors(['email']);

    expect(BusinessContactVersion::query()->count())->toBe(0);
});
