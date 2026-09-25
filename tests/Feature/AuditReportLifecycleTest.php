<?php

declare(strict_types=1);

use App\Application\Auditor\AmendAuditReport;
use App\Application\Auditor\DecideAuditReport;
use App\Application\Auditor\FindAuditReportOperation;
use App\Application\Auditor\GetAuditReport;
use App\Application\Auditor\SaveAuditReportStep;
use App\Application\Auditor\StartAuditReport;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Models\AuditReport;
use App\Models\AuditReportVersion;
use App\Models\RoleMembership;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\AuditEngagementFixture;
use Tests\Support\BusinessQuoteFixture;

beforeEach(function (): void {
    $this->withoutVite();
    $this->travelTo(CarbonImmutable::parse('2026-09-25 10:00:00', 'UTC'));
});

/** @return array<string, mixed> */
function auditLifecycleFixture(string $kind = 'routine'): array
{
    $fixture = BusinessQuoteFixture::ready(auditKind: $kind);
    BusinessQuoteFixture::submit($fixture, BusinessQuoteFixture::acceptance($fixture));
    $actor = $fixture['audit']['partners'][0]['user'];
    $started = app(StartAuditReport::class)->handle($actor->id, 1, $fixture['assignment']->id,
        $fixture['assignment']->refresh()->revision, $fixture['application']->id, $fixture['application']->refresh()->revision, (string) Str::uuid());

    return [...$fixture, 'actor' => $actor, 'report' => AuditReport::query()->findOrFail($started['data']['audit_id'])];
}

it('returns incomplete monthly work and keeps its reason immutable on both transports', function (bool $api, bool $reject): void {
    $f = auditLifecycleFixture();
    $prefix = $api ? 'api.v1.auditor.' : 'auditor.';
    if ($api) {
        Sanctum::actingAs($f['actor'], ['auditor:read', 'auditor:command']);
    } else {
        $this->actingAs($f['actor']);
    }
    $id = $f['report']->id;
    $path = route($prefix.'reports.show', ['report' => $id]);
    $read = $this->get($path)->assertOk();
    $page = $api ? $read->json('data') : $read->viewData('page')['props'];
    expect($page['stage']['step'])->toBe('statements')->and($page['reason_options']['request_changes'])->toHaveCount(6)
        ->and($page['allowed_actions'])->toContain('audit.request_changes', 'audit.reject');
    $body = ['audit_id' => $id, 'expected_revision' => 1, 'identity_context_revision' => 1, 'request_id' => (string) Str::uuid(),
        'reason_code' => 'other', 'reason' => 'The original closing balance needs confirmation.'];
    $command = $reject ? 'audit.reject' : 'audit.request_changes';
    $action = $page['actions'][$reject ? 'reject' : 'request_changes']['url'];
    $receipt = $this->postJson($action, $body)->assertOk()->assertJsonPath('code', $reject ? 'AUDIT_REJECTED' : 'AUDIT_CHANGES_REQUESTED')->json();
    $this->postJson($action, $body)->assertOk()->assertJsonPath('operation_id', $receipt['operation_id']);
    $this->postJson($action, [...$body, 'reason' => 'A different reason.'])->assertConflict()->assertJsonPath('code', 'IDEMPOTENCY_CONFLICT');
    $this->getJson(route($prefix.'reports.operations.show', ['request_id' => $body['request_id'], 'command' => $command]))
        ->assertOk()->assertJsonPath('data', $receipt['data']);
    $read = $this->get($path)->assertOk();
    $page = $api ? $read->json('data') : $read->viewData('page')['props'];
    expect($page['stage'])->toMatchArray(['step' => 'returned', 'status' => $reject ? 'rejected' : 'changes_requested',
        'reason' => ['code' => 'other', 'label' => 'Other', 'explanation' => $body['reason']], 'recorded_at' => '2026-09-25T10:00:00Z', 'amended_by' => null])
        ->and($page['steps'][1]['state'])->toBe('todo')->and($page['can_continue'])->toBeFalse()
        ->and($page['reason_options'])->toBeNull()->and($page['allowed_actions'])->toBe(['audit.amend']);
    $original = $f['report']->refresh()->getAttributes();
    $child = $this->postJson($page['actions']['amend']['url'], ['audit_id' => $id, 'expected_revision' => 2,
        'identity_context_revision' => 1, 'request_id' => (string) Str::uuid()])->assertOk()->assertJsonPath('code', 'AUDIT_AMENDMENT_CREATED')->json('data');
    $this->get($child['next']['url'])->assertOk();
    $read = $this->get($path)->assertOk();
    $parent = $api ? $read->json('data') : $read->viewData('page')['props'];
    expect($parent['stage']['amended_by']['report_id'])->toBe($child['audit_id'])
        ->and($parent['actions']['amend'])->toBeNull()->and($parent['allowed_actions'])->toBe([])
        ->and($f['report']->refresh()->getAttributes())->toBe($original)
        ->and($f['assignment']->refresh()->revision)->toBe(2)
        ->and($f['application']->refresh()->status)->toBe('submitted');
    $this->assertDatabaseCount('audit_report_versions', 3);
    expect(fn () => DB::transaction(fn (): bool => $f['report']->forceFill(['revision' => 3, 'draft' => []])->save()))
        ->toThrow(QueryException::class, 'Final audit reports');
})->with([false, true])->with([false, true]);

