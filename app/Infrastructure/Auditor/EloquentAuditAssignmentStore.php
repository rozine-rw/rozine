<?php

declare(strict_types=1);

namespace App\Infrastructure\Auditor;

use App\Application\Auditor\Contracts\AuditAssignmentStore;
use App\Application\Auditor\Contracts\AuditEngagementStore;
use App\Application\Auditor\Contracts\AuditReportLifecycle;
use App\Application\Business\Contracts\BusinessAuthorityStore;
use App\Application\Identity\AuthorizeActiveRole;
use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Identity\Contracts\IdentityRepository;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Operations\Contracts\OperationJournal;
use App\Domain\Auditor\AuditEngagementState;
use App\Domain\Auditor\AuditorDispatch;
use App\Domain\Auditor\AuditorIndependence;
use App\Domain\Auditor\AuditorStanding;
use App\Domain\Auditor\AuditReadPolicy;
use App\Domain\Auditor\VerifiedAuditLocation;
use App\Domain\Auditor\Wgs84Distance;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Models\AuditAssignment;
use App\Models\AuditAssignmentVersion;
use App\Models\AuditConflictDeclaration;
use App\Models\AuditLocation;
use App\Models\AuditorIndependenceReview;
use App\Models\AuditorProfile;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * @phpstan-import-type State from AuditEngagementState
 * @phpstan-import-type Assignment from AuditAssignmentStore
 * @phpstan-import-type AcceptedAssignment from AuditAssignmentStore
 * @phpstan-import-type View from AuditAssignmentStore
 * @phpstan-import-type OwnConflict from AuditAssignmentStore
 * @phpstan-import-type ConflictPage from AuditAssignmentStore
 * @phpstan-import-type Work from AuditAssignmentStore
 * @phpstan-import-type WorkIdentifiers from AuditAssignmentStore
 * @phpstan-import-type OperationsCase from AuditAssignmentStore
 * @phpstan-import-type AuditContext from BusinessAuthorityStore
 * @phpstan-import-type Candidate from AuditorDispatch
 */
final class EloquentAuditAssignmentStore implements AuditAssignmentStore
{
    public function __construct(
        private BusinessAuthorityStore $businesses,
        private IdentityRepository $identities,
        private OperationJournal $journal,
        private AuditEngagementState $states,
        private AuditorDispatch $dispatch,
        private AuditorStanding $standing,
        private AuditorIndependence $independence,
        private VerifiedAuditLocation $locations,
        private Wgs84Distance $distance,
        private CanonicalJson $json,
        private AuthorizeActiveRole $roles,
        private AuthorizeStaffPermission $staff,
        private AuditReportLifecycle $reports,
        private AuditEngagementStore $engagements,
    ) {}

    /** @return array<string, mixed> */
    public function request(int $actorId, string $businessId, string $kind, string $reason, string $requestId): array
    {
        $this->staff->check($actorId, 'audit.assignments.manage');

        return $this->scope($actorId, null, $businessId, true, function (array $context, array $candidates) use ($actorId, $businessId, $kind, $reason, $requestId): array {
            return $this->journal->execute('staff:'.$actorId, $actorId, 'audit.assignment.request', $requestId, 'business', $businessId,
                ['kind' => $kind, 'reason' => $reason], function (): void {},
                function () use ($actorId, $businessId, $kind, $reason, $context, $candidates): OperationResult {
                    $this->states->reason($reason);
                    $state = $this->states->start($kind, now()->toDateTimeImmutable());
                    $existing = AuditAssignment::query()->where('business_id', $businessId)->orderByDesc('id')->lockForUpdate()->first();
                    if ($existing?->status === 'closed') {
                        throw new CommandRejection('AUDIT_ENGAGEMENT_CLOSED', revision: $existing->revision, data: ['assignment_id' => $existing->id]);
                    }
                    if ($existing !== null && $existing->status !== 'completed') {
                        return new OperationResult('AUDIT_ASSIGNMENT_RESUMED', ['assignment_id' => $existing->id], $existing->revision);
                    }
                    $record = new AuditAssignment;
                    $record->forceFill(['business_id' => $businessId, 'revision' => 0]);
                    $state = $this->offer($state, $context, $candidates);
                    $this->persist($record, $state, $actorId, 'audit.assignment.request', $reason, $candidates, $context);

                    return new OperationResult('AUDIT_ASSIGNMENT_REQUESTED', ['assignment_id' => $record->id], $record->revision);
                });
        });
    }

