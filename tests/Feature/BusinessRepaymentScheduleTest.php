<?php

declare(strict_types=1);

use App\Application\Business\Contracts\HeldVisitDocuments;
use App\Application\Primary\Contracts\RetainedNoteSchedules;
use App\Application\Primary\GetRetainedNoteSchedule;
use App\Domain\Auditor\VisitReadyChecklist;
use App\Models\BusinessCampaign;
use App\Models\User;
use Brick\Math\BigInteger;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\PrimaryIssuanceFixture;

/*
 * Business Repayments before servicing opens: each issued Note's schedule as it was issued,
 * read through the retained Note schedule, and nothing due, payable or paid.
 */

beforeEach(function (): void {
    $this->freezeSecond();
    $this->withoutVite();
});

it('shows the issued schedule of each issued Note, as issued, with nothing due or payable', function (): void {
    ['campaign' => $campaign, 'instruction' => $instruction] = PrimaryIssuanceFixture::close(units: ['1', '1079', '1080']);
    $publication = BusinessCampaign::query()->findOrFail($campaign->campaignId);
    $snapshot = DB::transaction(fn () => app(GetRetainedNoteSchedule::class)->find($campaign->campaignId));
    $money = fn (string $amount): array => ['currency' => 'RWF', 'amount' => $amount];

    $props = $this->actingAs(User::query()->findOrFail($publication->actor_user_id))
        ->get(route('business.repayments.show', ['business' => $campaign->businessId]))->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->component('business/repayments'))->viewData('page')['props'];

    expect($props)->toMatchArray(['note' => null, 'servicing' => null, 'schedule' => [], 'pay' => null, 'allowed_actions' => [], 'actions' => ['pay' => null]])
        ->and($props['issued'])->toHaveCount(1)
        // Every instalment of this Note fell due in the past, so there is no visit to get ready for.
        ->and($props['visit_ready'])->toBeNull();
    $issued = $props['issued'][0];
    expect($issued)->toMatchArray(['note_id' => $campaign->campaignId, 'title' => $publication->payload['title'], 'effective_date' => $instruction->effectiveDate,
        'principal' => $money($campaign->principal), 'return' => $money($snapshot->contractualReturn), 'total' => $money($snapshot->gross)])
        ->and(array_column($issued['instalments'], 'due_on'))->toBe($instruction->dueDates)
        ->and(array_column(array_column($issued['instalments'], 'total'), 'amount'))->toBe(array_column(array_column($publication->payload['quote']['schedule'], 'amount'), 'amount'))
        ->and((string) BigInteger::sum(...array_column(array_column($issued['instalments'], 'principal'), 'amount')))->toBe($campaign->principal);
    foreach ($issued['instalments'] as $offset => $row) {
        expect($row['index'])->toBe($offset + 1)
            ->and((string) BigInteger::sum($row['principal']['amount'], $row['return']['amount']))->toBe($row['total']['amount']);
    }
});

it('lists no schedule for a Note that is funded but not yet issued', function (): void {
    ['campaign' => $campaign] = PrimaryIssuanceFixture::approved(settle: false);
    $publication = BusinessCampaign::query()->findOrFail($campaign->campaignId);

    $props = $this->actingAs(User::query()->findOrFail($publication->actor_user_id))
        ->get(route('business.repayments.show', ['business' => $campaign->businessId]))->assertOk()->viewData('page')['props'];

    expect($props['issued'])->toBe([]);
});

it('reads through its own transaction and leaves the caller\'s level as it was', function (): void {
    ['campaign' => $campaign] = PrimaryIssuanceFixture::close();
    $level = DB::transactionLevel();

    expect(app(RetainedNoteSchedules::class)->find($campaign->campaignId)?->campaignId)->toBe($campaign->campaignId)
        ->and(DB::transactionLevel())->toBe($level)
        ->and(app(RetainedNoteSchedules::class)->find('01J00000000000000000000000'))->toBeNull();
});

it('carries the visit-ready checklist from D−14 before the next instalment, built from the monthly visit steps (BRS v3 VIS-R03)', function (): void {
    ['campaign' => $campaign, 'instruction' => $instruction] = PrimaryIssuanceFixture::close(effectiveAt: now()->toIso8601String());
    $publication = BusinessCampaign::query()->findOrFail($campaign->campaignId);
    $user = User::query()->findOrFail($publication->actor_user_id);
    $read = fn (): mixed => $this->actingAs($user)->get(route('business.repayments.show', ['business' => $campaign->businessId]))->assertOk()->viewData('page')['props']['visit_ready'];
    [$first, $second] = $instruction->dueDates;
    $kigali = fn (string $date, int $days): CarbonImmutable => CarbonImmutable::parse($date, 'Africa/Kigali')->addDays($days)->setTime(9, 0);

    // Time only moves forward here: the identity was verified at the fixture's frozen now.
    expect($kigali($first, -15)->isFuture())->toBeTrue();
    $this->travelTo($kigali($first, -15));
    expect($read())->toBeNull();

    $this->travelTo($kigali($first, -14));
    $ready = $read();
    expect($ready)->toMatchArray(['due_on' => $first, 'opens_on' => $kigali($first, -14)->toDateString(),
        'visit_from' => $kigali($first, -10)->toDateString(), 'visit_by' => $kigali($first, -3)->toDateString()])
        // The CPA already holds the tax clearance certificate the baseline visit recorded, valid past this visit.
        ->and($ready['items'])->toBe((new VisitReadyChecklist)->items(['tax_clearance']))
        ->and(app(HeldVisitDocuments::class)->held($campaign->businessId, $kigali($first, -3)->toDateString()))->toBe(['tax_clearance']);

    $this->travelTo($kigali($first, 0));
    expect($read()['due_on'])->toBe($first);

    // Once an instalment's day has passed, the reminder moves to the next one when its D−14 arrives.
    $this->travelTo($kigali($first, 1));
    expect($read())->toBeNull();
    $this->travelTo($kigali($second, -14));
    expect($read()['due_on'])->toBe($second);
});
