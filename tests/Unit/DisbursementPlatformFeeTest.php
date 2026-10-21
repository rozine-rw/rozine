<?php

declare(strict_types=1);

use App\Domain\Disbursement\DisbursementViolation;
use App\Domain\Disbursement\PlatformFee;

it('splits the BRS SVC-R01 example: 3% of RWF 12M, 40% of it to the Audit Partner', function (): void {
    expect(PlatformFee::of('12000000')->toArray())->toBe([
        'principal' => '12000000', 'fee' => '360000', 'audit_partner_share' => '144000',
        'rozine_share' => '216000', 'payout' => '11640000', 'version' => 'brs-2.0-fee-r01',
    ]);
});

it('rounds each part half up to a whole franc and keeps the parts balanced', function (string $principal, string $fee, string $auditPartner): void {
    $split = PlatformFee::of($principal);

    expect((string) $split->fee)->toBe($fee)
        ->and((string) $split->auditPartnerShare)->toBe($auditPartner)
        ->and((string) $split->rozineShare->plus($split->auditPartnerShare))->toBe($fee)
        ->and((string) $split->payout->plus($split->fee))->toBe($principal);
})->with([
    'one issue unit' => ['5000', '150', '60'],
    'a fee ending in a half franc' => ['5050', '152', '61'],
    'a share ending in a half franc' => ['1250', '38', '15'],
]);

it('refuses a principal that is not a positive whole number of francs', function (string $principal): void {
    expect(fn () => PlatformFee::of($principal))->toThrow(DisbursementViolation::class, 'PLATFORM_FEE_PRINCIPAL_INVALID');
})->with(['0', '-5000', '5000.5', '', '05000']);
