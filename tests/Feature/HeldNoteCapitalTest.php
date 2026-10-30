<?php

declare(strict_types=1);

use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Primary\Contracts\HeldNoteCapital;
use App\Application\Primary\Contracts\RetainedNoteSchedules;
use App\Application\Primary\RetainedHoldingSchedule;
use App\Application\Primary\RetainedNoteSchedule;
use App\Models\PrimaryHolding;
use App\Models\PrimaryReservationRecord;
use Illuminate\Support\Str;
use Tests\Support\BusinessRepaymentFixture;
use Tests\Support\PrimaryIssuanceFixture;

beforeEach(fn () => $this->freezeSecond());

/** Serves one replacement schedule for its campaign, as a retained source that disagrees with the store would. */
function heldNoteSource(?RetainedNoteSchedule $schedule, string $campaignId): void
{
    app()->instance(RetainedNoteSchedules::class, new class($schedule, $campaignId) implements RetainedNoteSchedules
    {
        public function __construct(private ?RetainedNoteSchedule $schedule, private string $campaignId) {}

        public function find(string $campaignId): ?RetainedNoteSchedule
        {
            return $campaignId === $this->campaignId ? $this->schedule : null;
        }
    });
}

/** @param  array<int, RetainedHoldingSchedule>  $holdings */
function heldNoteSchedule(RetainedNoteSchedule $issued, array $holdings, ?string $campaignId = null): RetainedNoteSchedule
{
    return new RetainedNoteSchedule($campaignId ?? $issued->campaignId, $issued->businessId, $issued->fundingId, $issued->closing, $issued->issuedAt,
        $issued->principal, $issued->contractualReturn, $issued->gross, array_values($holdings), $issued->instalments);
}

function heldNoteHolding(RetainedHoldingSchedule $holding, ?string $holdingId = null, ?string $commitmentId = null, ?string $principal = null): RetainedHoldingSchedule
{
    return new RetainedHoldingSchedule($holdingId ?? $holding->holdingId, $holding->holdingSha256, $commitmentId ?? $holding->commitmentId,
        $holding->reservationId, $holding->partyId, $holding->units, $holding->ordinals, $holding->reservationSha256, $holding->confirmationVersionId,
        $holding->confirmationRevision, $holding->confirmationSha256, $holding->rights, $holding->terms, $principal ?? $holding->principal,
        $holding->contractualReturn, $holding->gross, $holding->instalments);
}

it('counts only the principal of notes the retained schedule issued the Party, never wallet cash, holds or unissued purchases', function (): void {
    $approved = PrimaryIssuanceFixture::approved(units: ['600', '480', '1080'], buyers: [0 => 'a', 1 => 'a']);
    $parties = PrimaryReservationRecord::query()->pluck('party_id')->unique()->values()->all();
    $capital = app(HeldNoteCapital::class);
    $before = array_map(fn (string $party): array => $capital->current($party), $parties);
    expect(array_column($before, 'outstanding_principal'))->each->toBe('0')
        ->and(array_column($before, 'complete'))->each->toBeTrue();

    $approved['observe'](now('UTC')->toIso8601String());
    $party = PrimaryHolding::query()->where('units', 600)->sole()->party_id;
    $notes = PrimaryHolding::query()->where('party_id', $party)->orderBy('id')->get()
        ->map(fn (PrimaryHolding $holding): array => ['id' => $holding->id, 'principal' => (string) $holding->principal])->all();
    $held = $capital->current($party);
    expect($notes)->toHaveCount(2)
        ->and($held['outstanding_principal'])->toBe('5400000')
        ->and($held['revision'])->toBe(2)
        ->and($held['complete'])->toBeTrue()
        ->and($held['as_of'])->toBe(now('UTC')->format('Y-m-d\TH:i:s\Z'))
        ->and($held['sha256'])->toBe(hash('sha256', app(CanonicalJson::class)->encode(['version' => 'held-note-capital-2',
            'party_id' => $party, 'notes' => $notes, 'outstanding_principal' => '5400000', 'complete' => true])))
        ->and($capital->current(PrimaryHolding::query()->where('units', 1080)->sole()->party_id)['outstanding_principal'])->toBe('5400000');
});

it('reports the held figure incomplete once any Business repayment could have reduced principal', function (): void {
    PrimaryIssuanceFixture::close(now('UTC')->toIso8601String());
    $party = PrimaryHolding::query()->orderBy('id')->firstOrFail()->party_id;
    $complete = app(HeldNoteCapital::class)->current($party);

    BusinessRepaymentFixture::record(BusinessRepaymentFixture::fundedWallet());
    $incomplete = app(HeldNoteCapital::class)->current($party);
    expect($complete['complete'])->toBeTrue()
        ->and($incomplete['complete'])->toBeFalse()
        ->and($incomplete['outstanding_principal'])->toBe($complete['outstanding_principal'])
        ->and($incomplete['sha256'])->not->toBe($complete['sha256']);
});

it('fails closed when the retained issuance authority is missing or disagrees with the stored Holdings', function (Closure $tamper): void {
    PrimaryIssuanceFixture::close(now('UTC')->toIso8601String(), units: ['600', '480', '1080'], buyers: [0 => 'a', 1 => 'a']);
    $holding = PrimaryHolding::query()->where('units', 600)->sole();
    $issued = app(RetainedNoteSchedules::class)->find($holding->business_campaign_id);
    expect($issued)->not->toBeNull();
    $own = array_values(array_filter($issued->holdings, fn (RetainedHoldingSchedule $candidate): bool => $candidate->partyId === $holding->party_id));
    $others = array_values(array_filter($issued->holdings, fn (RetainedHoldingSchedule $candidate): bool => $candidate->partyId !== $holding->party_id));
    heldNoteSource($tamper($issued, $own, $others), $holding->business_campaign_id);

    expect($own)->toHaveCount(2)
        ->and(fn () => app(HeldNoteCapital::class)->current($holding->party_id))->toThrow(RuntimeException::class, 'PRIMARY_ISSUANCE_INTEGRITY_FAILED');
})->with([
    'no schedule for an issued campaign' => [fn (RetainedNoteSchedule $issued, array $own, array $others): ?RetainedNoteSchedule => null],
    'a schedule for another campaign' => [fn (RetainedNoteSchedule $issued, array $own, array $others): RetainedNoteSchedule => heldNoteSchedule($issued, $issued->holdings, (string) Str::ulid())],
    'a principal that disagrees' => [fn (RetainedNoteSchedule $issued, array $own, array $others): RetainedNoteSchedule => heldNoteSchedule($issued, [...$others, heldNoteHolding($own[0], principal: '1'), $own[1]])],
    'another commitment' => [fn (RetainedNoteSchedule $issued, array $own, array $others): RetainedNoteSchedule => heldNoteSchedule($issued, [...$others, heldNoteHolding($own[0], commitmentId: (string) Str::ulid()), $own[1]])],
    'a Holding the store does not carry' => [fn (RetainedNoteSchedule $issued, array $own, array $others): RetainedNoteSchedule => heldNoteSchedule($issued, [...$others, heldNoteHolding($own[0], holdingId: (string) Str::ulid()), $own[1]])],
    'a stored Holding the schedule leaves out' => [fn (RetainedNoteSchedule $issued, array $own, array $others): RetainedNoteSchedule => heldNoteSchedule($issued, [...$others, $own[0]])],
    'a duplicated schedule Holding' => [fn (RetainedNoteSchedule $issued, array $own, array $others): RetainedNoteSchedule => heldNoteSchedule($issued, [...$others, $own[0], $own[0]])],
]);
