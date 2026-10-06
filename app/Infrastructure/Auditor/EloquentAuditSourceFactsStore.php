<?php

declare(strict_types=1);

namespace App\Infrastructure\Auditor;

use App\Application\Auditor\Contracts\AuditSourceFactsStore;
use App\Application\Auditor\WithAcceptedAuditAssignment;
use App\Application\Business\WithBusinessReview;
use App\Application\Environment\EnvironmentIsolation;
use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Operations\Contracts\OperationJournal;
use App\Domain\Auditor\AuditEngagementDocuments;
use App\Domain\Auditor\AuditSourceFacts;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Models\AuditAssignment;
use App\Models\AuditSourceSnapshot;

/**
 * Staff publication locks the Business before the assignment, the same order every accepted
 * Auditor read uses, so a read holds one stable source revision through its effect.
 *
 * @phpstan-import-type AcceptedAssignment from \App\Application\Auditor\Contracts\AuditAssignmentStore
 * @phpstan-import-type SourceFacts from AuditSourceFactsStore
 */
final class EloquentAuditSourceFactsStore implements AuditSourceFactsStore
{
    public function __construct(
        private WithBusinessReview $businesses,
        private WithAcceptedAuditAssignment $assignments,
        private AuthorizeStaffPermission $staff,
        private EnvironmentIsolation $isolation,
        private AuditSourceFacts $facts,
        private OperationJournal $journal,
        private CanonicalJson $json,
    ) {}

    /**
     * @param  array<string, mixed>|null  $facts
     * @return array<string, mixed>
     */
    public function recordFixture(int $actorId, string $assignmentId, int $assignmentRevision, int $expectedRevision, ?array $facts, string $sourceReference, string $reason, string $requestId): array
    {
        $this->staff->check($actorId, 'businesses.verify');
        $this->isolation->assertSeedingAllowed();
        $businessId = AuditAssignment::query()->whereKey($assignmentId)->value('business_id') ?? throw new CommandRejection('ASSIGNMENT_NOT_FOUND', 404);

        return $this->businesses->handle($actorId, $businessId, false, function () use ($actorId, $assignmentId, $assignmentRevision, $expectedRevision, $facts, $sourceReference, $reason, $requestId): array {
            return $this->journal->execute('staff:'.$actorId, $actorId, 'audit.source.fixture', $requestId, 'audit.assignment', $assignmentId,
                ['assignment_revision' => $assignmentRevision, 'expected_revision' => $expectedRevision, 'facts' => $facts,
                    'source_reference' => $sourceReference, 'reason' => $reason], function (): void {},
                function () use ($actorId, $assignmentId, $assignmentRevision, $expectedRevision, $facts, $sourceReference, $reason): OperationResult {
                    if ($expectedRevision < 0 || ! str_starts_with($sourceReference, 'synthetic:') || strlen($sourceReference) > 255
                        || trim(substr($sourceReference, 10)) === '' || ! mb_check_encoding($sourceReference, 'UTF-8') || preg_match('/[\p{Cc}\p{Cf}]/u', $sourceReference)
                        || trim($reason) === '' || mb_strlen($reason) > 2000 || ! mb_check_encoding($reason, 'UTF-8') || preg_match('/[\p{Cc}\p{Cf}]/u', $reason)) {
                        throw new CommandRejection('AUDIT_SOURCE_INVALID', 422);
                    }
                    $assignment = AuditAssignment::query()->lockForUpdate()->findOrFail($assignmentId);
                    if ($assignment->status !== 'accepted' || $assignment->party_id === null) {
                        throw new CommandRejection('ASSIGNMENT_NOT_ACCEPTED', revision: $assignment->revision);
                    }
                    if ($assignment->revision !== $assignmentRevision) {
                        throw new CommandRejection('ASSIGNMENT_VERSION_CONFLICT', revision: $assignment->revision);
                    }
                    $prior = AuditSourceSnapshot::query()->where('assignment_id', $assignment->id)
                        ->where('assignment_revision', $assignment->revision)->orderByDesc('revision')->first();
                    $revision = $prior->revision ?? 0;
                    if ($expectedRevision !== $revision) {
                        throw new CommandRejection('VERSION_CONFLICT', revision: $revision);
                    }
                    $record = new AuditSourceSnapshot;
                    $record->forceFill(['assignment_id' => $assignment->id, 'business_id' => $assignment->business_id,
                        'assignment_revision' => $assignment->revision, 'party_id' => $assignment->party_id, 'revision' => $revision + 1,
                        'status' => $facts === null ? 'withdrawn' : 'available', 'source_kind' => AuditSourceFacts::SOURCE_KIND,
                        'source_reference' => $sourceReference, 'procedure_version' => AuditEngagementDocuments::PROCEDURE,
                        'facts' => $facts === null ? null : $this->facts->normalize($facts, now()->toDateTimeImmutable()),
                        'actor_user_id' => $actorId, 'reason' => $reason]);
                    $record->forceFill(['sha256' => $this->digest($record)])->save();

                    return new OperationResult('AUDIT_SOURCE_FACTS_RECORDED', ['audit_source' => ['id' => $record->id, 'revision' => $record->revision,
                        'status' => $record->status, 'source_kind' => AuditSourceFacts::SOURCE_KIND, 'sha256' => $record->sha256,
                        'assignment_id' => $record->assignment_id, 'assignment_revision' => $record->assignment_revision]], $record->revision);
                });
        });
    }

