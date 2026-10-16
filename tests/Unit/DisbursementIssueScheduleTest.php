<?php

declare(strict_types=1);

use App\Domain\Disbursement\DisbursementReason;
use App\Domain\Disbursement\DisbursementViolation;
use App\Domain\Disbursement\IntentDigest;
use App\Domain\Disbursement\IssueSchedule;

it('anchors due dates on the original Kigali day and clamps each to month end', function (string $effectiveAt, int $term, string $effectiveDate, array $dueDates): void {
    $schedule = IssueSchedule::dates($effectiveAt, $term);
    expect([$schedule->effectiveDate, $schedule->dueDates])->toBe([$effectiveDate, $dueDates]);
})->with([
    'Jan 31 in a common year' => ['2027-01-31T08:00:00Z', 3, '2027-01-31', ['2027-02-28', '2027-03-31', '2027-04-30']],
    'Jan 31 in a leap year gives Feb 29' => ['2028-01-31T08:00:00Z', 3, '2028-01-31', ['2028-02-29', '2028-03-31', '2028-04-30']],
    'Feb 29 anchor keeps the 29th' => ['2028-02-29T12:00:00+02:00', 2, '2028-02-29', ['2028-03-29', '2028-04-29']],
    'Kigali midnight crosses into the next local day' => ['2026-12-31T22:00:00Z', 2, '2027-01-01', ['2027-02-01', '2027-03-01']],
    'one second before Kigali midnight stays on the UTC day' => ['2026-12-31T21:59:59Z', 2, '2026-12-31', ['2027-01-31', '2027-02-28']],
    'offset input is read in Kigali' => ['2026-10-30T23:30:00-05:00', 1, '2026-10-31', ['2026-11-30']],
    'year rollover' => ['2026-11-15T09:00:00Z', 14, '2026-11-15', ['2026-12-15', '2027-01-15', '2027-02-15', '2027-03-15', '2027-04-15', '2027-05-15',
        '2027-06-15', '2027-07-15', '2027-08-15', '2027-09-15', '2027-10-15', '2027-11-15', '2027-12-15', '2028-01-15']],
    'fractional seconds' => ['2026-05-31T10:00:00.123456Z', 1, '2026-05-31', ['2026-06-30']],
]);

it('refuses a schedule input it cannot read exactly', function (string $effectiveAt, int $term): void {
    expect(fn () => IssueSchedule::dates($effectiveAt, $term))->toThrow(DisbursementViolation::class, 'ISSUE_SCHEDULE_INPUT_INVALID');
})->with([
    'no term' => ['2026-01-31T00:00:00Z', 0],
    'term too long' => ['2026-01-31T00:00:00Z', 121],
    'relative time' => ['tomorrow', 3],
    'no offset' => ['2026-01-31T00:00:00', 3],
    'impossible month' => ['2026-13-01T00:00:00Z', 3],
    'impossible day that PHP would roll over' => ['2026-02-30T00:00:00Z', 3],
]);

/** @return array<string, string|int> */
function intentFields(): array
{
    return ['disbursement_id' => 'd1', 'revision' => 1, 'campaign_id' => 'c1', 'exposure_reservation_id' => 'e1', 'amount' => '10700000',
        'currency' => 'RWF', 'destination_sha256' => str_repeat('d', 64), 'commitments_digest' => str_repeat('c', 64), 'provider' => 'synthetic', 'environment' => 'testing'];
}

it('binds the intent digest to every intent fact regardless of key order', function (): void {
    $digest = IntentDigest::intent(intentFields());
    expect($digest)->toMatch('/^[0-9a-f]{64}$/')
        ->and(IntentDigest::intent(array_reverse(intentFields(), true)))->toBe($digest);
    foreach (['revision' => 2, 'amount' => '10700001', 'destination_sha256' => str_repeat('e', 64), 'commitments_digest' => str_repeat('f', 64), 'environment' => 'local'] as $field => $value) {
        expect(IntentDigest::intent([...intentFields(), $field => $value]))->not->toBe($digest);
    }
});

it('refuses an intent or destination digest over missing, extra or empty facts', function (): void {
    $missing = intentFields();
    unset($missing['provider']);
    expect(fn () => IntentDigest::intent($missing))->toThrow(DisbursementViolation::class, 'INTENT_DIGEST_INPUT_INVALID')
        ->and(fn () => IntentDigest::intent([...intentFields(), 'masked' => '•••• 1234']))->toThrow(DisbursementViolation::class, 'INTENT_DIGEST_INPUT_INVALID')
        ->and(fn () => IntentDigest::intent([...intentFields(), 'amount' => '']))->toThrow(DisbursementViolation::class, 'INTENT_DIGEST_INPUT_INVALID')
        ->and(fn () => IntentDigest::intent([...intentFields(), 'revision' => -1]))->toThrow(DisbursementViolation::class, 'INTENT_DIGEST_INPUT_INVALID')
        ->and(fn () => IntentDigest::destination(['destination_id' => 'x']))->toThrow(DisbursementViolation::class, 'DESTINATION_DIGEST_INPUT_INVALID');

    $destination = ['destination_id' => 'p1', 'revision' => 1, 'business_id' => 'b1', 'mandate_id' => 'm1', 'rail' => 'synthetic-bank',
        'account_token_sha256' => str_repeat('a', 64), 'environment' => 'testing', 'evidence_sha256' => str_repeat('b', 64),
        'verified_at' => '2026-09-28T00:00:00Z', 'expires_at' => '2027-09-28T00:00:00Z'];
    expect(IntentDigest::destination($destination))->toMatch('/^[0-9a-f]{64}$/')
        ->and(IntentDigest::destination([...$destination, 'mandate_id' => 'm2']))->not->toBe(IntentDigest::destination($destination));
});

it('digests an ordered commitment list and refuses an empty or keyed one', function (): void {
    $commitments = [['id' => 'a', 'principal' => '5000'], ['id' => 'b', 'principal' => '10000']];
    expect(IntentDigest::commitments($commitments))->toMatch('/^[0-9a-f]{64}$/')
        ->and(IntentDigest::commitments(array_reverse($commitments)))->not->toBe(IntentDigest::commitments($commitments))
        ->and(fn () => IntentDigest::commitments([]))->toThrow(DisbursementViolation::class, 'COMMITMENTS_DIGEST_INPUT_INVALID')
        ->and(fn () => IntentDigest::commitments([1 => ['id' => 'a']]))->toThrow(DisbursementViolation::class, 'COMMITMENTS_DIGEST_INPUT_INVALID');
});

it('normalizes a written reason and refuses an empty, long or control-character one', function (): void {
    expect(DisbursementReason::normalize('  Funded in full; destination verified.  '))->toBe('Funded in full; destination verified.')
        ->and(DisbursementReason::normalize('   '))->toBeNull()
        ->and(DisbursementReason::normalize(str_repeat('a', 1001)))->toBeNull()
        ->and(DisbursementReason::normalize(str_repeat('é', 1000)))->toBe(str_repeat('é', 1000))
        ->and(DisbursementReason::normalize("line\nbreak"))->toBeNull()
        ->and(DisbursementReason::normalize("zero\u{200B}width"))->toBeNull()
        ->and(DisbursementReason::normalize("separator\u{2028}"))->toBeNull()
        ->and(DisbursementReason::normalize("bad \xC3\x28 bytes"))->toBeNull();
});
