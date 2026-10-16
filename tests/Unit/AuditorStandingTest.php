<?php

declare(strict_types=1);

use App\Domain\Auditor\AuditorStanding;
use App\Domain\Operations\CommandRejection;

it('accepts an evidenced current ICPAR check through the last day printed on the certificate in Rwanda', function (): void {
    $standing = ['status' => 'active', 'licence' => 'SYNTHETIC-CPA-001', 'expires_on' => '2026-09-24',
        'checked_at' => '2026-08-25T21:59:59Z', 'check_reference' => 'synthetic-manual-register-check'];
    $policy = new AuditorStanding;
    $policy->requireCurrent($standing, new DateTimeImmutable('2026-09-24T21:59:59Z'));
    expect(fn () => $policy->requireCurrent($standing, new DateTimeImmutable('2026-09-24T22:00:00Z')))
        ->toThrow(CommandRejection::class, 'ACCREDITATION_EXPIRED');
});

it('denies an inactive expired undocumented future or stale standing record', function (string $field, ?string $value, string $reason): void {
    $standing = [
        'status' => $field === 'status' && $value !== null ? $value : 'active',
        'licence' => $field === 'licence' ? $value : 'SYNTHETIC-CPA-001',
        'expires_on' => $field === 'expires_on' ? $value : '2026-12-31',
        'checked_at' => $field === 'checked_at' ? $value : '2026-09-24T08:00:00Z',
        'check_reference' => $field === 'check_reference' ? $value : 'synthetic-manual-register-check',
    ];
    expect(fn () => (new AuditorStanding)->requireCurrent($standing, new DateTimeImmutable('2026-09-24T08:00:00Z')))
        ->toThrow(CommandRejection::class, $reason);
})->with([
    ['status', 'suspended', 'ACCREDITATION_SUSPENDED'],
    ['status', 'revoked', 'ACCREDITATION_SUSPENDED'],
    ['status', 'pending', 'ACCREDITATION_REQUIRED'],
    ['licence', null, 'ACCREDITATION_REQUIRED'],
    ['licence', ' ', 'ACCREDITATION_REQUIRED'],
    ['expires_on', null, 'ACCREDITATION_EXPIRED'],
    ['expires_on', 'not-a-date', 'ACCREDITATION_EXPIRED'],
    ['expires_on', '2026-02-29', 'ACCREDITATION_EXPIRED'],
    ['expires_on', '2026-09-23', 'ACCREDITATION_EXPIRED'],
    ['checked_at', null, 'STANDING_CHECK_REQUIRED'],
    ['checked_at', 'yesterday', 'STANDING_CHECK_REQUIRED'],
    ['checked_at', '2026-02-30T08:00:00Z', 'STANDING_CHECK_REQUIRED'],
    ['checked_at', '2026-09-24T08:00:01Z', 'STANDING_CHECK_REQUIRED'],
    ['checked_at', '2026-08-25T07:59:59Z', 'STANDING_CHECK_REQUIRED'],
    ['check_reference', null, 'STANDING_CHECK_REQUIRED'],
    ['check_reference', ' ', 'STANDING_CHECK_REQUIRED'],
]);