    /** @return array<string, mixed> */
    public function respond(int $userId, int $contextRevision, string $assignmentId, int $expectedRevision, string $decision, ?string $conflictKind, string $reason, string $requestId, ?string $reasonCode = null): array
    {
        if (! in_array($decision, ['accept', 'decline', 'conflict'], true)) {
            throw new CommandRejection('ASSIGNMENT_DECISION_INVALID', 422);
        }
        $partyId = $this->actorPartyId($userId);
        $businessId = $this->businessId($assignmentId, $partyId);

        return $this->scope($userId, $contextRevision, $businessId, $decision === 'accept',
            function (array $context, array $candidates) use ($userId, $contextRevision, $assignmentId, $expectedRevision, $decision, $conflictKind, $reason, $requestId, $reasonCode): array {
                $record = AuditAssignment::query()->lockForUpdate()->findOrFail($assignmentId);
                $partyId = $context['actor_party_id'] ?? throw new IdentityViolation('IDENTITY_NOT_LINKED');
                $this->participant($record, $partyId, false);

                return $this->journal->execute('party:'.$partyId, $userId, $decision === 'conflict' ? 'conflict.declare' : 'assignment.'.$decision, $requestId, 'audit.assignment', $assignmentId,
                    ['identity_context_revision' => $contextRevision, 'expected_revision' => $expectedRevision, 'conflict_kind' => $conflictKind, 'reason' => $reason,
                        ...($decision === 'decline' ? ['reason_code' => $reasonCode] : [])],
                    function (): void {}, function () use ($record, $context, $candidates, $partyId, $userId, $expectedRevision, $decision, $conflictKind, $reason, $reasonCode): OperationResult {
                        $this->participant($record, $partyId, true);
                        $this->revision($record, $expectedRevision);
                        $state = $record->state;
                        $conflict = null;
                        if ($decision === 'accept') {
                            $candidate = $this->candidate($candidates, $partyId);
                            $reasons = $candidate === null ? ['AUDITOR_INDEPENDENCE_REVIEW_REQUIRED'] : $this->dispatch->reasons($candidate, now()->toDateTimeImmutable());
                            if ($reasons !== []) {
                                throw new CommandRejection($reasons[0], 403);
                            }
                            $state = $this->states->accept($state, now()->toDateTimeImmutable());
                        } else {
                            if ($decision === 'decline' && $state['status'] !== 'offered') {
                                throw new CommandRejection('ASSIGNMENT_NOT_OFFERED');
                            }
                            if ($decision === 'decline' && $state['accept_by'] <= now('UTC')->format('Y-m-d\TH:i:s\Z')) {
                                throw new CommandRejection('ASSIGNMENT_ACCEPTANCE_EXPIRED');
                            }
                            if ($decision === 'decline') {
                                $this->states->decline($reasonCode, $reason);
                            } else {
                                $this->states->conflict($conflictKind ?? '', $reason);
                                $conflict = new AuditConflictDeclaration;
                                $conflict->forceFill(['assignment_id' => $record->id, 'business_id' => $record->business_id,
                                    'party_id' => $partyId, 'kind' => $conflictKind, 'reason' => $reason, 'actor_user_id' => $userId,
                                    'policy_version' => 'engineering-2026-09-23.4'])->save();
                                $this->reports->withdrawForConflict($record->id, $partyId, $userId, $conflict->id);
                            }
                            $state = $this->offer($state, $context, $candidates);
                        }
                        $this->persist($record, $state, $userId, $decision === 'conflict' ? 'conflict.declare' : 'assignment.'.$decision, $reason === '' ? null : $reason, $candidates, $context);

                        return new OperationResult(match ($decision) {
                            'accept' => 'ASSIGNMENT_ACCEPTED', 'decline' => 'ASSIGNMENT_DECLINED', default => 'CONFLICT_RECORDED',
                        }, ['assignment_id' => $record->id, ...($decision === 'decline' ? ['reason_code' => $reasonCode] : []),
                            ...($conflict === null ? [] : ['conflict_id' => $conflict->id,
                                'outcome' => ['resolution' => $state['status'] === 'operations' ? 'reassignment_pending' : 'reassigned']])], $record->revision);
                    });
            }, $decision === 'accept' ? [$partyId] : null);
    }

    /** @return View */
    public function get(int $userId, int $contextRevision, string $assignmentId): array
    {
        $partyId = $this->actorPartyId($userId);

        return $this->scope($userId, $contextRevision, $this->businessId($assignmentId, $partyId), true,
            function (array $context, array $candidates) use ($assignmentId): array {
                [$record, $candidate] = $this->current($context, $candidates, $assignmentId);

                return $this->view($record, $candidate);
            }, [$partyId]);
    }