it('records decision refusals and recovers field errors without changing history', function (bool $api): void {
    $f = auditLifecycleFixture();
    $prefix = $api ? 'api.v1.auditor.' : 'auditor.';
    if ($api) {
        Sanctum::actingAs($f['actor'], ['auditor:read', 'auditor:command']);
    } else {
        $this->actingAs($f['actor']);
    }
    $body = ['audit_id' => $f['report']->id, 'identity_context_revision' => 1, 'expected_revision' => 1,
        'request_id' => (string) Str::uuid(), 'reason_code' => 'other', 'reason' => ''];
    $result = $this->postJson(route($prefix.'reports.request-changes', ['report' => $f['report']->id]), $body)
        ->assertUnprocessable()->assertJsonPath('code', 'AUDIT_REPORT_DECISION_INVALID')->assertJsonValidationErrors('reason')->json();
    $this->getJson(route($prefix.'reports.operations.show', ['request_id' => $body['request_id'], 'command' => 'audit.request_changes']))
        ->assertUnprocessable()->assertJsonPath('operation_id', $result['operation_id'])->assertJsonValidationErrors('reason');
    $this->assertDatabaseCount('audit_report_versions', 1);
})->with([false, true]);

it('creates one linked amendment with fresh completion and retains old operation receipts after child edits', function (): void {
    $f = auditLifecycleFixture();
    $id = $f['report']->id;
    $actor = $f['actor']->id;
    $decisionId = (string) Str::uuid();
    $decision = app(DecideAuditReport::class)->handle($actor, 1, $id, 1, false, 'missing_originals', 'Missing original.', $decisionId);
    $oldBinding = $f['report']->refresh()->binding;
    $request = (string) Str::uuid();
    $amend = app(AmendAuditReport::class);
    $receipt = $amend->handle($actor, 1, $id, 2, $request);
    $childId = $receipt['data']['audit_id'];
    $child = AuditReport::query()->whereKey($childId)->firstOrFail();
    expect($child->binding['period'])->toBe($oldBinding['period'])->and($child->binding['application'])->toBe($oldBinding['application'])
        ->and($child->binding['amends']['id'])->toBe($id)->and($child->draft)->toBe(['note' => '', 'completed_steps' => [], 'fields' => []]);
    app(SaveAuditReportStep::class)->handle($actor, 1, $childId, 1, 'statements', [], (string) Str::uuid());
    expect($amend->handle($actor, 1, $id, 2, $request))->toBe($receipt)
        ->and(app(FindAuditReportOperation::class)->handle($actor, 1, 'audit.amend', $request))->toBe($receipt)
        ->and($amend->handle($actor, 1, $id, 2, (string) Str::uuid())['data']['audit_id'])->toBe($childId)
        ->and(app(DecideAuditReport::class)->handle($actor, 1, $id, 1, false, 'missing_originals', 'Missing original.', $decisionId))->toBe($decision);
    expect(fn () => $amend->handle($actor, 1, $id, 1, $request))->toThrow(CommandRejection::class, 'IDEMPOTENCY_CONFLICT');
    $this->assertDatabaseCount('audit_reports', 2);
    $this->assertDatabaseCount('audit_report_versions', 4);
    $migration = require database_path('migrations/2026_09_25_114139_enforce_audit_report_amendment_lineage.php');
    expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'Existing audit amendment history requires a forward migration.');
});

