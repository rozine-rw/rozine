<?php

declare(strict_types=1);

use App\Models\BusinessCampaign;
use App\Models\BusinessVisitReadyTick;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\PrimaryIssuanceFixture;

/*
 * BRS v3 VIS-R03: the Business ticks visit-ready checklist items as ready. Each tick is saved,
 * once per request id, only for an item of the checklist open today; the latest tick of an item
 * is its state, and saved ticks are never edited or removed.
 */

beforeEach(function (): void {
    $this->freezeSecond();
    $this->withoutVite();
});

/** @return array{business: string, user: User, due_on: string} */
function visitReadyOpen(object $test): array
{
    ['campaign' => $campaign, 'instruction' => $instruction] = PrimaryIssuanceFixture::close(effectiveAt: now()->toIso8601String());
    $user = User::query()->findOrFail(BusinessCampaign::query()->findOrFail($campaign->campaignId)->actor_user_id);
    $test->travelTo(CarbonImmutable::parse($instruction->dueDates[0], 'Africa/Kigali')->subDays(14)->setTime(9, 0));

    return ['business' => $campaign->businessId, 'user' => $user, 'due_on' => $instruction->dueDates[0]];
}

it('saves each tick and untick, and the sheet shows the latest state of each item', function (): void {
    ['business' => $business, 'user' => $user, 'due_on' => $due] = visitReadyOpen($this);
    $tick = fn (string $item, bool $ticked, ?string $request = null) => $this->actingAs($user)->post(route('business.repayments.visit-ready.tick', ['business' => $business]),
        ['request_id' => $request ?? (string) Str::uuid(), 'due_on' => $due, 'item' => $item, 'ticked' => $ticked]);

    $tick('bank_statements', true)->assertRedirect(route('business.repayments.show', ['business' => $business]));
    $tick('rssb_total', true)->assertRedirect();
    $request = (string) Str::uuid();
    $tick('bank_statements', false, $request)->assertRedirect();
    // The same request again is the same save, not a second row.
    $tick('bank_statements', false, $request)->assertRedirect();

    $props = $this->actingAs($user)->get(route('business.repayments.show', ['business' => $business]))->assertOk()->viewData('page')['props'];
    expect($props['visit_ready']['ticked'])->toBe(['rssb_total'])
        ->and($props['visit_ready']['tick'])->toBe(['url' => route('business.repayments.visit-ready.tick', ['business' => $business], false), 'method' => 'post'])
        ->and(BusinessVisitReadyTick::query()->where('business_id', $business)->count())->toBe(3);

    $row = BusinessVisitReadyTick::query()->where('business_id', $business)->firstOrFail();
    expect(fn () => DB::transaction(fn () => DB::table('business_visit_ready_ticks')->where('id', $row->id)->update(['ticked' => false])))->toThrow(QueryException::class, 'immutable')
        ->and(fn () => DB::transaction(fn () => DB::table('business_visit_ready_ticks')->where('id', $row->id)->delete()))->toThrow(QueryException::class, 'immutable');
});

it('refuses an item that is not on the checklist, or a checklist that is not open', function (): void {
    ['business' => $business, 'user' => $user, 'due_on' => $due] = visitReadyOpen($this);
    $tick = fn (string $dueOn, string $item) => $this->actingAs($user)->from(route('business.repayments.show', ['business' => $business]))
        ->post(route('business.repayments.visit-ready.tick', ['business' => $business]), ['request_id' => (string) Str::uuid(), 'due_on' => $dueOn, 'item' => $item, 'ticked' => true]);

    $tick($due, 'passport')->assertSessionHasErrors(['item' => 'Choose an item on this month’s checklist.']);
    $tick(CarbonImmutable::parse($due)->addMonth()->toDateString(), 'bank_statements')
        ->assertSessionHasErrors(['item' => 'This checklist is no longer open. Refresh to see the current one.']);
    $this->actingAs($user)->post(route('business.repayments.visit-ready.tick', ['business' => $business]), ['due_on' => $due, 'item' => 'bank_statements', 'ticked' => true])
        ->assertSessionHasErrors('request_id');

    expect(BusinessVisitReadyTick::query()->count())->toBe(0);
});

it('refuses a request id reused for a different tick', function (): void {
    ['business' => $business, 'user' => $user, 'due_on' => $due] = visitReadyOpen($this);
    $request = (string) Str::uuid();
    $post = fn (bool $ticked) => $this->actingAs($user)->post(route('business.repayments.visit-ready.tick', ['business' => $business]),
        ['request_id' => $request, 'due_on' => $due, 'item' => 'bank_statements', 'ticked' => $ticked]);

    $post(true)->assertRedirect(route('business.repayments.show', ['business' => $business]));
    $post(false)->assertSessionHasErrors('item');

    expect(BusinessVisitReadyTick::query()->count())->toBe(1);
});

it('refuses a user who holds no authority over the Business', function (): void {
    ['business' => $business, 'due_on' => $due] = visitReadyOpen($this);
    $stranger = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($stranger)->post(route('business.repayments.visit-ready.tick', ['business' => $business]),
        ['request_id' => (string) Str::uuid(), 'due_on' => $due, 'item' => 'bank_statements', 'ticked' => true])->assertStatus(403);

    expect(BusinessVisitReadyTick::query()->count())->toBe(0);
});