    /** @return WorkIdentifiers */
    public function workIdentifiers(int $userId, int $contextRevision, ?string $before, int $limit): array
    {
        $partyId = $this->actorPartyId($userId);

        return $this->roles->handle($userId, 'auditor', $partyId, $contextRevision, function () use ($partyId, $before, $limit): array {
            if ($limit < 1 || $limit > AuditReadPolicy::MAX_PAGE_SIZE || ($before !== null && ! preg_match('/^[0-9a-hjkmnp-tv-z]{26}$/D', $before))) {
                throw new CommandRejection('AUDIT_JOBS_PAGE_INVALID', 422);
            }
            $query = AuditAssignment::query()->where('party_id', $partyId)->whereIn('status', ['offered', 'accepted'])->orderByDesc('id');
            if ($before !== null) {
                $query->where('id', '<', $before);
            }
            $records = $query->limit($limit + 1)->get(['id']);
            $page = $records->take($limit);

            return ['party_id' => $partyId, 'ids' => array_values($page->modelKeys()), 'next_cursor' => $records->count() > $limit ? $page->last()?->id : null];
        });
    }

    /**
     * @template TResult
     *
     * @param  Closure(Work): TResult  $operation
     * @return TResult
     */
    public function withCurrent(int $userId, int $contextRevision, string $assignmentId, Closure $operation): mixed
    {
        $partyId = $this->actorPartyId($userId);

        return $this->scope($userId, $contextRevision, $this->businessId($assignmentId, $partyId), true,
            function (array $context, array $candidates) use ($assignmentId, $operation): mixed {
                [$record, $candidate] = $this->current($context, $candidates, $assignmentId);
                $profile = $context['business']['profile'];

                return $operation(['assignment' => $this->view($record, $candidate),
                    'business' => ['name' => $profile['name'], 'industry' => $profile['industry'], 'district' => $profile['district']],
                    'distance_upper_bound_m' => $candidate['distance_upper_bound_m']]);
            }, [$partyId]);
    }

    /**
     * @param  Candidate  $candidate
     * @return View
     */
    private function view(AuditAssignment $record, array $candidate): array
    {
        $state = $record->state;
        $actions = ['conflict.declare'];
        if ($state['status'] === 'offered') {
            $actions[] = 'assignment.decline';
            if ($this->dispatch->reasons($candidate, now()->toDateTimeImmutable()) === []) {
                $actions[] = 'assignment.accept';
            }
        }

        return ['id' => $record->id, 'business_id' => $record->business_id, 'revision' => $record->revision,
            'kind' => $state['kind'], 'status' => $state['status'], 'offered_at' => $state['offered_at'],
            'accept_by' => $state['accept_by'], 'complete_by' => $state['complete_by'], 'visit_by' => $state['visit_by'], 'allowed_actions' => $actions];
    }

    /**
     * @template TResult
     *
     * @param  Closure(AcceptedAssignment): TResult  $operation
     * @return TResult
     */
    public function withAccepted(int $userId, int $contextRevision, string $assignmentId, Closure $operation): mixed
    {
        $partyId = $this->actorPartyId($userId);

        return $this->scope($userId, $contextRevision, $this->businessId($assignmentId, $partyId), true,
            function (array $context, array $candidates) use ($assignmentId, $operation): mixed {
                [$record, $candidate, $profile] = $this->current($context, $candidates, $assignmentId);
                if ($record->status !== 'accepted') {
                    throw new CommandRejection('ASSIGNMENT_NOT_ACCEPTED', 403);
                }
                if ($candidate['engagement'] === null) {
                    throw new CommandRejection('AUDIT_ENGAGEMENT_ACCEPTANCE_REQUIRED', 403);
                }
                $review = AuditorIndependenceReview::query()->where('business_id', $record->business_id)->where('party_id', $candidate['id'])->firstOrFail();

                return $operation(['id' => $record->id, 'business_id' => $record->business_id, 'party_id' => $candidate['id'],
                    'revision' => $record->revision, 'kind' => $record->state['kind'], 'original_dispatch_at' => $record->state['original_dispatch_at'], 'business_revision' => $context['business']['revision'],
                    'engagement' => $candidate['engagement'],
                    'mandate_version' => $context['business']['mandate_version'], 'mandate_sha256' => hash('sha256', $this->json->encode($context['business']['mandate'])),
                    'independence' => ['id' => $review->id, 'revision' => $review->revision, 'checked_at' => $review->state['checked_at'],
                        'evidence_reference' => $review->state['evidence_reference'], 'sha256' => hash('sha256', $this->json->encode($review->state))],
                    'accreditation' => ['status' => $candidate['standing']['status'],
                        'profile_revision' => $profile->revision, 'licence' => $candidate['standing']['licence'],
                        'expires_on' => $candidate['standing']['expires_on'], 'checked_at' => $candidate['standing']['checked_at'],
                    ]]);
            }, [$partyId]);
    }

