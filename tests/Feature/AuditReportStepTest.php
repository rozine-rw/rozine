<?php

declare(strict_types=1);

use App\Application\Auditor\FindAuditReportOperation;
use App\Application\Auditor\GetAuditReport;
use App\Application\Auditor\SaveAuditReportStep;
use App\Application\Auditor\StartAuditReport;
use App\Application\Operations\Contracts\OperationJournal;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Models\AuditReport;
use App\Models\AuditReportVersion;
use App\Models\CommandOperation;
use App\Models\RoleMembership;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Support\BusinessQuoteFixture as Fixture;

/** @return array<string, mixed> */
function procedureReportFixture(string $kind = 'flash'): array
{
    $fixture = Fixture::ready(auditKind: $kind);
    Fixture::submit($fixture, Fixture::acceptance($fixture));
    $user = $fixture['audit']['partners'][0]['user'];
    $result = app(StartAuditReport::class)->handle($user->id, 1, $fixture['assignment']->id,
        $fixture['assignment']->refresh()->revision, $fixture['application']->id, $fixture['application']->refresh()->revision, (string) Str::uuid());

    return [...$fixture, 'auditor' => $user, 'report_id' => $result['data']['audit_id']];
}

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-09-25 10:00:00', 'UTC'));
});

it('saves report-only revisions and recovers the immutable original outcome after later work', function (): void {
    $fixture = procedureReportFixture();
    $assignmentRevision = $fixture['assignment']->refresh()->revision;
    $action = app(SaveAuditReportStep::class);
    $request = (string) Str::uuid();
    $saved = $action->handle($fixture['auditor']->id, 1, $fixture['report_id'], 1, 'review', [], $request);
    expect($saved['code'])->toBe('AUDIT_STEP_SAVED')->and($saved['revision'])->toBe(2)->and($saved['data']['step'])->toBe('check_in');
    $report = app(GetAuditReport::class)->handle($fixture['auditor']->id, 1, $fixture['report_id']);
    expect($report['draft']['completed_steps'])->toBe(['review'])->and($report['revision'])->toBe(2)
        ->and($fixture['assignment']->refresh()->revision)->toBe($assignmentRevision);
    $action->handle($fixture['auditor']->id, 1, $fixture['report_id'], 2, 'review', [], (string) Str::uuid());
    expect($action->handle($fixture['auditor']->id, 1, $fixture['report_id'], 1, 'review', [], $request))->toBe($saved)
        ->and(app(FindAuditReportOperation::class)->handle($fixture['auditor']->id, 1, 'audit.save_step', $request))->toBe($saved);
    $this->assertDatabaseCount('audit_report_versions', 3);
    expect(fn () => $action->handle($fixture['auditor']->id, 1, $fixture['report_id'], 1, 'review', ['rating' => 'A'], $request))
        ->toThrow(CommandRejection::class, 'IDEMPOTENCY_CONFLICT');
});

it('journals step and revision refusals without changing the report', function (int $revision, string $step, array $fields, string $code): void {
    $fixture = procedureReportFixture();
    $request = (string) Str::uuid();
    $before = AuditReport::query()->whereKey($fixture['report_id'])->firstOrFail()->getAttributes();
    $result = app(SaveAuditReportStep::class)->handle($fixture['auditor']->id, 1, $fixture['report_id'], $revision, $step, $fields, $request);
    expect($result['status'])->toBe('rejected')->and($result['code'])->toBe($code)
        ->and(app(FindAuditReportOperation::class)->handle($fixture['auditor']->id, 1, 'audit.save_step', $request))->toBe($result)
        ->and(AuditReport::query()->whereKey($fixture['report_id'])->firstOrFail()->getAttributes())->toBe($before);
    $this->assertDatabaseCount('audit_report_versions', 1);
})->with([
    [0, 'review', [], 'VERSION_CONFLICT'],
    [1, 'seal', ['note' => 'Skip'], 'AUDIT_STEP_NOT_AVAILABLE'],
    [1, 'review', ['capacity' => '100000000'], 'AUDIT_STEP_INPUT_INVALID'],
]);

it('uses the current verified statement pin for the monthly step and never fabricates capture', function (): void {
    $fixture = procedureReportFixture('routine');
    $result = app(SaveAuditReportStep::class)->handle($fixture['auditor']->id, 1, $fixture['report_id'], 1, 'statements', [], (string) Str::uuid());
    expect($result['code'])->toBe('AUDIT_STEP_SAVED')->and($result['data']['step'])->toBe('count');
    $report = app(GetAuditReport::class)->handle($fixture['auditor']->id, 1, $fixture['report_id']);
    expect($report['draft']['sources']['statements']['verification']['sha256'])->toHaveLength(64);
    $flash = procedureReportFixture();
    $save = app(SaveAuditReportStep::class);
    $save->handle($flash['auditor']->id, 1, $flash['report_id'], 1, 'review', [], (string) Str::uuid());
    $blocked = $save->handle($flash['auditor']->id, 1, $flash['report_id'], 2, 'check_in', [], (string) Str::uuid());
    expect($blocked['code'])->toBe('AUDIT_CAPTURE_REQUIRED')->and($blocked['status'])->toBe('rejected')
        ->and(AuditReport::query()->whereKey($flash['report_id'])->firstOrFail()->revision)->toBe(2);
});

