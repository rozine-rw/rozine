<?php

declare(strict_types=1);

use App\Application\Auditor\Contracts\PublishedMonthlyReports;
use App\Application\Primary\Contracts\BusinessHolders;
use App\Models\AuditReportPublication;
use App\Models\PartyNotice;
use App\Notifications\Investor\ReportPublished;
use Database\Factories\Scenarios\AuditSealingFixture as Fixture;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryHoldingFixture;

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

it('tells each holder of the business, once, when its monthly report publishes', function (): void {
    Notification::fake();
    $holder = InvestorWalletFixture::ready();
    app()->instance(BusinessHolders::class, new class($holder['party']->id) implements BusinessHolders
    {
        public function __construct(private readonly string $partyId) {}

        public function parties(string $businessId): array
        {
            return [$this->partyId];
        }
    });
    $fixture = Fixture::ready(kind: 'monthly');
    Fixture::seal($fixture);
    expect(PartyNotice::query()->where('kind', 'primary.report_published')->exists())->toBeFalse();

    Fixture::cosign($fixture);
    $notice = PartyNotice::query()->where('kind', 'primary.report_published')->sole();
    expect($notice->party_id)->toBe($holder['party']->id)
        ->and($notice->event_key)->toBe('report-published:'.$fixture['report']->id.':'.$holder['party']->id)
        ->and($notice->facts)->toMatchArray(['business_id' => $fixture['audit']['business'], 'report_id' => $fixture['report']->id])
        ->and($notice->facts['period'])->toMatch('/^\d{4}-\d{2}$/')
        ->and($notice->facts['partner'])->not->toBe('');
    Notification::assertSentToTimes($holder['user'], ReportPublished::class, 1);
});

it('names the distinct Parties holding any of a business\'s Holdings', function (): void {
    ['campaign' => $campaign, 'commitments' => $commitments] = PrimaryHoldingFixture::committed(['540', '540', '1080'], buyers: [0 => 'same', 1 => 'same']);
    $closing = PrimaryHoldingFixture::issuedClosing($campaign);
    foreach ($commitments as $commitment) {
        PrimaryHoldingFixture::insert($commitment->id, $closing);
    }
    $parties = DB::table('primary_holdings')->distinct()->orderBy('party_id')->pluck('party_id')->all();

    expect($parties)->toHaveCount(2)
        ->and(app(BusinessHolders::class)->parties($campaign->business_id))->toBe($parties)
        ->and(app(BusinessHolders::class)->parties(strtolower((string) Str::ulid())))->toBe([]);
});