    /** @return OwnConflict */
    public function ownConflict(int $userId, int $contextRevision, string $assignmentId): array
    {
        $partyId = $this->actorPartyId($userId);

        return $this->roles->handle($userId, 'auditor', $partyId, $contextRevision, function () use ($assignmentId, $partyId): array {
            $record = $this->ownConflictQuery($partyId)->where('audit_conflict_declarations.assignment_id', $assignmentId)->first()
                ?? throw new CommandRejection('AUDIT_CONFLICT_NOT_FOUND', 404);

            return $this->conflictReceipt($record);
        });
    }

    /** @return ConflictPage */
    public function ownConflicts(int $userId, int $contextRevision, ?string $before, int $limit): array
    {
        $partyId = $this->actorPartyId($userId);

        return $this->roles->handle($userId, 'auditor', $partyId, $contextRevision, function () use ($before, $limit, $partyId): array {
            if ($limit < 1 || $limit > AuditReadPolicy::MAX_PAGE_SIZE || ($before !== null && ! preg_match('/^[0-9a-hjkmnp-tv-z]{26}$/D', $before))) {
                throw new CommandRejection('AUDIT_CONFLICT_PAGE_INVALID', 422);
            }
            $query = $this->ownConflictQuery($partyId)->orderByDesc('audit_conflict_declarations.id');
            if ($before !== null) {
                $query->where('audit_conflict_declarations.id', '<', $before);
            }
            $records = $query->limit($limit + 1)->get();
            $page = $records->take($limit);

            return ['data' => array_values($page->map(fn (AuditConflictDeclaration $record): array => $this->conflictReceipt($record))->all()),
                'next_cursor' => $records->count() > $limit ? $page->last()?->id : null];
        });
    }

    /** @return Builder<AuditConflictDeclaration> */
    private function ownConflictQuery(string $partyId): Builder
    {
        return AuditConflictDeclaration::query()->where('audit_conflict_declarations.party_id', $partyId)
            ->join('audit_assignments', 'audit_assignments.id', '=', 'audit_conflict_declarations.assignment_id')
            ->select('audit_conflict_declarations.*')->addSelect('audit_assignments.status as assignment_status');
    }

    /** @return OwnConflict */
    private function conflictReceipt(AuditConflictDeclaration $record): array
    {
        return ['assignment_id' => $record->assignment_id, 'business_id' => $record->business_id,
            'conflict' => ['conflict_id' => $record->id, 'kind' => $record->kind, 'declared_at' => $record->created_at->toIso8601String(),
                'note' => $record->reason, 'blocking' => true,
                'status' => match ($record->getAttribute('assignment_status')) {
                    'operations' => 'reassignment_pending', 'closed' => 'closed', default => 'reassigned',
                }]];
    }

    /**
     * @template TResult
     *
     * @param  AcceptedAssignment  $assignment
     * @param  Closure(bool): TResult  $operation
     * @return TResult
     */
    public function withVerificationValidity(array $assignment, Closure $operation): mixed
    {
        return DB::transaction(function () use ($assignment, $operation): mixed {
            AuditorProfile::query()->where('party_id', $assignment['party_id'])->lockForUpdate()->first();

            return $operation($this->retainsVerification($assignment));
        }, 3);
    }

    /** @param AcceptedAssignment $assignment */
    public function retainsVerification(array $assignment): bool
    {
        $profile = AuditorProfile::query()->where('party_id', $assignment['party_id'])->first();
        if ($profile === null || $profile->state['standing']['status'] !== 'active' || $profile->state['certificate_id'] === null
            || ! $this->identities->auditorPartyIsActive($assignment['party_id'])) {
            return false;
        }
        $record = AuditAssignment::query()->whereKey($assignment['id'])->where('business_id', $assignment['business_id'])
            ->where('party_id', $assignment['party_id'])->whereIn('status', ['accepted', 'completed'])->first();
        if ($record === null || ! AuditAssignmentVersion::query()->where('assignment_id', $record->id)->where('revision', $assignment['revision'])
            ->where('party_id', $assignment['party_id'])->where('status', 'accepted')->exists()
            || AuditAssignmentVersion::query()->where('assignment_id', $record->id)->where('revision', '>', $assignment['revision'])->whereIn('status', ['offered', 'operations'])->exists()
            || AuditConflictDeclaration::query()->where('business_id', $assignment['business_id'])->where('party_id', $assignment['party_id'])->exists()) {
            return false;
        }
        $review = AuditorIndependenceReview::query()->where('business_id', $assignment['business_id'])->where('party_id', $assignment['party_id'])->first();
        try {
            $facts = $this->independence->facts($review?->state, $assignment['mandate_version'], now()->toDateTimeImmutable());
        } catch (CommandRejection) {
            return false;
        }

        return ! $this->dispatch->hasConflict($facts, now()->toDateTimeImmutable());
    }

