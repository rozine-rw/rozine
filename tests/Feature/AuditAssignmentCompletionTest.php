<?php

declare(strict_types=1);

use App\Application\Auditor\GetAssignmentAuditReport;
use App\Application\Auditor\GetAuditProcedure;
use App\Application\Auditor\GetAuditReport;
use App\Application\Auditor\StartAuditReport;
use App\Domain\Auditor\AuditEngagementState;
use App\Domain\Operations\CommandRejection;
use App\Models\AuditAssignment;
use App\Models\AuditAssignmentVersion;
use Illuminate\Support\Str;
use Tests\Support\AuditAssignmentFixture;
use Tests\Support\AuditSealingFixture as Fixture;

beforeEach(function (): void {
    $this->freezeSecond();
});

it('completes a published engagement on the next request so the business can be audited again', function (): void {
    $fixture = Fixture::ready();
    expect(Fixture::seal($fixture)['code'])->toBe('AUDIT_SEALED')
        ->and(Fixture::cosign($fixture)['code'])->toBe('REPORT_PUBLISHED');
    $flash = $fixture['assignment']->refresh();
    $accepted = $flash->revision;

    $routine = AuditAssignmentFixture::request($fixture['audit'], 'routine');

    $flash->refresh();
    expect($routine->id)->not->toBe($flash->id)->and($routine->state['kind'])->toBe('routine')
        ->and($flash->status)->toBe('completed')->and($flash->completed_at)->not->toBeNull()
        ->and($flash->state['completed_at'])->toBe(now('UTC')->format('Y-m-d\TH:i:s\Z'))->and($flash->revision)->toBe($accepted + 1)
        ->and(AuditAssignmentVersion::query()->where('assignment_id', $flash->id)->where('revision', $flash->revision)->value('command'))->toBe('audit.assignment.complete');

    $user = $fixture['user'];
    $report = app(GetAuditReport::class)->handle($user->id, 1, $fixture['report']->id);
    $procedure = app(GetAuditProcedure::class)->handle($user->id, 1, $fixture['report']->id);
    expect($report['status'])->toBe('sealed')->and($procedure['sealed']['published_at'])->not->toBeNull()
        ->and(app(GetAssignmentAuditReport::class)->handle($user->id, 1, $flash->id)['id'])->toBe($fixture['report']->id)
        ->and(fn () => app(StartAuditReport::class)->handle($user->id, 1, $flash->id, $flash->revision,
            $fixture['application']->id, $fixture['application']->refresh()->revision, (string) Str::uuid()))
        ->toThrow(CommandRejection::class, 'ASSIGNMENT_NOT_FOUND');
});

it('resumes an engagement whose sealed report is not yet published', function (): void {
    $fixture = Fixture::ready();
    Fixture::seal($fixture);

    $resumed = AuditAssignmentFixture::request($fixture['audit'], 'routine');

    expect($resumed->id)->toBe($fixture['assignment']->id)->and($resumed->status)->toBe('accepted')->and($resumed->completed_at)->toBeNull()
        ->and(AuditAssignment::query()->where('business_id', $fixture['audit']['business'])->count())->toBe(1);
});

it('completes only accepted engagements', function (string $status): void {
    $states = app(AuditEngagementState::class);
    $state = [...$states->start('routine', now()->toDateTimeImmutable()), 'status' => $status];

    expect(fn () => $states->complete($state, now()->toDateTimeImmutable()))->toThrow(CommandRejection::class, 'ASSIGNMENT_NOT_ACCEPTED');
})->with(['offered', 'operations', 'completed']);
