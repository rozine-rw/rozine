<?php

declare(strict_types=1);

use App\Application\Auditor\FindAuditReportOperation;
use App\Application\Auditor\GetAssignmentAuditReport;
use App\Application\Auditor\GetAuditReport;
use App\Application\Auditor\ResolveAuditAssignment;
use App\Application\Auditor\StartAuditReport;
use App\Application\Identity\Contracts\IdentityRepository;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Models\AuditReport;
use App\Models\AuditReportVersion;
use App\Models\CommandOperation;
use App\Models\RoleMembership;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Support\AuditAssignmentFixture;
use Tests\Support\AuditEngagementFixture;
use Tests\Support\AuditorFixture;
use Tests\Support\AuditSealingFixture;
use Tests\Support\BusinessQuoteFixture as Fixture;

/** @param array<string, mixed> $fixture
 * @return array<string, mixed>
 */
function startSubmittedAuditReport(array $fixture, ?string $requestId = null, ?int $assignmentRevision = null, ?int $applicationRevision = null, ?string $applicationId = null): array
{
    return app(StartAuditReport::class)->handle($fixture['audit']['partners'][0]['user']->id, 1, $fixture['assignment']->id,
        $assignmentRevision ?? $fixture['assignment']->refresh()->revision, $applicationId ?? $fixture['application']->id,
        $applicationRevision ?? $fixture['application']->refresh()->revision, $requestId ?? (string) Str::uuid());
}

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-09-25 10:00:00', 'UTC'));
});

it('starts one source-bound report through an explicit replayable command and exposes no private source facts', function (string $kind, string $step): void {
    $fixture = Fixture::ready(auditKind: $kind);
    Fixture::submit($fixture, Fixture::acceptance($fixture));
    $request = (string) Str::uuid();
    $user = $fixture['audit']['partners'][0]['user'];
    $count = CommandOperation::query()->count();
    expect(app(GetAssignmentAuditReport::class)->handle($user->id, 1, $fixture['assignment']->id))->toBeNull();
    $this->assertDatabaseCount('audit_reports', 0);
    $this->assertDatabaseCount('command_operations', $count);
    $first = startSubmittedAuditReport($fixture, $request);
    $report = AuditReport::query()->firstOrFail();
    expect(json_encode($report->binding, JSON_THROW_ON_ERROR))->not->toContain('scorecard', 'credit_source_reference', 'actor_user_id',
        'actor_party_id', 'recurring_owner_draw', 'obligations', 'pricing', 'capacity', 'payload', 'terms', 'draft');
    expect($first['code'])->toBe('AUDIT_REPORT_STARTED')
        ->and($first['data'])->toBe(['audit_id' => $report->id, 'assignment_id' => $fixture['assignment']->id])
        ->and($first['revision'])->toBe(1)
        ->and(startSubmittedAuditReport($fixture, $request))->toBe($first)
        ->and(app(FindAuditReportOperation::class)->handle($user->id, 1, 'audit.start', $request))->toBe($first);
    $view = app(GetAuditReport::class)->handle($user->id, 1, $report->id);
    expect($view['step'])->toBe($step)->and($view['kind'])->toBe($kind === 'routine' ? 'monthly' : 'flash')
        ->and($view['draft']['note'])->toBe('')
        ->and($view['application_id'])->toBe($fixture['application']->id)
        ->and($view['application_revision'])->toBe($fixture['application']->refresh()->revision)
        ->and($view)->not->toHaveKeys(['binding', 'quote', 'credit', 'result'])
        ->and(app(GetAssignmentAuditReport::class)->handle($user->id, 1, $fixture['assignment']->id))->toBe($view);
    $resumed = startSubmittedAuditReport($fixture);
    expect($resumed['code'])->toBe('AUDIT_REPORT_RESUMED')->and($resumed['data'])->toBe($first['data']);
    $this->assertDatabaseCount('audit_reports', 1);
    $this->assertDatabaseCount('audit_report_versions', 1);
    expect(fn () => startSubmittedAuditReport($fixture, $request, applicationRevision: 1))->toThrow(CommandRejection::class, 'IDEMPOTENCY_CONFLICT');
})->with([['flash', 'review'], ['routine', 'statements']]);