    /**
     * @param  AuditContext  $context
     * @param  list<Candidate>  $candidates
     * @return array{AuditAssignment, Candidate, AuditorProfile}
     */
    private function current(array $context, array $candidates, string $assignmentId): array
    {
        $record = AuditAssignment::query()->lockForUpdate()->findOrFail($assignmentId);
        $partyId = $context['actor_party_id'] ?? throw new IdentityViolation('IDENTITY_NOT_LINKED');
        $this->participant($record, $partyId, true);
        $profile = AuditorProfile::query()->where('party_id', $partyId)->firstOrFail();
        $this->standing->requireCurrent($profile->state['standing'], now()->toDateTimeImmutable());
        if ($record->state['status'] === 'offered' && $record->state['accept_by'] <= now('UTC')->format('Y-m-d\TH:i:s\Z')) {
            throw new CommandRejection('ASSIGNMENT_ACCEPTANCE_EXPIRED');
        }
        $candidate = $this->candidate($candidates, $partyId);
        if ($candidate === null || in_array('AUDITOR_CONFLICT', $this->dispatch->reasons($candidate, now()->toDateTimeImmutable()), true)) {
            throw new CommandRejection('AUDITOR_INDEPENDENCE_REVIEW_REQUIRED', 403);
        }

        return [$record, $candidate, $profile];
    }

    /** @return array<string, mixed> */
    public function findOperation(int $userId, int $contextRevision, string $command, string $requestId): array
    {
        if (! in_array($command, ['assignment.accept', 'assignment.decline', 'conflict.declare'], true)) {
            throw new CommandRejection('OPERATION_NOT_FOUND', 404);
        }
        $partyId = $this->identities->forUser($userId)['party']['id'] ?? throw new IdentityViolation('IDENTITY_NOT_LINKED');

        return $this->journal->find('party:'.$partyId, $command, $requestId, function (string $type, string $id) use ($userId, $contextRevision, $partyId): void {
            if ($type !== 'audit.assignment') {
                throw new CommandRejection('OPERATION_NOT_FOUND', 404);
            }
            $this->scope($userId, $contextRevision, $this->businessId($id, $partyId), false, function (array $context) use ($id, $partyId): void {
                if ($context['actor_party_id'] !== $partyId) {
                    throw new CommandRejection('OPERATION_NOT_FOUND', 404);
                }
                $this->participant(AuditAssignment::query()->lockForUpdate()->findOrFail($id), $partyId, false);
            }, [$partyId]);
        });
    }

    public function advanceDue(int $limit): int
    {
        if ($limit < 1 || $limit > 1000) {
            throw new CommandRejection('AUDIT_BATCH_LIMIT_INVALID', 422);
        }
        $count = 0;
        $due = AuditAssignment::query()->where('status', 'offered')->where('accept_by', '<=', now('UTC')->format('Y-m-d\TH:i:s\Z'))->orderBy('accept_by')->orderBy('id')->limit($limit)->get();
        foreach ($due as $offer) {
            try {
                $count += $this->scope(null, null, $offer->business_id, false, function (array $context, array $candidates) use ($offer): int {
                    $record = AuditAssignment::query()->lockForUpdate()->findOrFail($offer->id);
                    if ($record->status !== 'offered' || $record->state['accept_by'] > now('UTC')->format('Y-m-d\TH:i:s\Z')) {
                        return 0;
                    }
                    $state = $this->offer($record->state, $context, $candidates);
                    $this->persist($record, $state, null, 'audit.assignment.expire', 'Offer expired without acceptance.', $candidates, $context);

                    return 1;
                });
            } catch (Throwable $exception) {
                Log::error('AUDIT_OFFER_ADVANCE_FAILED', ['assignment_id' => $offer->id, 'exception_type' => $exception::class]);
            }
        }

        return $count;
    }

