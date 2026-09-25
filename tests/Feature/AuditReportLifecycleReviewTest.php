<?php

declare(strict_types=1);

use App\Application\Auditor\AmendAuditReport;
use App\Application\Auditor\DecideAuditReport;
use App\Application\Auditor\FindAuditReportOperation;
use App\Application\Auditor\GetAuditReport;
use App\Application\Auditor\StartAuditReport;
use App\Application\Business\WithAuditApplicationBinding;
use App\Domain\Operations\CommandRejection;
use App\Models\AuditReport;
use App\Models\AuditReportVersion;
use App\Models\CommandOperation;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\AuditAssignmentFixture;
use Tests\Support\AuditEngagementFixture;
use Tests\Support\BusinessQuoteFixture;

beforeEach(function (): void {
    $this->withoutVite();
    $this->travelTo(CarbonImmutable::parse('2026-09-25 10:00:00', 'UTC'));
});

/** @return array<string, mixed> */
function auditLifecycleReviewFixture(string $kind = 'routine'): array
{
    $fixture = BusinessQuoteFixture::ready(auditKind: $kind);
    BusinessQuoteFixture::submit($fixture, BusinessQuoteFixture::acceptance($fixture));
    $actor = $fixture['audit']['partners'][0]['user'];
    $started = app(StartAuditReport::class)->handle($actor->id, 1, $fixture['assignment']->id,
        $fixture['assignment']->refresh()->revision, $fixture['application']->id, $fixture['application']->refresh()->revision, (string) Str::uuid());

    return [...$fixture, 'actor' => $actor, 'report' => AuditReport::query()->findOrFail($started['data']['audit_id'])];
}

it('gives a replaced Auditor and the successor no path to a returned parent, its decision receipt or an amendment', function (): void {
    $f = auditLifecycleReviewFixture();
    $original = $f['actor'];
    $id = $f['report']->id;
    $decision = (string) Str::uuid();
    app(DecideAuditReport::class)->handle($original->id, 1, $id, 1, false, 'other', 'Missing original.', $decision);
    $replacement = AuditAssignmentFixture::make(1)['partners'][0];
    AuditAssignmentFixture::independence($f['audit']['staff'], $f['audit']['business'], $replacement['party']->id);
    AuditAssignmentFixture::respond($original, $f['assignment']->refresh(), 'conflict', 'New family tie.', 'family_or_business');
    AuditAssignmentFixture::respond($replacement['user'], $f['assignment']->refresh());
    expect($f['assignment']->refresh()->party_id)->toBe($replacement['party']->id);
    $parent = $f['report']->refresh()->getAttributes();
    $versions = AuditReportVersion::query()->count();
    $journal = CommandOperation::query()->count();

    foreach ([$original, $replacement['user']] as $user) {
        expect(fn () => app(AmendAuditReport::class)->handle($user->id, 1, $id, 2, (string) Str::uuid()))
            ->toThrow(CommandRejection::class, 'AUDIT_REPORT_NOT_FOUND')
            ->and(fn () => app(DecideAuditReport::class)->handle($user->id, 1, $id, 2, true, 'other', 'Unverifiable.', (string) Str::uuid()))
            ->toThrow(CommandRejection::class, 'AUDIT_REPORT_NOT_FOUND')
            ->and(fn () => app(GetAuditReport::class)->handle($user->id, 1, $id))
            ->toThrow(CommandRejection::class, 'AUDIT_REPORT_NOT_FOUND');
        $this->actingAs($user)->postJson(route('auditor.reports.amend', ['report' => $id]), ['audit_id' => $id, 'expected_revision' => 2,
            'identity_context_revision' => 1, 'request_id' => (string) Str::uuid()])->assertNotFound()->assertJsonPath('code', 'AUDIT_REPORT_NOT_FOUND');
    }
    expect(fn () => app(FindAuditReportOperation::class)->handle($original->id, 1, 'audit.request_changes', $decision))
        ->toThrow(CommandRejection::class, 'AUDIT_REPORT_NOT_FOUND')
        ->and(fn () => app(FindAuditReportOperation::class)->handle($replacement['user']->id, 1, 'audit.request_changes', $decision))
        ->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND')
        ->and($f['report']->refresh()->getAttributes())->toBe($parent)
        ->and(AuditReport::query()->where('amends_id', $id)->exists())->toBeFalse()
        ->and(AuditReportVersion::query()->count())->toBe($versions)
        ->and(CommandOperation::query()->count())->toBe($journal);
});

