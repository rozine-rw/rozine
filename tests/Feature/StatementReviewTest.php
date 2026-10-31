<?php

declare(strict_types=1);

use App\Application\Auditor\Contracts\AuditAssignmentStore;
use App\Application\Auditor\ResolveAuditAssignment;
use App\Application\Auditor\StartAuditReport;
use App\Application\Business\Contracts\BusinessAuditWindows;
use App\Application\Evidence\FindStatementReviewOperation;
use App\Application\Evidence\FindStatementVerificationOperation;
use App\Application\Evidence\GetStaffStatementAuditReadiness;
use App\Application\Evidence\IngestStatement;
use App\Application\Evidence\RecordStatementTranscription;
use App\Application\Evidence\RequestStatementReview;
use App\Domain\Auditor\AuditEngagementState;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Models\AuditAssignment;
use App\Models\AuditAssignmentVersion;
use App\Models\CommandOperation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\AuditAssignmentFixture as Fixture;
use Tests\Support\AuditorFixture;
use Tests\Support\BusinessAuthorityFixture;
use Tests\Support\StatementFixture;

beforeEach(function (): void {
    $this->freezeSecond();
});

/**
 * The bindings staff review before requesting, read from the readiness they were shown.
 *
 * @param  array<string, mixed>  $fixture
 * @param  array{expected_business_revision?: int, expected_mandate_version?: int, expected_evidence_revision?: int, transcription_id?: string, transcription_sha256?: string, readiness_sha256?: string}  $overrides
 * @return array{expected_business_revision: int, expected_mandate_version: int, expected_evidence_revision: int, transcription_id: string, transcription_sha256: string, readiness_sha256: string}
 */
function reviewBinding(array $fixture, array $overrides = []): array
{
    $readiness = app(GetStaffStatementAuditReadiness::class)->handle($fixture['staff']->id, $fixture['business']);

    return [...['expected_business_revision' => $readiness['business_revision'], 'expected_mandate_version' => $readiness['mandate_version'],
        'expected_evidence_revision' => $readiness['evidence_revision'], 'transcription_id' => $readiness['transcription']['id'] ?? '',
        'transcription_sha256' => $readiness['transcription']['sha256'] ?? '', 'readiness_sha256' => $readiness['readiness_sha256']], ...$overrides];
}

/**
 * @param  array<string, mixed>  $fixture
 * @param  array{expected_business_revision: int, expected_mandate_version: int, expected_evidence_revision: int, transcription_id: string, transcription_sha256: string, readiness_sha256: string}|null  $binding
 * @return array<string, mixed>
 */
function requestReview(array $fixture, ?array $binding = null, ?string $requestId = null): array
{
    return app(RequestStatementReview::class)->handle($fixture['staff']->id, $fixture['business'], $binding ?? reviewBinding($fixture),
        'First factual review of the uploaded statements.', $requestId ?? (string) Str::uuid());
}

/** @param array<string, mixed> $fixture */
function review(array $fixture, ?string $requestId = null): AuditAssignment
{
    return reviewAssignment(requestReview($fixture, requestId: $requestId)['data']['assignment_id']);
}

function reviewAssignment(mixed $id): AuditAssignment
{
    return AuditAssignment::query()->whereKey($id)->firstOrFail();
}

/**
 * Upload a fresh original and save its reviewed transcription, so the last verification is no longer current.
 *
 * @param  array<string, mixed>  $fixture
 * @return array{document_id: string, transcription_id: string}
 */
function amendStatements(array $fixture, int $revision): array
{
    $owner = $fixture['authority']['users'][0];
    $document = app(IngestStatement::class)->handle($owner->id, 1, $fixture['business'], $revision, 'amended.csv', StatementFixture::csv('300'), (string) Str::uuid())['data']['document_id'];
    $input = StatementFixture::transcription($document);

    $receipt = app(RecordStatementTranscription::class)->handle($owner->id, 1, $fixture['business'], $revision + 1,
        $input['rails'], $input['months'], $input['statements'], StatementFixture::reviewed($owner->id, $fixture['business']), (string) Str::uuid());
    expect($receipt['code'])->toBe('STATEMENT_RECONCILED_UNVERIFIED', json_encode($receipt, JSON_THROW_ON_ERROR));

    return ['document_id' => $document, 'transcription_id' => $receipt['data']['transcription']['id']];
}