    /** @return array<string, mixed> */
    public function advance(int $actorId, string $assignmentId, int $expectedRevision, string $requestId): array
    {
        $this->staff->check($actorId, 'audit.assignments.manage');

        return $this->scope($actorId, null, $this->businessId($assignmentId), false,
            function (array $context, array $candidates) use ($actorId, $assignmentId, $expectedRevision, $requestId): array {
                $record = AuditAssignment::query()->lockForUpdate()->findOrFail($assignmentId);

                return $this->journal->execute('staff:'.$actorId, $actorId, 'audit.assignment.advance', $requestId, 'audit.assignment', $assignmentId,
                    ['expected_revision' => $expectedRevision], function (): void {}, function () use ($record, $context, $candidates, $actorId, $expectedRevision): OperationResult {
                        $this->revision($record, $expectedRevision);
                        $state = $record->state;
                        $now = now('UTC')->format('Y-m-d\TH:i:s\Z');
                        if ($state['status'] !== 'offered' || $state['accept_by'] === null || $state['accept_by'] > $now) {
                            throw new CommandRejection('ASSIGNMENT_NOT_EXPIRED');
                        }
                        $state = $this->offer($state, $context, $candidates);
                        $this->persist($record, $state, $actorId, 'audit.assignment.advance', 'Offer expired without acceptance.', $candidates, $context);

                        return new OperationResult('ASSIGNMENT_ADVANCED', ['assignment_id' => $record->id], $record->revision);
                    });
            });
    }

    /** @return OperationsCase */
    public function operationsCase(int $actorId, string $assignmentId): array
    {
        $this->staff->check($actorId, 'audit.assignments.manage');

        return $this->scope($actorId, null, $this->businessId($assignmentId), false, function () use ($assignmentId): array {
            $record = AuditAssignment::query()->lockForUpdate()->findOrFail($assignmentId);
            $state = $record->state;

            return ['id' => $record->id, 'business_id' => $record->business_id, 'revision' => $record->revision, 'kind' => $state['kind'],
                'status' => $state['status'], 'original_dispatch_at' => $state['original_dispatch_at'], 'complete_by' => $state['complete_by'],
                'attempt' => $state['attempt'], 'operations_reason' => $state['operations_reason'], 'closed_at' => $state['closed_at'] ?? null,
                'allowed_actions' => $this->states->operationsActions($state, now()->toDateTimeImmutable())];
        }, []);
    }

    /** @return array<string, mixed> */
    public function resolve(int $actorId, string $assignmentId, int $expectedRevision, string $decision, string $reason, string $requestId): array
    {
        $this->staff->check($actorId, 'audit.assignments.manage');

        if (! in_array($decision, ['redispatch', 'close'], true)) {
            throw new CommandRejection('ASSIGNMENT_RESOLUTION_INVALID', 422);
        }

        return $this->scope($actorId, null, $this->businessId($assignmentId), false,
            function (array $context, array $candidates) use ($actorId, $assignmentId, $expectedRevision, $decision, $reason, $requestId): array {
                $record = AuditAssignment::query()->lockForUpdate()->findOrFail($assignmentId);
                $command = 'audit.assignment.'.$decision;

                return $this->journal->execute('staff:'.$actorId, $actorId, $command, $requestId, 'audit.assignment', $assignmentId,
                    ['expected_revision' => $expectedRevision, 'reason' => $reason], function (): void {},
                    function () use ($record, $context, $candidates, $actorId, $expectedRevision, $command, $decision, $reason): OperationResult {
                        $this->revision($record, $expectedRevision);
                        $this->states->reason($reason);
                        if ($record->status !== 'operations') {
                            throw new CommandRejection('ASSIGNMENT_NOT_IN_OPERATIONS');
                        }
                        $now = now()->toDateTimeImmutable();
                        if (! in_array($command, $this->states->operationsActions($record->state, $now), true)) {
                            throw new CommandRejection('AUDIT_DISPATCH_EXHAUSTED');
                        }
                        $state = $decision === 'close' ? $this->states->close($record->state, $now) : $this->offer($record->state, $context, $candidates);
                        $this->persist($record, $state, $actorId, $command, $reason, $candidates, $context);

                        return new OperationResult(match ($state['status']) {
                            'closed' => 'ASSIGNMENT_CLOSED', 'offered' => 'ASSIGNMENT_REDISPATCHED', default => 'ASSIGNMENT_REDISPATCH_PENDING',
                        }, ['assignment_id' => $record->id], $record->revision);
                    });
            }, $decision === 'close' ? [] : null);
    }

