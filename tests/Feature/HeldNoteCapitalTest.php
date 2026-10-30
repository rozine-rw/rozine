<?php

declare(strict_types=1);

use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Primary\Contracts\HeldNoteCapital;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use Tests\Support\BusinessRepaymentFixture;
use Tests\Support\PrimaryHoldingFixture;

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
        ->and($held['sha256'])->toBe(hash('sha256', app(CanonicalJson::class)->encode(['version' => 'held-note-capital-1',
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
