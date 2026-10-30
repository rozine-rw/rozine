<?php

declare(strict_types=1);

use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Primary\Contracts\HeldNoteCapital;
use App\Application\Primary\Contracts\RetainedNoteSchedules;
use App\Application\Primary\RetainedHoldingSchedule;
use App\Application\Primary\RetainedNoteSchedule;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use Tests\Support\BusinessRepaymentFixture;
use Tests\Support\PrimaryHoldingFixture;
use Mockery\MockInterface;

beforeEach(fn () => $this->freezeSecond());

it('counts only the principal of notes the Party holds, never wallet cash, holds or unissued purchases', function (): void {
    ['campaign' => $campaign, 'commitments' => $commitments] = PrimaryHoldingFixture::committed(['600', '480', '1080'], buyers: [0 => 'a', 1 => 'a']);
    $party = fn (PrimaryCommitment $commitment): string => PrimaryReservationRecord::query()->whereKey($commitment->primary_reservation_id)->sole()->party_id;
    $capital = app(HeldNoteCapital::class);
    $before = $capital->current($party($commitments[0]));
    expect($before)->toMatchArray(['outstanding_principal' => '0', 'revision' => 0, 'complete' => true]);

    $closing = PrimaryHoldingFixture::issuedClosing($campaign);
    $ids = array_map(fn (PrimaryCommitment $commitment): string => PrimaryHoldingFixture::insert($commitment->id, $closing), $commitments);
    array_map(fn (PrimaryCommitment $commitment) => PrimaryHoldingFixture::issue($commitment->id, $closing), $commitments);
    PrimaryHoldingFixture::flushDeferredChecks();

    $held = $capital->current($party($commitments[0]));
    $notes = [['id' => $ids[0], 'principal' => '3000000'], ['id' => $ids[1], 'principal' => '2400000']];
    usort($notes, fn (array $left, array $right): int => strcmp($left['id'], $right['id']));
    expect($held['outstanding_principal'])->toBe('5400000')
        ->and($held['revision'])->toBe(2)
        ->and($held['complete'])->toBeTrue()
        ->and($held['as_of'])->toBe(now('UTC')->format('Y-m-d\TH:i:s\Z'))
        ->and($held['sha256'])->toBe(hash('sha256', app(CanonicalJson::class)->encode(['version' => 'held-note-capital-2',
            'party_id' => $party($commitments[0]), 'notes' => $notes, 'outstanding_principal' => '5400000', 'complete' => true])))
        ->and($held['sha256'])->not->toBe($before['sha256'])
        ->and($capital->current($party($commitments[2]))['outstanding_principal'])->toBe('5400000');
});

it('reports the held figure incomplete once any Business repayment could have reduced principal', function (): void {
    ['campaign' => $campaign, 'commitments' => $commitments] = PrimaryHoldingFixture::committed();
    $closing = PrimaryHoldingFixture::issuedClosing($campaign);
    foreach ($commitments as $issued) {
        PrimaryHoldingFixture::insert($issued->id, $closing);
        PrimaryHoldingFixture::issue($issued->id, $closing);
    }
    $commitment = $commitments[0];
    $party = PrimaryReservationRecord::query()->whereKey($commitment->primary_reservation_id)->sole()->party_id;
    $complete = app(HeldNoteCapital::class)->current($party);

    BusinessRepaymentFixture::record(BusinessRepaymentFixture::fundedWallet());
    $incomplete = app(HeldNoteCapital::class)->current($party);
    expect($complete['complete'])->toBeTrue()
        ->and($incomplete['complete'])->toBeFalse()
        ->and($incomplete['outstanding_principal'])->toBe('5400000')
        ->and($incomplete['sha256'])->not->toBe($complete['sha256']);
});

/**
 * Issues every commitment of a fresh campaign and returns the first buyer's Party, the campaign and its retained schedule.
 *
 * @return array{party: string, campaign: string, schedule: RetainedNoteSchedule}
 */
function heldNoteIssued(): array
{
    ['campaign' => $campaign, 'commitments' => $commitments] = PrimaryHoldingFixture::committed();
    $closing = PrimaryHoldingFixture::issuedClosing($campaign);
    foreach ($commitments as $issued) {
        PrimaryHoldingFixture::insert($issued->id, $closing);
        PrimaryHoldingFixture::issue($issued->id, $closing);
    }
    PrimaryHoldingFixture::flushDeferredChecks();
    $party = PrimaryReservationRecord::query()->whereKey($commitments[0]->primary_reservation_id)->sole()->party_id;
    $schedule = app(RetainedNoteSchedules::class)->find($campaign->id);
    assert($schedule instanceof RetainedNoteSchedule);

    return ['party' => $party, 'campaign' => $campaign->id, 'schedule' => $schedule];
}