    /** @return array<string, mixed> */
    public function findResolutionOperation(int $actorId, string $command, string $requestId): array
    {
        $this->staff->check($actorId, 'audit.assignments.manage');

        if (! in_array($command, ['audit.assignment.redispatch', 'audit.assignment.close'], true)) {
            throw new CommandRejection('OPERATION_NOT_FOUND', 404);
        }

        return $this->journal->find('staff:'.$actorId, $command, $requestId, function (string $type, string $id) use ($actorId): void {
            if ($type !== 'audit.assignment') {
                throw new CommandRejection('OPERATION_NOT_FOUND', 404);
            }
            $this->operationsCase($actorId, $id);
        });
    }

    /**
     * Business -> actor -> sorted Parties -> sorted profiles -> shared engagement catalog -> locations -> independence.
     * Responses lock assignment then journal; new requests lock journal then look up the assignment.
     * The Business lock serializes both paths. Participant reads/acceptance lock only their candidate.
     * The Party lock serializes capacity across Businesses and all logins for that Auditor.
     *
     * @template TResult
     *
     * @param  Closure(AuditContext, list<Candidate>): TResult  $operation
     * @param  list<string>|null  $candidateIds
     * @return TResult
     */
    private function scope(?int $userId, ?int $contextRevision, string $businessId, bool $requireVerified, Closure $operation, ?array $candidateIds = null): mixed
    {
        $ids = $candidateIds ?? array_values(AuditorProfile::query()->orderBy('party_id')->get(['party_id'])->map(fn (AuditorProfile $profile): string => $profile->party_id)->all());

        return $this->businesses->withAudit($userId, $contextRevision, $businessId, $ids, $requireVerified,
            function (array $context) use ($ids, $operation): mixed {
                $profiles = AuditorProfile::query()->whereIn('party_id', $ids)->orderBy('party_id')->lockForUpdate()->get();

                return $this->engagements->withCurrentAcceptances($ids, function (array $acceptances) use ($context, $ids, $profiles, $operation): mixed {
                    $businessId = $context['business']['id'];
                    $premises = AuditLocation::query()->where('business_id', $businessId)->lockForUpdate()->first();
                    $offices = AuditLocation::query()->whereIn('office_party_id', $ids)->orderBy('office_party_id')->lockForUpdate()->get()->keyBy('office_party_id');
                    $reviews = AuditorIndependenceReview::query()->where('business_id', $businessId)->whereIn('party_id', $ids)->orderBy('party_id')->lockForUpdate()->get()->keyBy('party_id');
                    $activeCounts = AuditAssignment::query()->whereIn('party_id', $ids)->where('status', 'accepted')->get(['party_id'])->countBy('party_id');
                    $lastOffers = AuditAssignmentVersion::query()->selectRaw('DISTINCT ON (party_id) party_id, snapshot')->whereIn('party_id', $ids)->where('status', 'offered')->orderBy('party_id')->orderByDesc('id')->get()->keyBy('party_id');
                    $reports = array_values(AuditAssignment::query()->where('business_id', $businessId)->where('status', 'completed')->orderByDesc('completed_at')->orderByDesc('id')->limit(3)->get()->map(fn (AuditAssignment $report): string => $report->party_id ?? '')->all());
                    $conflicted = AuditConflictDeclaration::query()->where('business_id', $businessId)->pluck('party_id')->all();
                    $candidates = [];
                    foreach ($profiles as $profile) {
                        if (! in_array($profile->party_id, $context['candidate_ids'], true)) {
                            continue;
                        }
                        try {
                            $facts = $this->independence->facts($reviews->get($profile->party_id)?->state, $context['business']['mandate_version'], now()->toDateTimeImmutable());
                        } catch (CommandRejection) {
                            continue;
                        }
                        $office = $offices->get($profile->party_id)->state ?? $this->locations->empty();
                        $site = $premises->state ?? $this->locations->empty();
                        $tie = $context['role_ties'][$profile->party_id] ?? null;
                        $last = $lastOffers->get($profile->party_id);
                        $candidates[] = ['engagement' => $acceptances[$profile->party_id] ?? null, 'id' => $profile->party_id, 'standing' => $profile->state['standing'], 'accepting' => $profile->state['accepting'],
                            'active_count' => $activeCounts->get($profile->party_id, 0),
                            'consecutive_reports' => $this->consecutive($reports, $profile->party_id),
                            'last_assigned_at' => $last === null ? null : $last->snapshot['state']['offered_at'], 'office' => $office, 'premises' => $site,
                            'distance_upper_bound_m' => $office['point'] === null || $site['point'] === null ? null : $this->distance->upperBound($office['point'], $site['point']),
                            ...$facts, 'current_role_tie' => $facts['current_role_tie'] || ($tie['current'] ?? false),
                            'role_tie_ended_at' => $tie === null ? $facts['role_tie_ended_at'] : max($tie['ended_at'], $facts['role_tie_ended_at']),
                            'unresolved_conflict' => $facts['unresolved_conflict'] || in_array($profile->party_id, $conflicted, true)];
                    }

                    return $operation($context, $candidates);
                });
            });
    }

