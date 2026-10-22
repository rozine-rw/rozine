<?php

declare(strict_types=1);

use App\Application\Auditor\Contracts\MonthlyAuditBusinesses;
use App\Application\Auditor\GetAssignmentAuditReport;
use App\Application\Auditor\GetAuditProcedure;
use App\Application\Auditor\GetAuditReport;
use App\Application\Auditor\RequestMonthlyAudits;
use App\Application\Auditor\StartAuditReport;
use App\Domain\Auditor\AuditEngagementState;
use App\Domain\Operations\CommandRejection;
use App\Models\AuditAssignment;
use App\Models\AuditAssignmentVersion;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Tests\Support\AuditAssignmentFixture;
use Tests\Support\AuditSealingFixture as Fixture;
use Tests\TestCase;

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

/**
 * Request again through the manual action or the monthly application, including its calendar gate.
 *
 * @param  array<string, mixed>  $fixture
 */
function requestAfterPublishedCompletion(TestCase $testCase, array $fixture, bool $monthly): AuditAssignment
{
    $businessId = $fixture['audit']['business'];
    if ($monthly) {
        $testCase->travelTo(CarbonImmutable::now('Africa/Kigali')->addMonthNoOverflow()->startOfMonth()->addHours(6));
        app()->instance(MonthlyAuditBusinesses::class, new readonly class($businessId) implements MonthlyAuditBusinesses
        {
            public function __construct(private string $businessId) {}

            public function due(DateTimeImmutable $now): array
            {
                return [$this->businessId];
            }
        });
        expect(app(RequestMonthlyAudits::class)->handle(now()->toDateTimeImmutable()))->toBe(1);
    } else {
        AuditAssignmentFixture::request($fixture['audit'], 'routine');
    }

    expect($fixture['assignment']->refresh()->status)->toBe('completed');

    return AuditAssignment::query()->where('business_id', $businessId)->where('status', '<>', 'completed')->sole();
}

it('uses post-completion rotation facts when the published engagement is the third consecutive one', function (bool $monthly): void {
    $fixture = Fixture::ready();
    $partyId = $fixture['user']->party_id;
    $states = app(AuditEngagementState::class);
    foreach ([2, 1] as $days) {
        $at = now()->subDays($days);
        $instant = $at->toDateTimeImmutable();
        $state = $states->complete($states->accept($states->offer($states->start('routine', $instant), $partyId, $instant), $instant), $instant);
        AuditAssignment::factory()->create(['id' => strtolower((string) Str::ulid($at)), 'business_id' => $fixture['audit']['business'],
            'party_id' => $partyId, 'state' => $state, 'status' => 'completed', 'completed_at' => $at, 'created_at' => $at]);
    }
    expect(Fixture::seal($fixture)['code'])->toBe('AUDIT_SEALED')
        ->and(Fixture::cosign($fixture)['code'])->toBe('REPORT_PUBLISHED');

    $next = requestAfterPublishedCompletion($this, $fixture, $monthly);

    expect($next->status)->toBe('operations')->and($next->party_id)->toBeNull();
    $candidate = AuditAssignmentVersion::query()->where('assignment_id', $next->id)->sole()->selection_basis['candidates'][0];
    expect($candidate['consecutive_reports'])->toBe(3)->and($candidate['active_count'])->toBe(0);
})->with(['manual request' => false, 'monthly request' => true]);

it('uses post-completion capacity facts when the published engagement frees the third slot', function (bool $monthly): void {
    $fixture = Fixture::ready();
    $partyId = $fixture['user']->party_id;
    AuditAssignmentFixture::engagement($partyId);
    AuditAssignmentFixture::engagement($partyId);
    expect(Fixture::seal($fixture)['code'])->toBe('AUDIT_SEALED')
        ->and(Fixture::cosign($fixture)['code'])->toBe('REPORT_PUBLISHED');

    $next = requestAfterPublishedCompletion($this, $fixture, $monthly);

    expect($next->status)->toBe('offered')->and($next->party_id)->toBe($partyId);
    $candidate = AuditAssignmentVersion::query()->where('assignment_id', $next->id)->sole()->selection_basis['candidates'][0];
    expect($candidate['active_count'])->toBe(2)->and($candidate['consecutive_reports'])->toBe(1)
        ->and(AuditAssignmentFixture::respond($fixture['user'], $next)['code'])->toBe('ASSIGNMENT_ACCEPTED')
        ->and(AuditAssignment::query()->where('party_id', $partyId)->where('status', 'accepted')->count())->toBe(3);
})->with(['manual request' => false, 'monthly request' => true]);
