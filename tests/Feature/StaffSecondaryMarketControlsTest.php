<?php

declare(strict_types=1);

use App\Application\Business\Contracts\CampaignTitles;
use App\Infrastructure\Secondary\EloquentSecondaryMarket;
use App\Models\PrimaryHolding;
use App\Models\SecondaryHalt;
use App\Models\SecondaryListingRecord;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\PrimaryHoldingFixture;
use Tests\Support\PrimaryIssuanceFixture;
use Tests\Support\StaffFixture;

/*
 * Compliance's resale controls on the Secondary Market page (BRS v3.0 SEC-R08, SEC-R09, #816):
 * halts in force and open listings, and the halt, lift and cancel commands with a written reason.
 * One note is issued and its seller has one open listing of 10 units at RWF 2,700.
 */
beforeEach(function (): void {
    $this->withoutVite();
    config(['app.secondary_trading' => true]);
    Carbon::setTestNow(CarbonImmutable::parse('2026-10-10 10:00:00', 'Africa/Kigali'));
    PrimaryIssuanceFixture::close('2026-10-09T22:30:00+00:00');
    $holding = PrimaryHolding::query()->orderBy('id')->firstOrFail();
    $this->noteId = $holding->business_campaign_id;
    $seller = User::query()->where('party_id', $holding->party_id)->sole();
    $this->listing = app(EloquentSecondaryMarket::class)->list($seller->id, 1, $holding->id, '10', '2700', (string) Str::uuid())['data']['listing'];
    PrimaryHoldingFixture::flushDeferredChecks();
    $this->staff = function (array $roles): User {
        $user = User::factory()->withTwoFactor()->create();
        StaffFixture::provision($user, $roles, 'Market surveillance duty.');

        return $user;
    };
    $this->compliance = ($this->staff)(['compliance']);
    $this->command = fn (string $route, array $input, array $parameters = []) => $this->actingAs($this->compliance)
        ->from('/admin/secondary-market')->post(route($route, $parameters), ['request_id' => (string) Str::uuid(), ...$input]);
});

it('shows Compliance the open listings with a halt and a cancel each, and nobody else any controls', function (): void {
    $title = app(CampaignTitles::class)->title(strtoupper($this->noteId));

    $this->actingAs($this->compliance)->get('/admin/secondary-market')->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/section')->where('section', 'secondary_market')
            ->where('market_oversight.trading', true)->where('market_oversight.market_halted', false)
            ->where('market_oversight.halt', ['url' => '/admin/secondary-market/halt', 'method' => 'post'])
            ->where('market_oversight.halts', [])
            ->where('market_oversight.listings.0', ['listing_id' => $this->listing['listing_id'], 'note_id' => $this->noteId, 'deal_name' => $title,
                'remaining_units' => '10', 'price' => ['currency' => 'RWF', 'amount' => '2700'], 'listed_at' => $this->listing['listed_at'],
                'expires_at' => $this->listing['expires_at'], 'halt_note' => ['url' => '/admin/secondary-market/halt', 'method' => 'post'],
                'cancel' => ['url' => '/admin/secondary-market/listings/'.$this->listing['listing_id'].'/cancel', 'method' => 'post']]));
    $this->actingAs(($this->staff)(['analyst']))->get('/admin/secondary-market')->assertOk()
        ->assertInertia(fn (Assert $page) => $page->missing('market_oversight'));
});

