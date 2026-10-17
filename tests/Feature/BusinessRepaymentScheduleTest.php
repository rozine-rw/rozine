<?php

declare(strict_types=1);

use App\Application\Primary\Contracts\RetainedNoteSchedules;
use App\Application\Primary\GetRetainedNoteSchedule;
use App\Models\BusinessCampaign;
use App\Models\User;
use Brick\Math\BigInteger;
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
        ->and($props['issued'])->toHaveCount(1);
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
