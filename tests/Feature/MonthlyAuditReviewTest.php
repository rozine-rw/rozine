<?php

declare(strict_types=1);

use App\Application\Auditor\AmendAuditReport;
use App\Application\Auditor\Contracts\AuditReportPublicationStore;
use App\Application\Auditor\GetBusinessAuditReport;
use App\Domain\Auditor\MonthlyReportReview;
use App\Models\AuditDisputeProof;
use App\Models\AuditPublicationEvent;
use App\Models\AuditReportPublication;
use App\Models\AuditReportSignature;
use App\Models\AuditSigningKeyRevocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\AuditSealingFixture as Fixture;

beforeEach(function (): void {
    $this->freezeSecond();
    $this->withoutVite();
});

/** @param array<string, mixed> $fixture
 * @return array<string, mixed>
 */
function monthlyReviewInput(array $fixture, int $revision = 1): array
{
    return ['request_id' => (string) Str::uuid(), 'identity_context_revision' => 1, 'expected_revision' => $revision,
        'report_revision' => $fixture['report']->revision + 1, 'mandate_version' => 1,
        'digest' => $fixture['fields']['digest'], 'supporting_text' => 'The stock count excludes the retained delivery receipt.'];
}

it('pins a monthly delivery window and accepts one real signature without altering Flash', function (): void {
    $fixture = Fixture::ready(2, 'monthly');
    expect(Fixture::seal($fixture)['code'])->toBe('AUDIT_SEALED');
    $publication = AuditReportPublication::query()->firstOrFail();
    expect($publication->policy_version)->toBe(MonthlyReportReview::POLICY)
        ->and($publication->due_at->diffInHours($publication->delivered_at, true))->toBe(24.0)
        ->and(Fixture::cosign($fixture)['code'])->toBe('REPORT_PUBLISHED');
    $publication->refresh();
    expect($publication->published_reason)->toBe('signed')->and($publication->revision)->toBe(2)
        ->and(AuditReportSignature::query()->count())->toBe(1)->and(AuditPublicationEvent::query()->count())->toBe(2);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('publishes exactly at the due instant without fabricating a signature and retries do not move delivery', function (): void {
    $fixture = Fixture::ready(kind: 'monthly');
    Fixture::seal($fixture);
    $publication = AuditReportPublication::query()->firstOrFail();
    $due = $publication->due_at;
    $store = app(AuditReportPublicationStore::class);
    $this->travelTo($due->subSecond());
    expect($store->advanceDue()['published'])->toBe(0);
    $this->travelTo($due);
    expect(Fixture::cosign($fixture)['code'])->toBe('REPORT_WINDOW_CLOSED')
        ->and($store->advanceDue()['published'])->toBe(1)->and($store->advanceDue()['published'])->toBe(0);
    $page = app(GetBusinessAuditReport::class)->handle($fixture['audit']['authority']['users'][0]->id, 1, $fixture['audit']['business'], $fixture['report']->id);
    expect($page['cosign']['published_reason'])->toBe('auto_approved')->and($page['cosign']['signed_count'])->toBe(0)
        ->and($page['cosign']['state'])->toBe('pending')->and($publication->refresh()->due_at->eq($due))->toBeTrue()
        ->and($store->forAuditor($fixture['report']->id)['cosign']['signed_at'])->toBeNull();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('freezes a disputed report, retains protected original proof and escalates CPA uphold to staff', function (): void {
    $fixture = Fixture::ready(kind: 'monthly');
    Fixture::seal($fixture);
    $store = app(AuditReportPublicationStore::class);
    $owner = $fixture['audit']['authority']['users'][0];
    $input = monthlyReviewInput($fixture);
    $files = [['filename' => 'private-name.pdf', 'content' => "%PDF-1.4\nSynthetic retained proof\n%%EOF"]];
    $result = $store->dispute($owner->id, $fixture['audit']['business'], $fixture['report']->id, $input, $files);
    expect($result['code'])->toBe('REPORT_DISPUTED')
        ->and($store->dispute($owner->id, $fixture['audit']['business'], $fixture['report']->id, $input, $files))->toBe($result);
    $proof = AuditDisputeProof::query()->firstOrFail();
    expect($proof->getRawOriginal('content'))->not->toContain('Synthetic retained proof')
        ->and($store->proof($owner->id, 1, 'business', $fixture['audit']['business'], $fixture['report']->id, $proof->id)['content'])->toBe($files[0]['content']);
    $this->travel(25)->hours();
    expect($store->advanceDue()['published'])->toBe(0);
    $upheld = $store->uphold($fixture['user']->id, $fixture['report']->id, [...$input, 'request_id' => (string) Str::uuid(), 'expected_revision' => 2, 'reason' => 'The receipt postdates this reporting period.']);
    expect($upheld['code'])->toBe('REPORT_DISPUTE_ESCALATED')->and(AuditReportPublication::query()->firstOrFail()->status)->toBe('escalated');
    $amend = app(AmendAuditReport::class)->handle($fixture['user']->id, 1, $fixture['report']->id, $fixture['report']->revision + 1, (string) Str::uuid());
    expect($amend['code'])->toBe('REPORT_DISPUTE_STAFF_REVIEW_REQUIRED');
    $resolved = $store->staffDecision($fixture['audit']['staff']->id, $fixture['assignment']->id, $fixture['report']->id, 'audit.dispute.resolve',
        [...$input, 'request_id' => (string) Str::uuid(), 'expected_revision' => 3, 'reason' => 'Independent retained evidence confirms the period cutoff.', 'decision' => 'uphold']);
    expect($resolved['code'])->toBe('REPORT_PUBLISHED')->and(AuditReportPublication::query()->firstOrFail()->published_reason)->toBe('staff_resolved')
        ->and(AuditReportSignature::query()->count())->toBe(0);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('refuses automatic publication when current seal authority has been withdrawn', function (): void {
    $fixture = Fixture::ready(kind: 'monthly');
    Fixture::seal($fixture);
    AuditSigningKeyRevocation::factory()->create(['audit_signing_key_id' => $fixture['key']->id]);
    $this->travel(25)->hours();
    $result = app(AuditReportPublicationStore::class)->advanceDue();
    expect($result['published'])->toBe(0)->and($result['blocked'])->toHaveCount(1)
        ->and(AuditReportPublication::query()->firstOrFail()->status)->toBe('pending');
});