    /** @param list<string> $reports */
    private function consecutive(array $reports, string $partyId): int
    {
        $count = 0;
        foreach ($reports as $report) {
            if ($report !== $partyId) {
                break;
            }
            $count++;
        }

        return $count;
    }

    /**
     * @param  State  $state
     * @param  AuditContext  $context
     * @param  list<Candidate>  $candidates
     * @return State
     */
    private function offer(array $state, array $context, array $candidates): array
    {
        $available = array_values(array_filter($candidates, fn (array $candidate): bool => ! in_array($candidate['id'], $state['tried'], true)));

        return $this->states->offer($state, $context['current'] ? $this->dispatch->select($available, now()->toDateTimeImmutable()) : null, now()->toDateTimeImmutable());
    }

    /**
     * @param  list<Candidate>  $candidates
     * @return Candidate|null
     */
    private function candidate(array $candidates, string $partyId): ?array
    {
        foreach ($candidates as $candidate) {
            if ($candidate['id'] === $partyId) {
                return $candidate;
            }
        }

        return null;
    }

    private function participant(AuditAssignment $record, string $partyId, bool $current): void
    {
        if (! in_array($partyId, $record->state['tried'], true)) {
            throw new CommandRejection('ASSIGNMENT_NOT_FOUND', 404);
        }
        if ($current && ($record->party_id !== $partyId || ! in_array($record->status, ['offered', 'accepted'], true))) {
            $offer = AuditAssignmentVersion::query()->where('assignment_id', $record->id)->where('party_id', $partyId)
                ->where('status', 'offered')->orderByDesc('revision')->first(['revision']);
            $exit = $offer === null ? null : AuditAssignmentVersion::query()->where('assignment_id', $record->id)
                ->where('revision', '>', $offer->revision)->orderBy('revision')->first(['command']);
            if ($exit !== null && in_array($exit->command, ['audit.assignment.expire', 'audit.assignment.advance'], true)) {
                throw new CommandRejection('ASSIGNMENT_ACCEPTANCE_EXPIRED');
            }
            throw new CommandRejection('ASSIGNMENT_NOT_FOUND', 404);
        }
    }

    private function actorPartyId(int $userId): string
    {
        return $this->identities->forUser($userId)['party']['id'] ?? throw new IdentityViolation('IDENTITY_NOT_LINKED');
    }

    private function businessId(string $id, ?string $participantId = null): string
    {
        $record = AuditAssignment::query()->find($id) ?? throw new CommandRejection('ASSIGNMENT_NOT_FOUND', 404);
        if ($participantId !== null) {
            $this->participant($record, $participantId, false);
        }

        return $record->business_id;
    }

    private function revision(AuditAssignment $record, int $expected): void
    {
        if ($record->revision !== $expected) {
            throw new CommandRejection('VERSION_CONFLICT', 409, $record->revision);
        }
    }

    /**
     * @param  State  $state
     * @param  list<Candidate>  $candidates
     * @param  AuditContext  $context
     */
    private function persist(AuditAssignment $record, array $state, ?int $actorId, string $command, ?string $reason, array $candidates, array $context): void
    {
        $record->forceFill(['revision' => $record->revision + 1, 'state' => $state, 'status' => $state['status'], 'party_id' => $state['party_id'], 'accept_by' => $state['accept_by']])->save();
        (new AuditAssignmentVersion)->forceFill(['assignment_id' => $record->id, 'revision' => $record->revision, 'party_id' => $state['party_id'], 'status' => $state['status'],
            'snapshot' => $this->snapshot($record), 'selection_basis' => ['business_revision' => $context['business']['revision'], 'mandate_version' => $context['business']['mandate_version'], 'candidates' => $candidates], 'actor_user_id' => $actorId, 'command' => $command,
            'reason' => $reason, 'policy_version' => 'engineering-2026-09-23.4'])->save();
    }

    /** @return Assignment */
    private function snapshot(AuditAssignment $record): array
    {
        return ['id' => $record->id, 'business_id' => $record->business_id, 'revision' => $record->revision, 'state' => $record->state];
    }
}