it('refuses stale or inappropriate lifecycle commands without a new version', function (): void {
    $f = auditLifecycleFixture('flash');
    $actor = $f['actor']->id;
    $id = $f['report']->id;
    $decide = app(DecideAuditReport::class);
    $amend = app(AmendAuditReport::class);
    expect($decide->handle($actor, 1, $id, 0, true, 'other', 'Factual reason.', (string) Str::uuid())['code'])->toBe('VERSION_CONFLICT')
        ->and($decide->handle($actor, 1, $id, 1, true, 'other', 'Factual reason.', (string) Str::uuid())['code'])->toBe('AUDIT_REPORT_DECISION_NOT_ALLOWED')
        ->and($amend->handle($actor, 1, $id, 0, (string) Str::uuid())['code'])->toBe('VERSION_CONFLICT')
        ->and($amend->handle($actor, 1, $id, 1, (string) Str::uuid())['code'])->toBe('AUDIT_REPORT_NOT_AMENDABLE');
    $this->assertDatabaseCount('audit_report_versions', 1);
});

it('reauthorizes decisions amendments and old receipts after revocation', function (): void {
    $f = auditLifecycleFixture();
    $actor = $f['actor']->id;
    $id = $f['report']->id;
    $request = (string) Str::uuid();
    app(DecideAuditReport::class)->handle($actor, 1, $id, 1, false, 'other', 'Missing original.', $request);
    RoleMembership::query()->where('party_id', $f['actor']->party_id)->update(['status' => 'revoked']);
    expect(fn () => app(DecideAuditReport::class)->handle($actor, 1, $id, 1, false, 'other', 'Missing original.', $request))->toThrow(IdentityViolation::class)
        ->and(fn () => app(AmendAuditReport::class)->handle($actor, 1, $id, 2, (string) Str::uuid()))->toThrow(IdentityViolation::class)
        ->and(fn () => app(FindAuditReportOperation::class)->handle($actor, 1, 'audit.request_changes', $request))->toThrow(IdentityViolation::class);
    $this->assertDatabaseCount('audit_reports', 1);
});

it('rolls a decision or amendment back with its journal when version retention fails', function (bool $amend): void {
    $f = auditLifecycleFixture();
    $actor = $f['actor']->id;
    $id = $f['report']->id;
    if ($amend) {
        app(DecideAuditReport::class)->handle($actor, 1, $id, 1, false, 'other', 'Missing original.', (string) Str::uuid());
    }
    $before = $f['report']->refresh()->getAttributes();
    $request = (string) Str::uuid();
    AuditReportVersion::creating(static function (): never {
        throw new RuntimeException('Version retention failed.');
    });
    try {
        expect(fn () => $amend ? app(AmendAuditReport::class)->handle($actor, 1, $id, 2, $request)
            : app(DecideAuditReport::class)->handle($actor, 1, $id, 1, false, 'other', 'Missing original.', $request))
            ->toThrow(RuntimeException::class, 'Version retention failed.');
    } finally {
        Event::forget('eloquent.creating: '.AuditReportVersion::class);
    }
    expect($f['report']->refresh()->getAttributes())->toBe($before);
    $this->assertDatabaseCount('audit_reports', 1);
    $this->assertDatabaseMissing('command_operations', ['request_id' => $request]);
})->with([false, true]);