it('takes a new Business through statement review, acceptance and verification without a report or application', function (): void {
    $fixture = Fixture::make(1);
    $sources = Fixture::statements($fixture);
    $partner = $fixture['partners'][0];
    $request = (string) Str::uuid();
    $receipt = requestReview($fixture, requestId: $request);
    $assignment = reviewAssignment($receipt['data']['assignment_id']);

    expect($receipt['code'])->toBe('AUDIT_ASSIGNMENT_REQUESTED')
        ->and(requestReview($fixture, requestId: $request))->toBe($receipt)
        ->and(app(FindStatementReviewOperation::class)->handle($fixture['staff']->id, $fixture['business'], $request))->toBe($receipt)
        ->and(fn () => app(FindStatementReviewOperation::class)->handle($fixture['staff']->id, (string) Str::ulid(), $request))
        ->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND')
        ->and($assignment->state)->toMatchArray(['kind' => 'routine', 'purpose' => 'statement_review', 'status' => 'offered', 'party_id' => $partner['party']->id])
        ->and(AuditAssignmentVersion::query()->where('assignment_id', $assignment->id)->sole()->command)->toBe('audit.statement_review.request')
        ->and(app(BusinessAuditWindows::class)->upcoming($fixture['business'], now()->toDateTimeImmutable()))->toBeNull();
    $open = app(GetStaffStatementAuditReadiness::class)->handle($fixture['staff']->id, $fixture['business']);
    expect($open['assignment'])->toMatchArray(['id' => $assignment->id, 'purpose' => 'statement_review'])
        ->and($open['reasons'])->toBe([['code' => 'AUDIT_STATEMENT_REVIEW_OPEN', 'field' => 'assignment']])
        ->and(requestReview($fixture)['code'])->toBe('AUDIT_ASSIGNMENT_RESUMED');

    Fixture::respond($partner['user'], $assignment);
    $assignment->refresh();
    expect(app(AuditAssignmentStore::class)->get($partner['user']->id, 1, $assignment->id)['period'])->toBeNull()
        ->and(AuditAssignment::query()->where('party_id', $partner['party']->id)->where('status', 'accepted')->count())->toBe(1);
    // An accepted review with no verification yet stays open to the hourly monthly run.
    expect(app(AuditAssignmentStore::class)->requestMonthly($fixture['business']))->toBeNull();

    $verified = Fixture::verifyStatements($fixture, $assignment, $sources['transcription_id']);
    $assignment->refresh();
    $completion = AuditAssignmentVersion::query()->where('assignment_id', $assignment->id)->orderByDesc('revision')->first();
    expect($verified['code'])->toBe('STATEMENT_SOURCE_VERIFIED')->and($assignment->status)->toBe('completed')
        ->and($completion->command)->toBe('audit.assignment.complete')->and($completion->reason)->toBe($verified['data']['verification_id'])
        ->and($completion->actor_user_id)->toBe($partner['user']->id)->and($completion->selection_basis['candidates'])->toBe([])
        ->and(AuditAssignment::query()->where('party_id', $partner['party']->id)->where('status', 'accepted')->count())->toBe(0)
        ->and(DB::table('audit_reports')->count())->toBe(0)->and(DB::table('business_applications')->count())->toBe(0);
    $after = app(GetStaffStatementAuditReadiness::class)->handle($fixture['staff']->id, $fixture['business']);
    expect($after['assignment'])->toBeNull()->and($after['source_verified'])->toBeTrue()
        ->and($after['reasons'])->toBe([['code' => 'STATEMENT_ALREADY_VERIFIED', 'field' => null]])
        ->and(requestReview($fixture))->toMatchArray(['status' => 'rejected', 'code' => 'STATEMENT_ALREADY_VERIFIED', 'http_status' => 409]);
});