/** @param  list<RetainedHoldingSchedule>  $holdings */
function heldNoteSchedule(RetainedNoteSchedule $schedule, array $holdings, ?string $campaignId = null): RetainedNoteSchedule
{
    return new RetainedNoteSchedule(...['campaignId' => $campaignId ?? $schedule->campaignId, 'holdings' => $holdings] + get_object_vars($schedule));
}

/** @param  array<string, mixed>  $changes */
function heldNoteHolding(RetainedHoldingSchedule $holding, array $changes): RetainedHoldingSchedule
{
    return new RetainedHoldingSchedule(...$changes + get_object_vars($holding));
}

it('takes each held principal from the retained schedule that issued it', function (): void {
    ['party' => $party, 'schedule' => $schedule] = heldNoteIssued();
    $own = array_values(array_filter($schedule->holdings, fn (RetainedHoldingSchedule $holding): bool => $holding->partyId === $party));
    $capital = app(HeldNoteCapital::class)->current($party);

    expect($own)->not->toBeEmpty()
        ->and($capital['revision'])->toBe(count($own))
        ->and($capital['outstanding_principal'])->toBe((string) array_sum(array_map(fn (RetainedHoldingSchedule $holding): int => (int) $holding->principal, $own)));
});

it('fails closed when the retained issuance authority is missing or disagrees with the stored Holdings', function (Closure $tamper): void {
    ['party' => $party, 'schedule' => $schedule] = heldNoteIssued();
    $replacement = $tamper($schedule, $party);
    $this->mock(RetainedNoteSchedules::class, fn (MockInterface $mock) => $mock->shouldReceive('find')->andReturn($replacement));

    expect(fn () => app(HeldNoteCapital::class)->current($party))->toThrow(RuntimeException::class, 'PRIMARY_ISSUANCE_INTEGRITY_FAILED');
})->with([
    'no schedule for an issued campaign' => [fn (RetainedNoteSchedule $schedule, string $party): ?RetainedNoteSchedule => null],
    'a schedule for another campaign' => [fn (RetainedNoteSchedule $schedule, string $party): RetainedNoteSchedule => heldNoteSchedule($schedule, $schedule->holdings, '01jzzzzzzzzzzzzzzzzzzzzzzz')],
    'a principal that disagrees' => [fn (RetainedNoteSchedule $schedule, string $party): RetainedNoteSchedule => heldNoteSchedule($schedule, array_map(
        fn (RetainedHoldingSchedule $holding): RetainedHoldingSchedule => $holding->partyId === $party ? heldNoteHolding($holding, ['principal' => '1']) : $holding, $schedule->holdings))],
    'another commitment' => [fn (RetainedNoteSchedule $schedule, string $party): RetainedNoteSchedule => heldNoteSchedule($schedule, array_map(
        fn (RetainedHoldingSchedule $holding): RetainedHoldingSchedule => $holding->partyId === $party ? heldNoteHolding($holding, ['commitmentId' => '01jzzzzzzzzzzzzzzzzzzzzzzz']) : $holding, $schedule->holdings))],
    'a Holding the store does not carry' => [fn (RetainedNoteSchedule $schedule, string $party): RetainedNoteSchedule => heldNoteSchedule($schedule, array_map(
        fn (RetainedHoldingSchedule $holding): RetainedHoldingSchedule => $holding->partyId === $party ? heldNoteHolding($holding, ['holdingId' => '01jzzzzzzzzzzzzzzzzzzzzzzz']) : $holding, $schedule->holdings))],
    'a stored Holding the schedule leaves out' => [fn (RetainedNoteSchedule $schedule, string $party): RetainedNoteSchedule => heldNoteSchedule($schedule, array_values(array_filter(
        $schedule->holdings, fn (RetainedHoldingSchedule $holding): bool => $holding->partyId !== $party)))],
    'a duplicated schedule Holding' => [function (RetainedNoteSchedule $schedule, string $party): RetainedNoteSchedule {
        $own = array_values(array_filter($schedule->holdings, fn (RetainedHoldingSchedule $holding): bool => $holding->partyId === $party));
        $others = array_values(array_filter($schedule->holdings, fn (RetainedHoldingSchedule $holding): bool => $holding->partyId !== $party));

        return heldNoteSchedule($schedule, [...$others, ...array_fill(0, count($own) + 1, $own[0])]);
    }],
]);