it('records pin and submission refusals without starting or rewriting a report', function (string $fault, string $code): void {
    $fixture = Fixture::ready();
    if ($fault !== 'draft') {
        Fixture::submit($fixture, Fixture::acceptance($fixture));
    }
    $request = (string) Str::uuid();
    $result = startSubmittedAuditReport($fixture, $request, $fault === 'assignment' ? 1 : null, $fault === 'application' ? 1 : null);
    expect($result['status'])->toBe('rejected')->and($result['code'])->toBe($code)
        ->and(app(FindAuditReportOperation::class)->handle($fixture['audit']['partners'][0]['user']->id, 1, 'audit.start', $request))->toBe($result);
    $this->assertDatabaseCount('audit_reports', 0);
    $this->assertDatabaseCount('audit_report_versions', 0);
})->with([['draft', 'APPLICATION_NOT_SUBMITTED'], ['assignment', 'VERSION_CONFLICT'], ['application', 'APPLICATION_VERSION_CONFLICT']]);

it('binds the accepted engagement terms and resumes the same report after current terms are renewed', function (): void {
    $fixture = Fixture::ready();
    Fixture::submit($fixture, Fixture::acceptance($fixture));
    $first = startSubmittedAuditReport($fixture);
    $report = AuditReport::query()->firstOrFail();
    $binding = $report->binding;
    $originalRevision = $fixture['assignment']->refresh()->revision;
    expect($binding['engagement']['release_revision'])->toBe(1)
        ->and($binding['engagement']['sha256'])->toHaveLength(64);
    $release = AuditEngagementFixture::release($fixture['audit']['staff'], 1);
    expect(fn () => startSubmittedAuditReport($fixture))->toThrow(CommandRejection::class, 'AUDIT_ENGAGEMENT_ACCEPTANCE_REQUIRED');
    AuditEngagementFixture::accept($fixture['audit']['partners'][0]['user'], $release);
    $resumed = startSubmittedAuditReport($fixture);
    expect($resumed['code'])->toBe('AUDIT_REPORT_RESUMED')->and($resumed['data'])->toBe($first['data'])
        ->and($report->refresh()->binding)->toBe($binding)
        ->and($fixture['assignment']->refresh()->revision)->toBe($originalRevision);
    $this->assertDatabaseCount('audit_reports', 1);
});

it('scopes both application selection and report reads without exposing other businesses', function (): void {
    $fixture = Fixture::ready();
    Fixture::submit($fixture, Fixture::acceptance($fixture));
    $other = Fixture::ready();
    Fixture::submit($other, Fixture::acceptance($other));
    $started = startSubmittedAuditReport($other);
    foreach ([$other['application']->id, (string) Str::ulid()] as $id) {
        expect(startSubmittedAuditReport($fixture, applicationId: $id)['code'])->toBe('APPLICATION_NOT_FOUND');
    }
    foreach ([$started['data']['audit_id'], (string) Str::ulid()] as $id) {
        expect(fn () => app(GetAuditReport::class)->handle($fixture['audit']['partners'][0]['user']->id, 1, $id))
            ->toThrow(CommandRejection::class, 'AUDIT_REPORT_NOT_FOUND');
    }
});

