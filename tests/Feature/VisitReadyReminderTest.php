<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessNoticeAudience;
use App\Application\Business\Contracts\HeldVisitDocuments;
use App\Application\Business\RemindVisitReady;
use App\Domain\Auditor\VisitReadyChecklist;
use App\Models\BusinessCampaign;
use App\Models\PartyNotice;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Tests\Support\PrimaryIssuanceFixture;

/*
 * BRS v3 VIS-R03, AUD-R04: once per Business and instalment, an in-app notice at D−14 that the
 * visit-ready checklist is open, and at D−11 a reminder naming how many items are still not ticked,
 * sent only when some are. Both open the Repayments page, where the checklist is.
 */

beforeEach(function (): void {
    $this->freezeSecond();
    $this->withoutVite();
});

/** @return array{business: string, user: User, due_on: string, at: Closure(int): CarbonImmutable} */
function visitReadyIssued(): array
{
    ['campaign' => $campaign, 'instruction' => $instruction] = PrimaryIssuanceFixture::close(effectiveAt: now()->toIso8601String());
    $due = $instruction->dueDates[0];

    return ['business' => $campaign->businessId, 'due_on' => $due,
        'user' => User::query()->findOrFail(BusinessCampaign::query()->findOrFail($campaign->campaignId)->actor_user_id),
        'at' => fn (int $daysBefore): CarbonImmutable => CarbonImmutable::parse($due, 'Africa/Kigali')->subDays($daysBefore)->setTime(9, 0)];
}

it('opens the checklist at D−14 once, then reminds at D−11 of the items not ticked', function (): void {
    ['business' => $business, 'user' => $user, 'due_on' => $due, 'at' => $at] = visitReadyIssued();
    // Certificates the CPA already holds are not counted.
    $total = count((new VisitReadyChecklist)->items(app(HeldVisitDocuments::class)->held($business, VisitReadyChecklist::visitBy($due))));
    expect($total)->toBe(count((new VisitReadyChecklist)->items()) - 1);

    $this->travelTo($at(15));
    expect(Artisan::call('business:remind-visit-ready'))->toBe(0)
        ->and(PartyNotice::query()->where('kind', 'like', 'business.visit_ready%')->count())->toBe(0);

    $this->travelTo($at(14));
    expect(Artisan::call('business:remind-visit-ready'))->toBe(0)->and(Artisan::output())->toContain('Opened 1 visit-ready checklists and sent 0 reminders.');
    $open = PartyNotice::query()->where('kind', 'business.visit_ready_open')->sole();
    expect($open->facts)->toEqual(['business_id' => $business, 'due_on' => $due, 'items' => (string) $total]);
    $this->actingAs($user)->getJson(route('business.notices.index', $business))->assertOk()
        ->assertJsonPath('data.notices.0.kind', 'business.visit_ready_open')
        ->assertJsonPath('data.notices.0.link', ['url' => "/business/{$business}/repayments", 'method' => 'get']);

    // A later hourly run, or a catch-up on D−12, records nothing new.
    $this->travelTo($at(12));
    Artisan::call('business:remind-visit-ready');
    expect(PartyNotice::query()->where('kind', 'business.visit_ready_open')->count())->toBe(1);

    $this->actingAs($user)->post(route('business.repayments.visit-ready.tick', ['business' => $business]),
        ['request_id' => (string) Str::uuid(), 'due_on' => $due, 'item' => 'bank_statements', 'ticked' => true])->assertRedirect();

    $this->travelTo($at(11));
    expect(app(RemindVisitReady::class)->handle())->toBe(['opened' => 0, 'reminded' => 1])
        ->and(app(RemindVisitReady::class)->handle())->toBe(['opened' => 0, 'reminded' => 1]);
    $reminder = PartyNotice::query()->where('kind', 'business.visit_ready_reminder')->sole();
    expect($reminder->facts)->toEqual(['business_id' => $business, 'due_on' => $due, 'open' => (string) ($total - 1), 'items' => (string) $total]);
    $this->actingAs($user)->getJson(route('business.notices.index', $business))->assertOk()
        ->assertJsonPath('data.notices.0.kind', 'business.visit_ready_reminder')
        ->assertJsonPath('data.notices.0.link', ['url' => "/business/{$business}/repayments", 'method' => 'get']);

    // From D−9 the CPA's window is already open, so nothing more is sent for this instalment.
    $this->travelTo($at(9));
    expect(app(RemindVisitReady::class)->handle())->toBe(['opened' => 0, 'reminded' => 0]);
});

it('sends no reminder when every item is ticked', function (): void {
    ['business' => $business, 'user' => $user, 'due_on' => $due, 'at' => $at] = visitReadyIssued();
    $this->travelTo($at(14));
    foreach ((new VisitReadyChecklist)->items(['tax_clearance']) as ['key' => $item]) {
        $this->actingAs($user)->post(route('business.repayments.visit-ready.tick', ['business' => $business]),
            ['request_id' => (string) Str::uuid(), 'due_on' => $due, 'item' => $item, 'ticked' => true])->assertRedirect();
    }

    $this->travelTo($at(10));
    expect(app(RemindVisitReady::class)->handle())->toBe(['opened' => 0, 'reminded' => 0])
        ->and(PartyNotice::query()->where('kind', 'business.visit_ready_reminder')->count())->toBe(0);
});

it('skips a Business with no entity party to tell', function (): void {
    ['at' => $at] = visitReadyIssued();
    $audience = Mockery::mock(BusinessNoticeAudience::class);
    $audience->shouldReceive('findEntityParty')->andReturnNull();
    $this->app->instance(BusinessNoticeAudience::class, $audience);

    $this->travelTo($at(14));
    expect(app(RemindVisitReady::class)->handle())->toBe(['opened' => 0, 'reminded' => 0])
        ->and(PartyNotice::query()->where('kind', 'like', 'business.visit_ready%')->count())->toBe(0);
});

it('runs every hour with overlap protection, so a missed hour catches up', function (): void {
    $events = array_values(array_filter(app(Schedule::class)->events(), fn ($event): bool => str_contains($event->command ?? '', 'business:remind-visit-ready')));
    expect($events)->toHaveCount(1)->and($events[0]->expression)->toBe('0 * * * *')
        ->and($events[0]->withoutOverlapping)->toBeTrue()->and($events[0]->expiresAt)->toBe(30);
});
