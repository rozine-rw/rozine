<?php

declare(strict_types=1);

use App\Domain\Auditor\AccreditationProfile;
use App\Domain\Auditor\AuditorStanding;
use App\Domain\Operations\CommandRejection;

it('keeps self submitted claims separate from independently reviewed standing', function (): void {
    $policy = new AccreditationProfile(new AuditorStanding);
    $now = new DateTimeImmutable('2026-09-24T08:00:00Z');
    $pending = $policy->submit($policy->empty(), 'certificate-a', ' CPA-A ', '2026-09-24', $now);
    expect($pending['standing']['status'])->toBe('none')->and(($pending['submission']['licence'] ?? null))->toBe('CPA-A')->and($pending['accepting'])->toBeFalse();
    $approved = $policy->review($pending, 'approve', 'certificate-a', '2026-09-24T08:00:00Z', 'manual-register-check', 'Reviewed', $now);
    expect($approved['certificate_id'])->toBe('certificate-a')->and($approved['standing']['status'])->toBe('active');
    $renewal = $policy->submit($policy->availability($approved, true, $now), 'certificate-b', 'CPA-B', '2027-01-01', $now, true);
    expect($renewal['standing'])->toBe($approved['standing']);
    $rejected = $policy->review($renewal, 'reject', 'certificate-b', '2026-09-24T08:00:00Z', 'manual-register-check', 'Wrong document', $now);
    expect($rejected['standing'])->toBe($approved['standing'])->and($rejected['accepting'])->toBeTrue()
        ->and($rejected['submission'])->toBe(['status' => 'rejected', 'id' => 'certificate-b', 'reason' => 'Wrong document']);
    expect($policy->withdraw($renewal, 'certificate-b')['standing'])->toBe($approved['standing']);
    expect(fn () => $policy->withdraw($renewal, 'certificate-a'))->toThrow(CommandRejection::class, 'ACCREDITATION_SUBMISSION_STALE');
    expect(fn () => $policy->submit($renewal, 'certificate-c', 'CPA-C', '2027-01-01', $now))->toThrow(CommandRejection::class, 'ACCREDITATION_SUBMISSION_PENDING');
    expect(fn () => $policy->review($pending, 'approve', 'certificate-a', '2026-09-24T08:00:00Z', 'manual-register-check', 'Reviewed', new DateTimeImmutable('2026-09-24T22:00:00Z')))
        ->toThrow(CommandRejection::class, 'ACCREDITATION_SUBMISSION_EXPIRED');
});

it('refuses invalid certificate claims before creating pending authority', function (string $licence, string $expiry, string $reason): void {
    $policy = new AccreditationProfile(new AuditorStanding);
    expect(fn () => $policy->submit($policy->empty(), 'certificate-a', $licence, $expiry, new DateTimeImmutable('2026-09-24T08:00:00Z')))
        ->toThrow(CommandRejection::class, $reason);
})->with([
    ['', '2027-01-01', 'ACCREDITATION_LICENCE_INVALID'],
    ['  ', '2027-01-01', 'ACCREDITATION_LICENCE_INVALID'],
    [str_repeat('a', 33), '2027-01-01', 'ACCREDITATION_LICENCE_INVALID'],
    ["CPA\u{202e}", '2027-01-01', 'ACCREDITATION_LICENCE_INVALID'],
    ["CPA\xff", '2027-01-01', 'ACCREDITATION_LICENCE_INVALID'],
    ['CPA', 'yesterday', 'ACCREDITATION_EXPIRY_INVALID'],
    ['CPA', '2027-02-29', 'ACCREDITATION_EXPIRY_INVALID'],
    ['CPA', '2026-09-23', 'ACCREDITATION_EXPIRY_INVALID'],
]);

it('requires current explicit staff evidence for every review decision', function (string $decision, string $checkedAt, string $reference, string $reason, string $expected): void {
    $policy = new AccreditationProfile(new AuditorStanding);
    expect(fn () => $policy->review($policy->empty(), $decision, null, $checkedAt, $reference, $reason, new DateTimeImmutable('2026-09-24T08:00:00Z')))
        ->toThrow(CommandRejection::class, $expected);
})->with([
    ['invented', '2026-09-24T08:00:00Z', 'check', 'reviewed', 'ACCREDITATION_REVIEW_INVALID'],
    ['approve', '2026-09-24T08:00:00Z', ' ', 'reviewed', 'ACCREDITATION_REVIEW_INVALID'],
    ['approve', '2026-09-24T08:00:00Z', str_repeat('a', 256), 'reviewed', 'ACCREDITATION_REVIEW_INVALID'],
    ['approve', '2026-09-24T08:00:00Z', 'check', '', 'ACCREDITATION_REVIEW_INVALID'],
    ['approve', '2026-09-24T08:00:00Z', 'check', str_repeat('a', 2001), 'ACCREDITATION_REVIEW_INVALID'],
    ['approve', 'yesterday', 'check', 'reviewed', 'STANDING_CHECK_REQUIRED'],
    ['approve', '2026-02-30T08:00:00Z', 'check', 'reviewed', 'STANDING_CHECK_REQUIRED'],
    ['approve', '2026-09-24T08:00:01Z', 'check', 'reviewed', 'STANDING_CHECK_REQUIRED'],
    ['approve', '2026-08-25T07:59:59Z', 'check', 'reviewed', 'STANDING_CHECK_REQUIRED'],
    ['approve', '2026-09-24T08:00:00Z', 'check', 'reviewed', 'ACCREDITATION_SUBMISSION_STALE'],
    ['recheck', '2026-09-24T08:00:00Z', 'check', 'reviewed', 'ACCREDITATION_REVIEW_INVALID'],
]);