it('rechecks current membership and conflict authority before reads retries and operation lookups', function (string $change): void {
    $fixture = Fixture::ready();
    Fixture::submit($fixture, Fixture::acceptance($fixture));
    $request = (string) Str::uuid();
    $receipt = startSubmittedAuditReport($fixture, $request);
    $partner = $fixture['audit']['partners'][0];
    if ($change === 'conflict') {
        AuditAssignmentFixture::respond($partner['user'], $fixture['assignment']->refresh(), 'conflict', 'Newly identified related party.', 'family_or_business');
    } else {
        RoleMembership::query()->where('party_id', $partner['party']->id)->update(['status' => 'revoked']);
    }
    $exception = $change === 'conflict' ? CommandRejection::class : IdentityViolation::class;
    expect(fn () => startSubmittedAuditReport($fixture, $request))->toThrow($exception)
        ->and(fn () => app(GetAuditReport::class)->handle($partner['user']->id, 1, $receipt['data']['audit_id']))->toThrow($exception)
        ->and(fn () => app(FindAuditReportOperation::class)->handle($partner['user']->id, 1, 'audit.start', $request))->toThrow($exception);
})->with(['membership', 'conflict']);

it('rolls the report and journal back if immutable version creation fails', function (): void {
    $fixture = Fixture::ready();
    Fixture::submit($fixture, Fixture::acceptance($fixture));
    $request = (string) Str::uuid();
    $event = 'eloquent.creating: '.AuditReportVersion::class;
    Event::listen($event, function (): void {
        throw new RuntimeException('Report history unavailable.');
    });
    try {
        expect(fn () => startSubmittedAuditReport($fixture, $request))->toThrow(RuntimeException::class, 'Report history unavailable.');
    } finally {
        Event::forget($event);
    }
    $this->assertDatabaseCount('audit_reports', 0);
    expect(CommandOperation::query()->where('request_id', $request)->exists())->toBeFalse();
    expect(startSubmittedAuditReport($fixture, $request)['code'])->toBe('AUDIT_REPORT_STARTED');
});

it('refuses corrupted report bindings snapshots and missing history', function (string $fault): void {
    $fixture = Fixture::ready();
    Fixture::submit($fixture, Fixture::acceptance($fixture));
    $receipt = startSubmittedAuditReport($fixture);
    $model = in_array($fault, ['binding', 'missing', 'engagement'], true) ? AuditReport::class : AuditReportVersion::class;
    $event = 'eloquent.retrieved: '.$model;
    Event::listen($event, function (AuditReport|AuditReportVersion $record) use ($fault): void {
        if ($record instanceof AuditReport) {
            if ($fault === 'binding') {
                $record->binding_sha256 = str_repeat('0', 64);
            } elseif ($fault === 'engagement') {
                $record->engagement_acceptance_id = (string) Str::ulid();
            } else {
                $record->revision++;
            }
        } elseif ($fault === 'digest') {
            $record->sha256 = str_repeat('0', 64);
        } else {
            $snapshot = $record->snapshot;
            $snapshot['draft']['note'] = 'Changed assessment';
            $record->snapshot = $snapshot;
            $record->sha256 = hash('sha256', app(CanonicalJson::class)->encode($snapshot));
        }
    });
    try {
        expect(fn () => app(GetAuditReport::class)->handle($fixture['audit']['partners'][0]['user']->id, 1, $receipt['data']['audit_id']))
            ->toThrow(RuntimeException::class, 'AUDIT_REPORT_INTEGRITY_FAILED');
    } finally {
        Event::forget($event);
    }
})->with(['binding', 'missing', 'digest', 'snapshot', 'engagement']);

it('rejects unsupported journal commands unlinked identities and wrong target types', function (): void {
    $user = User::factory()->create();
    $lookup = app(FindAuditReportOperation::class);
    expect(fn () => $lookup->handle($user->id, 1, 'audit.unknown', (string) Str::uuid()))->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND')
        ->and(fn () => $lookup->handle($user->id, 1, 'audit.start', (string) Str::uuid()))->toThrow(IdentityViolation::class, 'IDENTITY_NOT_LINKED');
    $fixture = Fixture::ready();
    $request = (string) Str::uuid();
    CommandOperation::factory()->create(['actor_key' => 'party:'.$fixture['audit']['partners'][0]['party']->id, 'command' => 'audit.start',
        'request_id' => $request, 'target_type' => 'application', 'target_id' => $fixture['application']->id]);
    expect(fn () => $lookup->handle($fixture['audit']['partners'][0]['user']->id, 1, 'audit.start', $request))
        ->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND');
});