it('returns the retained verification receipt after completion and refuses new verification work', function (): void {
    $fixture = Fixture::make(1);
    $sources = Fixture::statements($fixture);
    $partner = $fixture['partners'][0];
    $assignment = review($fixture);
    Fixture::respond($partner['user'], $assignment);
    $assignment->refresh();
    $accepted = clone $assignment;
    $request = (string) Str::uuid();
    $receipt = Fixture::verifyStatements($fixture, $assignment, $sources['transcription_id'], requestId: $request);
    expect($assignment->refresh()->status)->toBe('completed');
    $assignment = $accepted;

    // A lost response: the same request comes back with its original receipt, and lookup finds it.
    expect(Fixture::verifyStatements($fixture, $assignment, $sources['transcription_id'], requestId: $request))->toBe($receipt)
        ->and(app(FindStatementVerificationOperation::class)->handle($partner['user']->id, 1, $request))->toBe($receipt)
        ->and(Fixture::verifyStatements($fixture, $assignment, $sources['transcription_id'], verificationRevision: 1))
        ->toMatchArray(['status' => 'rejected', 'code' => 'ASSIGNMENT_NOT_ACCEPTED', 'http_status' => 403]);

    AuditorFixture::review($partner['staff'], $partner['party']->id, 3, 'suspend');
    expect(fn () => Fixture::verifyStatements($fixture, $assignment, $sources['transcription_id'], requestId: $request))
        ->toThrow(CommandRejection::class, 'ACCREDITATION_SUSPENDED')
        ->and(fn () => app(FindStatementVerificationOperation::class)->handle($partner['user']->id, 1, $request))
        ->toThrow(CommandRejection::class, 'ACCREDITATION_SUSPENDED');
});

it('keeps a verification receipt from another partner out of reach', function (): void {
    $fixture = Fixture::make(2);
    $sources = Fixture::statements($fixture);
    $assignment = review($fixture);
    $partner = Fixture::recipient($fixture, $assignment);
    $other = array_values(array_filter($fixture['partners'], fn (array $candidate): bool => $candidate['party']->id !== $partner['party']->id))[0];
    Fixture::respond($partner['user'], $assignment);
    $request = (string) Str::uuid();
    Fixture::verifyStatements($fixture, $assignment->refresh(), $sources['transcription_id'], requestId: $request);

    expect(fn () => app(FindStatementVerificationOperation::class)->handle($other['user']->id, 1, $request))
        ->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND');
});

it('allows a new review once a changed original, transcription or mandate makes the verification non-current', function (string $change): void {
    $fixture = Fixture::make(1);
    $sources = Fixture::statements($fixture);
    $owner = $fixture['authority']['users'][0];
    $partner = $fixture['partners'][0];
    $first = review($fixture);
    Fixture::respond($partner['user'], $first);
    Fixture::verifyStatements($fixture, $first->refresh(), $sources['transcription_id']);

    [$transcription, $review, $evidence] = match ($change) {
        'original' => (function () use ($fixture, $sources): array {
            $amended = amendStatements($fixture, 2);

            return [$amended['transcription_id'], StatementFixture::review([$sources['document_id'] => hash('sha256', StatementFixture::csv()),
                $amended['document_id'] => hash('sha256', StatementFixture::csv('300'))]), 4];
        })(),
        'transcription' => (function () use ($fixture, $sources, $owner): array {
            $input = StatementFixture::transcription($sources['document_id']);
            $saved = app(RecordStatementTranscription::class)->handle($owner->id, 1, $fixture['business'], 2, $input['rails'], $input['months'], $input['statements'],
                StatementFixture::reviewed($owner->id, $fixture['business']), (string) Str::uuid());

            return [$saved['data']['transcription']['id'], null, 3];
        })(),
        default => (function () use ($fixture, $sources, $partner): array {
            $authority = $fixture['authority'];
            $authority['profile']['name'] = 'Updated legal business name';
            BusinessAuthorityFixture::configure($authority, 1);
            Fixture::independence($fixture['staff'], $fixture['business'], $partner['party']->id, 1);

            return [$sources['transcription_id'], null, 2];
        })(),
    };
    $readiness = app(GetStaffStatementAuditReadiness::class)->handle($fixture['staff']->id, $fixture['business']);
    expect($readiness['source_verified'])->toBeFalse()->and($readiness['ready_to_request'])->toBeTrue();

    $second = review($fixture);
    expect($second->id)->not->toBe($first->id)->and($second->party_id)->toBe($partner['party']->id);
    Fixture::respond($partner['user'], $second);
    $receipt = Fixture::verifyStatements($fixture, $second->refresh(), $transcription, $review, evidenceRevision: $evidence, verificationRevision: 1);
    expect($receipt['code'])->toBe('STATEMENT_SOURCE_VERIFIED')->and($second->refresh()->status)->toBe('completed')
        ->and(AuditAssignment::query()->where('party_id', $partner['party']->id)->where('status', 'accepted')->count())->toBe(0)
        ->and(DB::table('business_applications')->count())->toBe(0);
})->with(['original', 'transcription', 'mandate']);

