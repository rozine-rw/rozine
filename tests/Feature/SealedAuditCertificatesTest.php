<?php

declare(strict_types=1);

use App\Application\Business\Contracts\HeldVisitDocuments;
use App\Models\AuditReport;
use Tests\Support\AuditSealingFixture;

/*
 * BRS v3 VIS-R03: documents the CPA already holds are not asked again. The tax and RSSB clearance
 * certificates are held while the latest sealed audit report's recorded expiry date is on or after
 * the day the visit is sealed by; a draft report holds nothing.
 */

beforeEach(function (): void {
    $this->freezeSecond();
});

it('holds each certificate until the expiry date its latest sealed report recorded', function (): void {
    $fixture = AuditSealingFixture::ready(kind: 'monthly');
    $business = (string) $fixture['report']->business_id;
    $held = fn (string $on): array => app(HeldVisitDocuments::class)->held($business, $on);

    // A report still in draft is not something the CPA holds.
    expect($held('2027-01-01'))->toBe([]);

    expect(AuditSealingFixture::seal($fixture)['code'])->toBe('AUDIT_SEALED')
        ->and($held('2027-03-31'))->toBe(['tax_clearance', 'rssb_clearance'])
        ->and($held('2027-04-01'))->toBe(['rssb_clearance'])
        ->and($held('2027-07-01'))->toBe([])
        ->and(app(HeldVisitDocuments::class)->held('01J00000000000000000000000', '2027-01-01'))->toBe([]);
});

it('reads the tax clearance a baseline visit recorded on its registration step', function (): void {
    $fixture = AuditSealingFixture::ready();
    $report = AuditReport::query()->whereKey($fixture['report']->id)->firstOrFail();
    expect(AuditSealingFixture::seal($fixture)['code'])->toBe('AUDIT_SEALED');
    $business = (string) $report->business_id;
    expect($report->binding['baseline'] ?? false)->toBeTrue()
        ->and(app(HeldVisitDocuments::class)->held($business, '2027-01-01'))->toBe(['tax_clearance']);

});