it('refuses amendment, decision, parent reads and receipt lookups during an engagement lapse without journaling', function (): void {
    $f = auditLifecycleReviewFixture();
    $actor = $f['actor'];
    $id = $f['report']->id;
    $decision = (string) Str::uuid();
    app(DecideAuditReport::class)->handle($actor->id, 1, $id, 1, false, 'other', 'Missing original.', $decision);
    AuditEngagementFixture::release($f['audit']['staff'], 1);
    $journal = CommandOperation::query()->count();
    $versions = AuditReportVersion::query()->count();
    $this->actingAs($actor);

    $this->postJson(route('auditor.reports.amend', ['report' => $id]), ['audit_id' => $id, 'expected_revision' => 2,
        'identity_context_revision' => 1, 'request_id' => (string) Str::uuid()])
        ->assertForbidden()->assertJsonPath('code', 'AUDIT_ENGAGEMENT_ACCEPTANCE_REQUIRED');
    $this->postJson(route('auditor.reports.reject', ['report' => $id]), ['audit_id' => $id, 'expected_revision' => 2,
        'identity_context_revision' => 1, 'request_id' => (string) Str::uuid(), 'reason_code' => 'other', 'reason' => 'Unverifiable.'])
        ->assertForbidden()->assertJsonPath('code', 'AUDIT_ENGAGEMENT_ACCEPTANCE_REQUIRED');
    $this->getJson(route('auditor.reports.show', ['report' => $id]))->assertForbidden();
    $this->getJson(route('auditor.reports.operations.show', ['request_id' => $decision, 'command' => 'audit.request_changes']))->assertForbidden();

    expect(CommandOperation::query()->count())->toBe($journal)
        ->and(AuditReportVersion::query()->count())->toBe($versions)
        ->and(AuditReport::query()->count())->toBe(1);
});

it('withdraws only the amendment when its Auditor declares a conflict and leaves the rejected parent unchanged', function (): void {
    $f = auditLifecycleReviewFixture();
    $actor = $f['actor'];
    $id = $f['report']->id;
    app(DecideAuditReport::class)->handle($actor->id, 1, $id, 1, true, 'evidence_unverifiable', 'Unverifiable.', (string) Str::uuid());
    $parent = $f['report']->refresh()->getAttributes();
    $child = app(AmendAuditReport::class)->handle($actor->id, 1, $id, 2, (string) Str::uuid())['data']['audit_id'];

    AuditAssignmentFixture::respond($actor, $f['assignment']->refresh(), 'conflict', 'New family tie.', 'family_or_business');

    $withdrawn = AuditReport::query()->whereKey($child)->firstOrFail();
    expect($withdrawn->status)->toBe('withdrawn')->and($withdrawn->revision)->toBe(2)
        ->and($withdrawn->draft['withdrawal']['reason_code'])->toBe('AUDITOR_CONFLICT')
        ->and($f['report']->refresh()->getAttributes())->toBe($parent)
        ->and(AuditReportVersion::query()->where('audit_report_id', $id)->count())->toBe(2);
});

it('counts the reason limit in characters and keeps unfinished step states when returning early', function (): void {
    $f = auditLifecycleReviewFixture();
    $actor = $f['actor'];
    $id = $f['report']->id;
    $decide = app(DecideAuditReport::class);

    $tooLong = $decide->handle($actor->id, 1, $id, 1, false, 'other', str_repeat('é', 2001), (string) Str::uuid());
    expect($tooLong['code'])->toBe('AUDIT_REPORT_DECISION_INVALID')->and($tooLong['field_errors'])->toHaveKey('reason')
        ->and($decide->handle($actor->id, 1, $id, 1, false, 'other', str_repeat('é', 2000), (string) Str::uuid())['code'])->toBe('AUDIT_CHANGES_REQUESTED');

    $report = $f['report']->refresh();
    expect($report->status)->toBe('changes_requested')->and($report->step)->toBe('statements')
        ->and($report->draft['completed_steps'])->toBe([])->and(mb_strlen($report->draft['decision']['explanation']))->toBe(2000);
    $this->actingAs($actor);
    $steps = $this->get(route('auditor.reports.show', ['report' => $id]))->assertOk()->viewData('page')['props']['steps'];
    expect(array_column($steps, 'state'))->toBe(['current', 'todo', 'todo', 'todo']);
});

it('refuses at the database an amendment whose kind differs from its terminal parent', function (): void {
    $f = auditLifecycleReviewFixture();
    app(DecideAuditReport::class)->handle($f['actor']->id, 1, $f['report']->id, 1, true, 'procedure_incomplete', 'Incomplete.', (string) Str::uuid());
    [$assignment, $binding] = app(WithAuditApplicationBinding::class)->handle($f['actor']->id, 1, $f['assignment']->id, $f['application']->id,
        fn (array $assignment, array $binding): array => [$assignment, $binding]);
    $factory = AuditReport::factory()->forBinding($assignment, $binding);

    expect(fn () => DB::transaction(fn (): AuditReport => $factory->create(['amends_id' => $f['report']->id, 'kind' => 'flash', 'step' => 'review'])))
        ->toThrow(QueryException::class, 'identical submitted source lineage')
        ->and(AuditReport::query()->where('amends_id', $f['report']->id)->exists())->toBeFalse();

    $child = $factory->create(['amends_id' => $f['report']->id]);
    expect($child->kind)->toBe('monthly')->and($child->amends_id)->toBe($f['report']->id);
});