it('never counts completed statement reviews toward three consecutive reports', function (): void {
    $fixture = Fixture::make(1);
    $partyId = $fixture['partners'][0]['party']->id;
    $states = app(AuditEngagementState::class);
    foreach ([3, 2, 1] as $days) {
        $at = now()->subDays($days);
        $instant = $at->toDateTimeImmutable();
        $state = $states->complete($states->accept($states->offer($states->start('routine', $instant, 'statement_review'), $partyId, $instant), $instant), $instant);
        AuditAssignment::factory()->create(['id' => strtolower((string) Str::ulid($at)), 'business_id' => $fixture['business'],
            'party_id' => $partyId, 'state' => $state, 'status' => 'completed', 'completed_at' => $at, 'created_at' => $at]);
    }

    $monthly = AuditAssignment::query()->findOrFail(app(AuditAssignmentStore::class)->requestMonthly($fixture['business']));
    $candidate = AuditAssignmentVersion::query()->where('assignment_id', $monthly->id)->sole()->selection_basis['candidates'][0];
    expect($monthly->status)->toBe('offered')->and($monthly->party_id)->toBe($partyId)
        ->and($monthly->state['purpose'] ?? null)->toBe('monthly')->and($candidate['consecutive_reports'])->toBe(0);
});

it('lets the monthly audit and a statement review take turns under the one-open-engagement rule', function (): void {
    $fixture = Fixture::make(1);
    $sources = Fixture::statements($fixture);
    $partner = $fixture['partners'][0];
    $store = app(AuditAssignmentStore::class);

    $monthly = AuditAssignment::query()->findOrFail($store->requestMonthly($fixture['business']));
    expect(requestReview($fixture))->toMatchArray(['status' => 'rejected', 'code' => 'AUDIT_ENGAGEMENT_OPEN', 'http_status' => 409,
        'data' => ['assignment_id' => $monthly->id, 'kind' => 'routine', 'purpose' => 'monthly', 'status' => 'offered']]);
    $this->assertDatabaseCount('audit_assignments', 1);

    $monthly->forceFill(['status' => 'completed', 'completed_at' => now(), 'state' => [...$monthly->state, 'status' => 'completed']])->save();
    $review = review($fixture);
    expect($store->requestMonthly($fixture['business']))->toBeNull();
    Fixture::respond($partner['user'], $review);
    Fixture::verifyStatements($fixture, $review->refresh(), $sources['transcription_id']);
    // The review completed in the month the monthly audit already ran, so that month stays dispatched.
    expect($store->requestMonthly($fixture['business']))->toBeNull();

    $this->travelTo(now('Africa/Kigali')->addMonthNoOverflow()->startOfMonth()->addMinute());
    expect($store->requestMonthly($fixture['business']))->not->toBeNull();
});

it('dispatches the monthly audit in the same month once the review that held it completes', function (): void {
    $fixture = Fixture::make(1);
    $sources = Fixture::statements($fixture);
    $store = app(AuditAssignmentStore::class);
    $review = review($fixture);
    expect($store->requestMonthly($fixture['business']))->toBeNull();
    Fixture::respond($fixture['partners'][0]['user'], $review);
    Fixture::verifyStatements($fixture, $review->refresh(), $sources['transcription_id']);

    $monthly = AuditAssignment::query()->findOrFail($store->requestMonthly($fixture['business']));
    expect($monthly->state['purpose'] ?? null)->toBe('monthly')->and($monthly->state['kind'])->toBe('routine');
    $this->travelTo(now('Africa/Kigali')->addDay()->startOfMonth()->addMinute());
    expect($store->requestMonthly($fixture['business']))->toBeNull();
});