it('pins newly accepted engagement terms when amending after a release change', function (): void {
    $f = auditLifecycleFixture();
    $actor = $f['actor'];
    $id = $f['report']->id;
    app(DecideAuditReport::class)->handle($actor->id, 1, $id, 1, true, 'procedure_incomplete', 'Missing original.', (string) Str::uuid());
    $next = AuditEngagementFixture::release($f['audit']['staff'], 1);
    AuditEngagementFixture::accept($actor, $next);
    $amended = app(AmendAuditReport::class)->handle($actor->id, 1, $id, 2, (string) Str::uuid());
    $child = AuditReport::query()->whereKey($amended['data']['audit_id'])->firstOrFail();
    expect($child->engagement_acceptance_id)->not->toBe($f['report']->engagement_acceptance_id)
        ->and($child->binding['engagement']['release_id'])->toBe($next->id)
        ->and(app(GetAuditReport::class)->handle($actor->id, 1, $id)['status'])->toBe('rejected');
});

it('amends a retained sealed Flash version without copying completion or changing the original', function (): void {
    $f = auditLifecycleFixture('flash');
    $report = $f['report'];
    $report->forceFill(['status' => 'sealed', 'step' => 'seal', 'revision' => 2])->save();
    AuditReportVersion::factory()->forReport($report, $f['actor']->party_id, $f['actor']->id)->create();
    $before = $report->refresh()->getAttributes();
    $receipt = app(AmendAuditReport::class)->handle($f['actor']->id, 1, $report->id, 2, (string) Str::uuid());
    $child = AuditReport::query()->whereKey($receipt['data']['audit_id'])->firstOrFail();
    expect($child->step)->toBe('review')->and($child->draft['completed_steps'])->toBe([])
        ->and($report->refresh()->getAttributes())->toBe($before);
});

it('refuses lifecycle HTTP commands for read-only tokens and mismatched report targets', function (): void {
    $f = auditLifecycleFixture();
    $path = route('api.v1.auditor.reports.request-changes', ['report' => $f['report']->id]);
    $body = ['audit_id' => $f['report']->id, 'expected_revision' => 1, 'identity_context_revision' => 1,
        'request_id' => (string) Str::uuid(), 'reason_code' => 'other', 'reason' => 'Missing original.'];
    Sanctum::actingAs($f['actor'], ['auditor:read']);
    $this->postJson($path, $body)->assertForbidden();
    $this->getJson(route('api.v1.auditor.reports.show', ['report' => $f['report']->id]))->assertOk()
        ->assertJsonPath('data.allowed_actions', [])->assertJsonPath('data.actions.request_changes', null);
    Sanctum::actingAs($f['actor'], ['auditor:read', 'auditor:command']);
    $this->postJson($path, [...$body, 'audit_id' => (string) Str::ulid()])->assertUnprocessable()->assertJsonValidationErrors('audit_id');
    $this->postJson($path, [...$body, 'identity_context_revision' => 0])->assertConflict();
    $this->assertDatabaseCount('audit_report_versions', 1);
});

it('refuses to enable lineage protection over an inconsistent legacy amendment without rewriting history', function (): void {
    $f = auditLifecycleFixture();
    $migration = require database_path('migrations/2026_09_25_114139_enforce_audit_report_amendment_lineage.php');
    $migration->down();
    $invalid = $f['report']->replicate();
    $invalid->forceFill(['amends_id' => $f['report']->id])->save();
    $before = $invalid->refresh()->getAttributes();
    expect(fn () => $migration->up())->toThrow(RuntimeException::class, 'Existing audit amendment lineage must be reconciled before protection.')
        ->and($invalid->refresh()->getAttributes())->toBe($before);
});