it('requires current authority for saves, replays and lookups without exposing another report', function (): void {
    $fixture = procedureReportFixture();
    $other = procedureReportFixture();
    $action = app(SaveAuditReportStep::class);
    foreach ([$other['report_id'], (string) Str::ulid()] as $id) {
        expect(fn () => $action->handle($fixture['auditor']->id, 1, $id, 1, 'review', [], (string) Str::uuid()))
            ->toThrow(CommandRejection::class, 'AUDIT_REPORT_NOT_FOUND');
    }
    $request = (string) Str::uuid();
    $action->handle($fixture['auditor']->id, 1, $fixture['report_id'], 1, 'review', [], $request);
    RoleMembership::query()->where('party_id', $fixture['audit']['partners'][0]['party']->id)->update(['status' => 'revoked']);
    expect(fn () => $action->handle($fixture['auditor']->id, 1, $fixture['report_id'], 1, 'review', [], $request))->toThrow(IdentityViolation::class)
        ->and(fn () => app(FindAuditReportOperation::class)->handle($fixture['auditor']->id, 1, 'audit.save_step', $request))->toThrow(IdentityViolation::class);
});

it('rolls back a step when its immutable history cannot be appended', function (): void {
    $fixture = procedureReportFixture();
    $request = (string) Str::uuid();
    $event = 'eloquent.creating: '.AuditReportVersion::class;
    Event::listen($event, function (): void {
        throw new RuntimeException('History unavailable');
    });
    try {
        expect(fn () => app(SaveAuditReportStep::class)->handle($fixture['auditor']->id, 1, $fixture['report_id'], 1, 'review', [], $request))
            ->toThrow(RuntimeException::class, 'History unavailable');
    } finally {
        Event::forget($event);
    }
    expect(AuditReport::query()->whereKey($fixture['report_id'])->firstOrFail()->revision)->toBe(1)
        ->and(CommandOperation::query()->where('request_id', $request)->exists())->toBeFalse();
    $this->assertDatabaseCount('audit_report_versions', 1);
});

it('refuses edits to a terminal report and refuses a receipt from another target scope', function (): void {
    $fixture = procedureReportFixture();
    $user = $fixture['auditor'];
    $report = AuditReport::query()->whereKey($fixture['report_id'])->firstOrFail();
    $report->forceFill(['revision' => 2, 'status' => 'rejected'])->save();
    AuditReportVersion::factory()->forReport($report, $user->party_id, $user->id)->create();
    $before = $report->refresh()->getRawOriginal();
    $result = app(SaveAuditReportStep::class)->handle($user->id, 1, $report->id, 2, 'review', [], (string) Str::uuid());
    expect($result['code'])->toBe('AUDIT_REPORT_NOT_EDITABLE')->and($report->refresh()->getRawOriginal())->toBe($before);
    $request = (string) Str::uuid();
    app(OperationJournal::class)->execute('party:'.$user->party_id, $user->id, 'audit.save_step', $request, 'another.target', $report->id, [],
        function (): void {}, fn (): OperationResult => new OperationResult('OTHER_EFFECT', [], 1));
    expect(fn () => app(FindAuditReportOperation::class)->handle($user->id, 1, 'audit.save_step', $request))
        ->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND');
    $this->assertDatabaseCount('audit_report_versions', 2);
});

it('keeps the original reporting cycle when the report is first started in a later month', function (): void {
    $fixture = Fixture::ready(auditKind: 'routine');
    Fixture::submit($fixture, Fixture::acceptance($fixture));
    $this->travelTo(CarbonImmutable::parse('2026-10-01 01:00:00', 'UTC'));
    $user = $fixture['audit']['partners'][0]['user'];
    $started = app(StartAuditReport::class)->handle($user->id, 1, $fixture['assignment']->id, $fixture['assignment']->refresh()->revision,
        $fixture['application']->id, $fixture['application']->refresh()->revision, (string) Str::uuid());
    $report = app(GetAuditReport::class)->handle($user->id, 1, $started['data']['audit_id']);
    expect($report['period'])->toBe('2026-08')->and($report['revision'])->toBe(1);
});