it('reviews a Business with completed audit history but not one whose last audit was closed', function (): void {
    $fixture = Fixture::make(1);
    Fixture::statements($fixture);
    Fixture::engagement($fixture['partners'][0]['party']->id, 'completed', $fixture['business']);
    expect(requestReview($fixture)['code'])->toBe('AUDIT_ASSIGNMENT_REQUESTED');

    $closed = Fixture::make(0);
    Fixture::statements($closed);
    $assignment = Fixture::request($closed);
    app(ResolveAuditAssignment::class)->handle($closed['staff']->id, $assignment->id, 1, 'close', 'No eligible partner.', (string) Str::uuid());
    expect(requestReview($closed))->toMatchArray(['status' => 'rejected', 'code' => 'AUDIT_ENGAGEMENT_CLOSED']);
});

it('refuses to start a report on a statement review', function (): void {
    $fixture = Fixture::make(1);
    Fixture::statements($fixture);
    $assignment = review($fixture);
    Fixture::respond($fixture['partners'][0]['user'], $assignment);
    $assignment->refresh();

    expect(app(StartAuditReport::class)->handle($fixture['partners'][0]['user']->id, 1, $assignment->id, $assignment->revision,
        strtolower((string) Str::ulid()), 1, (string) Str::uuid()))->toMatchArray(['status' => 'rejected', 'code' => 'AUDIT_REPORT_NOT_APPLICABLE', 'http_status' => 409]);
    $this->assertDatabaseCount('audit_reports', 0);
});

it('compares every reviewed binding inside the request and creates nothing when one is stale', function (string $fault, string $code): void {
    $fixture = Fixture::make(1);
    $owner = $fixture['authority']['users'][0];
    if ($fault === 'not ready') {
        app(IngestStatement::class)->handle($owner->id, 1, $fixture['business'], 0, 'momo.csv', StatementFixture::csv(), (string) Str::uuid());
        $binding = reviewBinding($fixture);
    } else {
        Fixture::statements($fixture);
        $binding = reviewBinding($fixture, match ($fault) {
            'business' => ['expected_business_revision' => 99],
            'mandate' => ['expected_mandate_version' => 99],
            'evidence' => ['expected_evidence_revision' => 1],
            'transcription' => ['transcription_sha256' => str_repeat('0', 64)],
            default => ['readiness_sha256' => str_repeat('0', 64)],
        });
    }

    expect(requestReview($fixture, $binding))->toMatchArray(['status' => 'rejected', 'code' => $code]);
    $this->assertDatabaseCount('audit_assignments', 0);
    expect(CommandOperation::query()->where('command', 'audit.statement_review.request')->sole()->result['code'])->toBe($code);
})->with([
    ['business', 'BUSINESS_STALE'], ['mandate', 'MANDATE_STALE'], ['evidence', 'VERSION_CONFLICT'], ['not ready', 'INITIAL_AUDIT_NOT_READY'],
    ['transcription', 'STATEMENT_TRANSCRIPTION_STALE'], ['readiness', 'STATEMENT_READINESS_STALE'],
]);

it('keeps the review request to staff who manage audit assignments', function (): void {
    $fixture = Fixture::make(1);
    Fixture::statements($fixture);
    $binding = reviewBinding($fixture);

    expect(fn () => app(RequestStatementReview::class)->handle($fixture['partners'][0]['staff']->id, $fixture['business'], $binding, 'Review.', (string) Str::uuid()))
        ->toThrow(IdentityViolation::class, 'STAFF_PERMISSION_REQUIRED')
        ->and(fn () => app(FindStatementReviewOperation::class)->handle($fixture['partners'][0]['staff']->id, $fixture['business'], (string) Str::uuid()))
        ->toThrow(IdentityViolation::class, 'STAFF_PERMISSION_REQUIRED');
    $this->assertDatabaseCount('audit_assignments', 0);
});

it('fixes the purpose at creation to a report or a routine statement review', function (string $kind, string $purpose): void {
    expect(fn () => app(AuditEngagementState::class)->start($kind, now()->toDateTimeImmutable(), $purpose))
        ->toThrow(CommandRejection::class, 'AUDIT_PURPOSE_INVALID');
})->with([['flash', 'statement_review'], ['routine', 'initial']]);
