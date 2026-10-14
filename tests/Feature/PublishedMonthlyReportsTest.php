<?php

declare(strict_types=1);

use App\Application\Auditor\Contracts\PublishedMonthlyReports;
use App\Models\AuditReportPublication;
use Illuminate\Support\Str;
use Tests\Support\AuditSealingFixture as Fixture;

/*
 * The Investor-facing facts of a Business's published monthly reports (FR-103, FR-110): only a
 * published monthly report with a valid seal and reconciled monthly figures, never a Flash audit
 * or one still in its review window.
 */

beforeEach(function (): void {
    $this->freezeSecond();
});

it('reads a published monthly report with its reconciled figures, the Audit Partner and the business note', function (): void {
    $fixture = Fixture::ready(kind: 'monthly');
    Fixture::seal($fixture);
    $reports = app(PublishedMonthlyReports::class);
    expect($reports->forBusiness($fixture['audit']['business']))->toBe([]);

    expect(Fixture::cosign($fixture, overrides: ['note' => 'Sales held steady.'])['code'])->toBe('REPORT_PUBLISHED');
    [$report] = $reports->forBusiness($fixture['audit']['business']);

    expect($report)->toMatchArray(['report_id' => $fixture['report']->id, 'operational_status' => 'active', 'differences' => false,
        'auditor_note' => '', 'business_note' => 'Sales held steady.'])
        ->and($report['period'])->toMatch('/^\d{4}-\d{2}$/')
        ->and($report['net'])->toBe((string) ((int) $report['inflow'] - (int) $report['outflow']))
        ->and($report['auditor']['licence'])->not->toBe('')
        ->and($reports->forBusiness(strtolower((string) Str::ulid())))->toBe([]);
});

it('marks a counted difference and keeps the Audit Partner\'s note', function (): void {
    $fixture = Fixture::ready(kind: 'monthly', findings: true);
    Fixture::seal($fixture);
    Fixture::cosign($fixture);

    expect(app(PublishedMonthlyReports::class)->forBusiness($fixture['audit']['business'])[0])
        ->toMatchArray(['differences' => true, 'auditor_note' => 'Observed difference retained for review.', 'business_note' => '']);
});

it('never reads a Flash audit or a report whose publication is not published', function (): void {
    $flash = Fixture::ready();
    Fixture::seal($flash);
    Fixture::cosign($flash);
    expect(AuditReportPublication::query()->sole()->status)->toBe('published')
        ->and(app(PublishedMonthlyReports::class)->forBusiness($flash['audit']['business']))->toBe([]);
});
