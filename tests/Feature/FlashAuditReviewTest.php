<?php

declare(strict_types=1);

use App\Application\Auditor\Contracts\AuditReportPublicationStore;
use App\Application\Auditor\GetBusinessAuditReport;
use App\Domain\Auditor\MonthlyReportReview;
use App\Models\AuditPublicationEvent;
use App\Models\AuditReportPublication;
use App\Models\AuditReportSignature;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\Support\AuditSealingFixture as Fixture;

beforeEach(function (): void {
    $this->freezeSecond();
    $this->withoutVite();
});

it('seals a Flash report under the 24-hour window, where one signatory publishes it', function (): void {
    $fixture = Fixture::ready(2);
    expect(Fixture::seal($fixture)['code'])->toBe('AUDIT_SEALED');
    $publication = AuditReportPublication::query()->sole();
    expect($publication->policy_version)->toBe(MonthlyReportReview::FLASH_POLICY)
        ->and($publication->delivered_at->eq(now()))->toBeTrue()
        ->and($publication->due_at->diffInHours($publication->delivered_at, true))->toBe(24.0)
        ->and(AuditPublicationEvent::query()->sole()->command)->toBe('report.delivered');

    $page = app(GetBusinessAuditReport::class)->handle($fixture['audit']['authority']['users'][0]->id, 1, $fixture['audit']['business'], $fixture['report']->id);
    expect($page['cosign']['policy_version'])->toBe(MonthlyReportReview::FLASH_POLICY)
        ->and($page['cosign']['required_signatures'])->toBe(1)
        ->and($page['cosign']['due_at'])->toBe($publication->due_at->toIso8601String())
        ->and($page['can_dispute'])->toBeTrue();

    expect(Fixture::cosign($fixture)['code'])->toBe('REPORT_PUBLISHED');
    expect($publication->refresh()->published_reason)->toBe('signed')
        ->and(AuditReportSignature::query()->count())->toBe(1);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('approves an unanswered Flash report at the end of its window, without a signature', function (): void {
    $fixture = Fixture::ready();
    Fixture::seal($fixture);
    $store = app(AuditReportPublicationStore::class);
    $publication = AuditReportPublication::query()->sole();

    $this->travelTo($publication->due_at->subSecond());
    expect($store->advanceDue()['published'])->toBe(0);
    $this->travelTo($publication->due_at);
    expect(Fixture::cosign($fixture)['code'])->toBe('REPORT_WINDOW_CLOSED')
        ->and($store->advanceDue()['published'])->toBe(1);
    expect($publication->refresh()->status)->toBe('published')->and($publication->published_reason)->toBe('auto_approved')
        ->and(AuditReportSignature::query()->count())->toBe(0);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('keeps an earlier Flash report under the legacy all-signers rule, with no deadline', function (): void {
    $fixture = Fixture::ready(2);
    Fixture::sealLegacy($fixture);
    $publication = AuditReportPublication::query()->sole();
    expect($publication->policy_version)->toBe(MonthlyReportReview::LEGACY_POLICY)->and($publication->due_at)->toBeNull();

    $this->travel(3)->days();
    expect(app(AuditReportPublicationStore::class)->advanceDue()['published'])->toBe(0)
        ->and(Fixture::cosign($fixture)['code'])->toBe('REPORT_COSIGNATURE_RECORDED')
        ->and(Fixture::cosign($fixture, signer: 1, revision: 2)['code'])->toBe('REPORT_PUBLISHED');
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('allows the Flash review policy only on a Flash report', function (): void {
    $fixture = Fixture::ready(kind: 'monthly');
    $event = 'eloquent.creating: '.AuditReportPublication::class;
    Event::listen($event, fn (AuditReportPublication $publication) => $publication->policy_version = MonthlyReportReview::FLASH_POLICY);
    try {
        expect(fn () => Fixture::seal($fixture))->toThrow(QueryException::class, 'Flash review policy requires a Flash report');
    } finally {
        Event::forget($event);
    }
    $this->assertDatabaseCount('audit_report_publications', 0);
});

it('refuses to roll the Flash review migration back over Flash review history', function (): void {
    Fixture::seal(Fixture::ready());
    $migration = require database_path('migrations/2026_10_26_090000_add_flash_audit_review_policy.php');
    expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'Existing Flash review history requires a forward migration.');
});

it('round trips the Flash review migration while only legacy and monthly history exists', function (): void {
    $fixture = Fixture::ready(kind: 'monthly');
    Fixture::seal($fixture);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    $migration = require database_path('migrations/2026_10_26_090000_add_flash_audit_review_policy.php');
    $migration->down();
    $migration->up();
    expect(Fixture::cosign($fixture)['code'])->toBe('REPORT_PUBLISHED');
});