    /** @return SourceFacts|null */
    public function forAssignment(int $userId, int $contextRevision, string $assignmentId): ?array
    {
        return $this->assignments->handle($userId, $contextRevision, $assignmentId, fn (array $assignment): ?array => $this->forAccepted($assignment));
    }

    /**
     * @param  AcceptedAssignment  $assignment
     * @return SourceFacts|null
     */
    public function forAccepted(array $assignment): ?array
    {
        if (! $this->isolation->canSeed()) {
            return null;
        }
        $record = AuditSourceSnapshot::query()->where('assignment_id', $assignment['id'])
            ->where('assignment_revision', $assignment['revision'])->orderByDesc('revision')->first();
        if ($record === null) {
            return null;
        }
        if ($record->party_id !== $assignment['party_id'] || $record->business_id !== $assignment['business_id']
            || $record->source_kind !== AuditSourceFacts::SOURCE_KIND || ! hash_equals($record->sha256, $this->digest($record))) {
            throw new CommandRejection('AUDIT_SOURCE_FACTS_CORRUPTED');
        }
        $facts = $record->facts;
        if ($record->status !== 'available' || $facts === null || $record->procedure_version !== AuditEngagementDocuments::PROCEDURE) {
            return null;
        }

        return ['source' => ['id' => $record->id, 'revision' => $record->revision, 'sha256' => $record->sha256, 'kind' => AuditSourceFacts::SOURCE_KIND,
            'reference' => $record->source_reference, 'procedure_version' => $record->procedure_version,
            'assignment_id' => $record->assignment_id, 'assignment_revision' => $record->assignment_revision],
            'declared_stock_rwf' => $facts['declared_stock_rwf'], 'declared_stock_units' => $facts['declared_stock_units'],
            'declared_unit_label' => $facts['declared_unit_label'], 'declared_sector_label' => $facts['declared_sector_label'],
            'declared_account_label' => $facts['declared_account_label'], 'check_in' => $facts['check_in'],
            'photos' => $facts['photos'], 'proof_ids' => $facts['proof_ids']];
    }

    private function digest(AuditSourceSnapshot $record): string
    {
        return hash('sha256', $this->json->encode(['assignment_id' => $record->assignment_id, 'business_id' => $record->business_id,
            'assignment_revision' => $record->assignment_revision, 'party_id' => $record->party_id, 'revision' => $record->revision,
            'status' => $record->status, 'source_kind' => $record->source_kind, 'source_reference' => $record->source_reference,
            'procedure_version' => $record->procedure_version, 'facts' => $record->facts]));
    }
}
