<?php

declare(strict_types=1);

use App\Application\Auditor\AmendAuditReport;
use App\Application\Auditor\DecideAuditReport;
use App\Application\Auditor\SaveAuditReportStep;
use App\Application\Auditor\StartAuditReport;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Models\AuditReport;
use App\Models\AuditReportVersion;
use App\Models\CommandOperation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\AuditAssignmentFixture;
use Tests\Support\AuditEngagementFixture;
use Tests\Support\BusinessQuoteFixture;

/**
 * Runs each contender in its own process and connection. Exit 0 means the call returned (a journaled
 * outcome, which may itself be a refusal), 2 a thrown domain refusal and 1 any other failure.
 *
 * @param  list<Closure(): mixed>  $operations
 * @return list<int>
 */
function auditLifecycleReviewContenders(array $operations): array
{
    $pids = [];
    foreach ($operations as $operation) {
        $pid = pcntl_fork();
        if ($pid === -1) {
            throw new RuntimeException('Could not fork a report lifecycle contender.');
        }
        if ($pid === 0) {
            DB::purge();
            try {
                $operation();
                exit(0);
            } catch (IdentityViolation|CommandRejection) {
                exit(2);
            } catch (Throwable) {
                exit(1);
            }
        }
        $pids[] = $pid;
    }

    $statuses = [];
    foreach ($pids as $pid) {
        pcntl_waitpid($pid, $status);
        $exitCode = pcntl_wexitstatus($status);
        $statuses[] = pcntl_wifexited($status) && is_int($exitCode) ? $exitCode : 99;
    }

    return $statuses;
}

/** @return array<string, mixed> */
function auditLifecycleReviewRace(bool $returned): array
{
    $fixture = BusinessQuoteFixture::ready(auditKind: 'routine');
    BusinessQuoteFixture::submit($fixture, BusinessQuoteFixture::acceptance($fixture));
    $actor = $fixture['audit']['partners'][0]['user'];
    $id = app(StartAuditReport::class)->handle($actor->id, 1, $fixture['assignment']->id, $fixture['assignment']->refresh()->revision,
        $fixture['application']->id, $fixture['application']->refresh()->revision, (string) Str::uuid())['data']['audit_id'];
    if ($returned) {
        app(DecideAuditReport::class)->handle($actor->id, 1, $id, 1, false, 'other', 'Missing original.', (string) Str::uuid());
    }

    return [...$fixture, 'actor' => $actor, 'id' => $id];
}

/** @return list<string> */
function auditLifecycleReviewCodes(string $command): array
{
    return array_values(CommandOperation::query()->where('command', $command)->get()
        ->map(fn (CommandOperation $operation): string => $operation->result['code'])->all());
}

it('serializes an amendment against its Auditor declaring a conflict without leaving a live child or changing the parent', function (): void {
    $f = auditLifecycleReviewRace(true);
    $parent = AuditReport::query()->whereKey($f['id'])->firstOrFail()->getAttributes();

    $statuses = auditLifecycleReviewContenders([
        fn (): array => app(AmendAuditReport::class)->handle($f['actor']->id, 1, $f['id'], 2, (string) Str::uuid()),
        fn (): array => AuditAssignmentFixture::respond($f['actor'], $f['assignment']->refresh(), 'conflict', 'New family tie.', 'family_or_business'),
    ]);

    $children = AuditReport::query()->where('amends_id', $f['id'])->get();
    expect($statuses[1])->toBe(0)->and($statuses[0])->toBeIn([0, 2])
        ->and(AuditReport::query()->whereKey($f['id'])->firstOrFail()->getAttributes())->toBe($parent)
        ->and($children)->toHaveCount($statuses[0] === 0 ? 1 : 0)
        ->and($children->where('status', 'draft'))->toHaveCount(0);
});

it('lets exactly one of a decision and a competing step save advance the report', function (): void {
    $f = auditLifecycleReviewRace(false);

    $statuses = auditLifecycleReviewContenders([
        fn (): array => app(DecideAuditReport::class)->handle($f['actor']->id, 1, $f['id'], 1, true, 'other', 'Unverifiable.', (string) Str::uuid()),
        fn (): array => app(SaveAuditReportStep::class)->handle($f['actor']->id, 1, $f['id'], 1, 'statements', [], (string) Str::uuid()),
    ]);

    $report = AuditReport::query()->whereKey($f['id'])->firstOrFail();
    $decision = auditLifecycleReviewCodes('audit.reject');
    $save = auditLifecycleReviewCodes('audit.save_step');
    expect($statuses)->toBe([0, 0])->and($report->revision)->toBe(2)
        ->and(AuditReportVersion::query()->where('audit_report_id', $f['id'])->count())->toBe(2);
    if ($report->status === 'rejected') {
        expect($decision)->toBe(['AUDIT_REJECTED'])->and($save)->toBe(['VERSION_CONFLICT']);
    } else {
        expect($report->status)->toBe('draft')->and($save)->toBe(['AUDIT_STEP_SAVED'])->and($decision)->toBe(['VERSION_CONFLICT']);
    }
});

it('serializes a decision against its Auditor declaring a conflict into one retained report version', function (): void {
    $f = auditLifecycleReviewRace(false);

    $statuses = auditLifecycleReviewContenders([
        fn (): array => app(DecideAuditReport::class)->handle($f['actor']->id, 1, $f['id'], 1, false, 'other', 'Missing original.', (string) Str::uuid()),
        fn (): array => AuditAssignmentFixture::respond($f['actor'], $f['assignment']->refresh(), 'conflict', 'New family tie.', 'family_or_business'),
    ]);

    $report = AuditReport::query()->whereKey($f['id'])->firstOrFail();
    expect($statuses[1])->toBe(0)->and($report->revision)->toBe(2)
        ->and(AuditReportVersion::query()->where('audit_report_id', $f['id'])->count())->toBe(2);
    if ($report->status === 'changes_requested') {
        expect($statuses[0])->toBe(0)->and(auditLifecycleReviewCodes('audit.request_changes'))->toBe(['AUDIT_CHANGES_REQUESTED']);
    } else {
        expect($report->status)->toBe('withdrawn')->and($statuses[0])->toBe(2)->and(auditLifecycleReviewCodes('audit.request_changes'))->toBe([]);
    }
});

it('serializes an amendment against an engagement terms publication so the child pins only current terms', function (): void {
    $f = auditLifecycleReviewRace(true);
    $acceptance = AuditReport::query()->whereKey($f['id'])->firstOrFail()->engagement_acceptance_id;

    $statuses = auditLifecycleReviewContenders([
        fn (): array => app(AmendAuditReport::class)->handle($f['actor']->id, 1, $f['id'], 2, (string) Str::uuid()),
        fn (): mixed => AuditEngagementFixture::release($f['audit']['staff'], 1),
    ]);

    $children = AuditReport::query()->where('amends_id', $f['id'])->get();
    expect($statuses[1])->toBe(0)->and($statuses[0])->toBeIn([0, 2])
        ->and($children)->toHaveCount($statuses[0] === 0 ? 1 : 0)
        ->and($children->every(fn (AuditReport $child): bool => $child->engagement_acceptance_id === $acceptance))->toBeTrue();
});