it('withdraws the original draft atomically and lets the replacement Auditor start and resume a fresh report', function (bool $redispatch): void {
    $fixture = Fixture::ready();
    Fixture::submit($fixture, Fixture::acceptance($fixture));
    $receipt = startSubmittedAuditReport($fixture);
    $original = AuditReport::query()->whereKey($receipt['data']['audit_id'])->firstOrFail();
    $binding = $original->getRawOriginal('binding');
    $other = AuditAssignmentFixture::make(1);
    $replacement = $other['partners'][0];
    if (! $redispatch) {
        AuditAssignmentFixture::independence($fixture['audit']['staff'], $fixture['audit']['business'], $replacement['party']->id);
    }
    AuditAssignmentFixture::respond($fixture['audit']['partners'][0]['user'], $fixture['assignment']->refresh(), 'conflict', 'New family tie.', 'family_or_business');
    if ($redispatch) {
        expect($fixture['assignment']->refresh()->status)->toBe('operations');
        AuditAssignmentFixture::independence($fixture['audit']['staff'], $fixture['audit']['business'], $replacement['party']->id);
        expect(app(ResolveAuditAssignment::class)->handle($fixture['audit']['staff']->id, $fixture['assignment']->id, $fixture['assignment']->revision,
            'redispatch', 'Reviewed new independent partner.', (string) Str::uuid())['code'])->toBe('ASSIGNMENT_REDISPATCHED');
    }
    expect($fixture['assignment']->refresh()->party_id)->toBe($replacement['party']->id);
    AuditAssignmentFixture::respond($replacement['user'], $fixture['assignment']);
    $replacementFixture = $fixture;
    $replacementFixture['audit']['partners'][0] = $replacement;

    expect(app(GetAssignmentAuditReport::class)->handle($replacement['user']->id, 1, $fixture['assignment']->id))->toBeNull()
        ->and(fn () => app(GetAuditReport::class)->handle($replacement['user']->id, 1, $receipt['data']['audit_id']))
        ->toThrow(CommandRejection::class, 'AUDIT_REPORT_NOT_FOUND');
    $replacementStart = startSubmittedAuditReport($replacementFixture);
    expect($replacementStart['code'])->toBe('AUDIT_REPORT_STARTED')
        ->and($replacementStart['data']['audit_id'])->not->toBe($original->id)
        ->and(startSubmittedAuditReport($replacementFixture)['code'])->toBe('AUDIT_REPORT_RESUMED')
        ->and(app(GetAssignmentAuditReport::class)->handle($replacement['user']->id, 1, $fixture['assignment']->id)['id'])
        ->toBe($replacementStart['data']['audit_id'])
        ->and(app(GetAuditReport::class)->handle($replacement['user']->id, 1, $replacementStart['data']['audit_id'])['revision'])->toBe(1)
        ->and($original->refresh()->status)->toBe('withdrawn')
        ->and($original->revision)->toBe(2)->and($original->getRawOriginal('binding'))->toBe($binding)
        ->and($original->draft['withdrawal']['reason_code'])->toBe('AUDITOR_CONFLICT')
        ->and(AuditReportVersion::query()->where('audit_report_id', $original->id)->where('revision', 1)->firstOrFail()->status)->toBe('draft')
        ->and(AuditReportVersion::query()->where('audit_report_id', $original->id)->where('revision', 2)->firstOrFail()->command)->toBe('conflict.declare');
    $this->assertDatabaseCount('audit_reports', 2);
    $this->assertDatabaseCount('audit_report_versions', 3);
})->with([false, true]);