it('halts one note, shows the halt in force without a second halt of it, then lifts it (SEC-R08)', function (): void {
    ($this->command)('staff.secondary.halt', ['note_id' => strtoupper($this->noteId), 'reason' => 'Business under review.'])
        ->assertRedirect('/admin/secondary-market')->assertSessionHasNoErrors();
    PrimaryHoldingFixture::flushDeferredChecks();

    expect(SecondaryHalt::query()->sole())->business_campaign_id->toBe($this->noteId)->reason->toBe('Business under review.')
        ->and(SecondaryListingRecord::query()->sole()->cancelled_by)->toBe('halt');

    $title = app(CampaignTitles::class)->title(strtoupper($this->noteId));
    $this->actingAs($this->compliance)->get('/admin/secondary-market')->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('market_oversight.halts.0.note_id', $this->noteId)
            ->where('market_oversight.halts.0.deal_name', $title)->where('market_oversight.halts.0.reason', 'Business under review.')
            ->where('market_oversight.halts.0.lift', ['url' => '/admin/secondary-market/lift', 'method' => 'post'])
            ->where('market_oversight.listings', [])->where('market_oversight.market_halted', false));

    ($this->command)('staff.secondary.halt', ['note_id' => $this->noteId, 'reason' => 'Again.'])
        ->assertSessionHasErrors(['reason' => 'This note is already halted. Refresh the page.']);
    ($this->command)('staff.secondary.lift', ['note_id' => $this->noteId, 'reason' => 'Review closed.'])->assertSessionHasNoErrors();

    expect(SecondaryHalt::query()->sole()->lift)->not->toBeNull();
});

it('halts the whole market, offers no second market halt, and refuses lifting a halt that is not there', function (): void {
    ($this->command)('staff.secondary.lift', ['reason' => 'Nothing to lift.'])
        ->assertSessionHasErrors(['reason' => 'The whole market is not halted. Refresh the page.']);
    ($this->command)('staff.secondary.halt', ['note_id' => '', 'reason' => 'Suspected wash trading.'])->assertSessionHasNoErrors();
    PrimaryHoldingFixture::flushDeferredChecks();

    $this->actingAs($this->compliance)->get('/admin/secondary-market')->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('market_oversight.market_halted', true)->where('market_oversight.halt', null)
            ->where('market_oversight.halts.0.note_id', null)->where('market_oversight.halts.0.deal_name', null));
});

it('cancels one listing with the reason shown to the seller, and refuses one that has ended (SEC-R09)', function (): void {
    $cancel = fn (string $reason) => ($this->command)('staff.secondary.listings.cancel', ['reason' => $reason], ['listing' => $this->listing['listing_id']]);

    $cancel('Price misleads buyers.')->assertRedirect('/admin/secondary-market')->assertSessionHasNoErrors();
    PrimaryHoldingFixture::flushDeferredChecks();

    expect(SecondaryListingRecord::query()->sole())->cancelled_by->toBe('staff')->cancel_reason->toBe('Price misleads buyers.');

    $cancel('Again.')->assertSessionHasErrors(['reason' => 'This listing has already ended. Refresh the page.']);
    ($this->command)('staff.secondary.listings.cancel', ['reason' => 'Gone.'], ['listing' => strtolower((string) Str::ulid())])
        ->assertSessionHasErrors(['reason' => 'This listing no longer exists. Refresh the page.']);
});

it('needs a reason and a request id, and says so when resale trading is switched off', function (): void {
    ($this->command)('staff.secondary.halt', ['reason' => ''])->assertSessionHasErrors(['reason']);
    $this->actingAs($this->compliance)->post(route('staff.secondary.halt'), ['reason' => 'No id.'])->assertSessionHasErrors(['request_id']);

    config(['app.secondary_trading' => false]);

    ($this->command)('staff.secondary.halt', ['reason' => 'Suspected wash trading.'])
        ->assertSessionHasErrors(['reason' => 'Resale trading is switched off, so there is nothing to halt, lift or cancel.']);
    expect(SecondaryHalt::query()->count())->toBe(0);
});

it('refuses a halt from staff without the compliance permission', function (): void {
    $this->actingAs(($this->staff)(['analyst']))->post(route('staff.secondary.halt'), ['request_id' => (string) Str::uuid(), 'reason' => 'Not mine.'])
        ->assertForbidden();

    expect(SecondaryHalt::query()->count())->toBe(0);
});