it('rolls conflict declaration and reassignment back when report withdrawal history cannot be appended', function (): void {
    $fixture = Fixture::ready();
    Fixture::submit($fixture, Fixture::acceptance($fixture));
    startSubmittedAuditReport($fixture);
    $assignmentBefore = $fixture['assignment']->refresh()->getRawOriginal();
    $reportBefore = AuditReport::query()->firstOrFail()->getRawOriginal();
    $event = 'eloquent.creating: '.AuditReportVersion::class;
    Event::listen($event, function (): void {
        throw new RuntimeException('Withdrawal history unavailable.');
    });
    $request = (string) Str::uuid();
    try {
        expect(fn () => AuditAssignmentFixture::respond($fixture['audit']['partners'][0]['user'], $fixture['assignment'], 'conflict',
            'New family tie.', 'family_or_business', $request))->toThrow(RuntimeException::class, 'Withdrawal history unavailable.');
    } finally {
        Event::forget($event);
    }
    expect($fixture['assignment']->refresh()->getRawOriginal())->toBe($assignmentBefore)
        ->and(AuditReport::query()->firstOrFail()->getRawOriginal())->toBe($reportBefore)
        ->and(CommandOperation::query()->where('request_id', $request)->exists())->toBeFalse();
    $this->assertDatabaseCount('audit_conflict_declarations', 0);
    $this->assertDatabaseCount('audit_report_versions', 1);
    expect(AuditAssignmentFixture::respond($fixture['audit']['partners'][0]['user'], $fixture['assignment'], 'conflict',
        'New family tie.', 'family_or_business', $request)['code'])->toBe('CONFLICT_RECORDED');
    $this->assertDatabaseCount('audit_report_versions', 2);
});

it('does not rewrite sealed history when an Auditor subsequently declares a conflict', function (): void {
    $fixture = AuditSealingFixture::ready();
    AuditSealingFixture::seal($fixture);
    $report = $fixture['report'];
    $versionCount = AuditReportVersion::query()->count();
    $before = $report->refresh()->getRawOriginal();
    $request = (string) Str::uuid();
    $first = AuditAssignmentFixture::respond($fixture['audit']['partners'][0]['user'], $fixture['assignment']->refresh(), 'conflict',
        'New family tie.', 'family_or_business', $request);
    expect($first['code'])->toBe('CONFLICT_RECORDED')->and($report->refresh()->getRawOriginal())->toBe($before);
    $this->assertDatabaseCount('audit_report_versions', $versionCount);
});

it('replays an original refusal after the application becomes submitted without creating a report', function (): void {
    $fixture = Fixture::ready();
    $request = (string) Str::uuid();
    $revision = $fixture['application']->revision;
    $refusal = startSubmittedAuditReport($fixture, $request, applicationRevision: $revision);
    expect($refusal['code'])->toBe('APPLICATION_NOT_SUBMITTED');
    Fixture::submit($fixture, Fixture::acceptance($fixture));
    expect(startSubmittedAuditReport($fixture, $request, applicationRevision: $revision))->toBe($refusal);
    $this->assertDatabaseCount('audit_reports', 0);
    expect(startSubmittedAuditReport($fixture)['code'])->toBe('AUDIT_REPORT_STARTED');
});

it('denies a new report before journaling for suspended expired offered and nonparticipant Auditors', function (string $fault, string $code): void {
    $fixture = Fixture::ready();
    Fixture::submit($fixture, Fixture::acceptance($fixture));
    $partner = $fixture['audit']['partners'][0];
    if ($fault === 'suspended') {
        AuditorFixture::review($partner['staff'], $partner['party']->id, 3, 'suspend');
    } elseif ($fault === 'expired') {
        $this->travelTo(now()->addYear()->addDay());
    } else {
        $replacement = AuditAssignmentFixture::make(1)['partners'][0];
        if ($fault === 'offered') {
            AuditAssignmentFixture::independence($fixture['audit']['staff'], $fixture['audit']['business'], $replacement['party']->id);
            AuditAssignmentFixture::respond($partner['user'], $fixture['assignment']->refresh(), 'conflict', 'New family tie.', 'family_or_business');
        }
        $fixture['audit']['partners'][0] = $replacement;
    }
    $request = (string) Str::uuid();
    expect(fn () => startSubmittedAuditReport($fixture, $request))->toThrow(CommandRejection::class, $code)
        ->and(CommandOperation::query()->where('request_id', $request)->exists())->toBeFalse();
    $this->assertDatabaseCount('audit_reports', 0);
})->with([['suspended', 'ACCREDITATION_SUSPENDED'], ['expired', 'ACCREDITATION_EXPIRED'],
    ['offered', 'ASSIGNMENT_NOT_ACCEPTED'], ['unrelated', 'ASSIGNMENT_NOT_FOUND']]);

it('checks the stored author and accepted revision again before exposing a report', function (string $column): void {
    $fixture = Fixture::ready();
    Fixture::submit($fixture, Fixture::acceptance($fixture));
    $receipt = startSubmittedAuditReport($fixture);
    $event = 'eloquent.retrieved: '.AuditReport::class;
    Event::listen($event, function (AuditReport $record) use ($column): void {
        $record->setAttribute($column, $column === 'author_party_id' ? (string) Str::ulid() : $record->assignment_revision - 1);
    });
    try {
        expect(fn () => app(GetAuditReport::class)->handle($fixture['audit']['partners'][0]['user']->id, 1, $receipt['data']['audit_id']))
            ->toThrow(CommandRejection::class, 'AUDIT_REPORT_REASSIGNMENT_REQUIRED');
    } finally {
        Event::forget($event);
    }
})->with(['author_party_id', 'assignment_revision']);

it('does not reveal a former Party receipt when identity changes before locked authorization', function (): void {
    $fixture = Fixture::ready();
    Fixture::submit($fixture, Fixture::acceptance($fixture));
    $request = (string) Str::uuid();
    startSubmittedAuditReport($fixture, $request);
    $replacement = AuditAssignmentFixture::make(1)['partners'][0];
    AuditAssignmentFixture::independence($fixture['audit']['staff'], $fixture['audit']['business'], $replacement['party']->id);
    $user = $fixture['audit']['partners'][0]['user'];
    $repository = app(IdentityRepository::class);
    $calls = 0;
    $mock = $this->createMock(IdentityRepository::class);
    $mock->method('forUser')->willReturnCallback(function (int $id) use ($repository, $user, $fixture, $replacement, &$calls): array {
        $snapshot = $repository->forUser($id);
        if ($calls++ === 0) {
            AuditAssignmentFixture::respond($user, $fixture['assignment']->refresh(), 'conflict', 'New family tie.', 'family_or_business');
            AuditAssignmentFixture::respond($replacement['user'], $fixture['assignment']->refresh());
            $membership = RoleMembership::query()->where('party_id', $replacement['party']->id)->firstOrFail();
            $user->forceFill(['party_id' => $replacement['party']->id, 'active_membership_id' => $membership->id])->save();
        }

        return $snapshot;
    });
    $mock->method('auditorPartyIsActive')->willReturnCallback(fn (string $id): bool => $repository->auditorPartyIsActive($id));
    $this->app->instance(IdentityRepository::class, $mock);
    expect(fn () => app(FindAuditReportOperation::class)->handle($user->id, 1, 'audit.start', $request))
        ->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND');
});

it('refuses an existing report whose application binding does not match the requested source', function (): void {
    $fixture = Fixture::ready();
    Fixture::submit($fixture, Fixture::acceptance($fixture));
    startSubmittedAuditReport($fixture);
    $event = 'eloquent.retrieved: '.AuditReport::class;
    Event::listen($event, function (AuditReport $record): void {
        $record->application_id = (string) Str::ulid();
    });
    try {
        expect(startSubmittedAuditReport($fixture)['code'])->toBe('AUDIT_APPLICATION_BOUND');
    } finally {
        Event::forget($event);
    }
});
