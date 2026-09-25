<?php

declare(strict_types=1);

namespace App\Infrastructure\Auditor;

use App\Application\Auditor\Contracts\AuditReportStore;
use App\Application\Auditor\WithAcceptedAuditAssignment;
use App\Application\Business\WithAuditApplicationBinding;
use App\Application\Identity\Contracts\IdentityRepository;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Operations\Contracts\OperationJournal;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Models\AuditReport;
use App\Models\AuditReportVersion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as Query;
use RuntimeException;

/**
 * @phpstan-import-type AcceptedAssignment from \App\Application\Auditor\Contracts\AuditAssignmentStore
 * @phpstan-import-type AuditBinding from \App\Application\Business\Contracts\BusinessApplicationStore
 * @phpstan-import-type Report from AuditReportStore
 */
final class EloquentAuditReportStore implements AuditReportStore
{
    public function __construct(
        private WithAcceptedAuditAssignment $assignments,
        private WithAuditApplicationBinding $applications,
        private IdentityRepository $identities,
        private OperationJournal $journal,
        private CanonicalJson $json,
    ) {}

    /** @return array<string, mixed> */
    public function start(int $userId, int $contextRevision, string $assignmentId, int $expectedRevision, string $applicationId, int $applicationRevision, string $requestId): array
    {
        return $this->assignments->handle($userId, $contextRevision, $assignmentId,
            function (array $assignment) use ($userId, $contextRevision, $assignmentId, $expectedRevision, $applicationId, $applicationRevision, $requestId): array {
                return $this->journal->execute('party:'.$assignment['party_id'], $userId, 'audit.start', $requestId, 'audit.assignment', $assignmentId,
                    ['identity_context_revision' => $contextRevision, 'expected_revision' => $expectedRevision,
                        'application_id' => $applicationId, 'application_revision' => $applicationRevision], function (): void {},
                    function () use ($assignment, $userId, $contextRevision, $assignmentId, $expectedRevision, $applicationId, $applicationRevision): OperationResult {
                        if ($assignment['revision'] !== $expectedRevision) {
                            throw new CommandRejection('VERSION_CONFLICT', revision: $assignment['revision']);
                        }

                        return $this->applications->handle($userId, $contextRevision, $assignmentId, $applicationId,
                            function (array $current, array $application) use ($userId, $applicationRevision): OperationResult {
                                if ($application['application']['revision'] !== $applicationRevision) {
                                    throw new CommandRejection('APPLICATION_VERSION_CONFLICT', revision: $application['application']['revision']);
                                }
                                $existing = $this->latest($current)->lockForUpdate()->first();
                                if ($existing !== null) {
                                    $this->author($existing, $current);
                                    if ($existing->application_id !== $application['application']['id']) {
                                        throw new CommandRejection('AUDIT_APPLICATION_BOUND', revision: $existing->revision);
                                    }
                                    $report = $this->view($existing);

                                    return $this->receipt('AUDIT_REPORT_RESUMED', $report);
                                }
                                $binding = ['assignment' => ['id' => $current['id'], 'revision' => $current['revision'], 'party_id' => $current['party_id']], 'engagement' => $current['engagement'], 'application' => $application];
                                $report = new AuditReport;
                                $report->forceFill(['assignment_id' => $current['id'], 'business_id' => $current['business_id'],
                                    'assignment_revision' => $current['revision'], 'author_party_id' => $current['party_id'],
                                    'engagement_acceptance_id' => $current['engagement']['id'],
                                    'application_id' => $application['application']['id'], 'application_revision' => $applicationRevision,
                                    'application_version_id' => $application['version']['id'], 'submission_id' => $application['submission']['id'],
                                    'quote_id' => $application['quote']['id'], 'amends_id' => null, 'revision' => 1,
                                    'kind' => $current['kind'] === 'routine' ? 'monthly' : 'flash', 'status' => 'draft',
                                    'step' => $current['kind'] === 'routine' ? 'statements' : 'review',
                                    'binding' => $binding, 'binding_sha256' => $this->hash($binding),
                                    'draft' => ['note' => '', 'completed_steps' => [], 'fields' => []]])->save();
                                $this->append($report, $current['party_id'], $userId, 'audit.start');

                                return $this->receipt('AUDIT_REPORT_STARTED', $this->view($report));
                            });
                    });
            });
    }

    /** @return Report */
    public function get(int $userId, int $contextRevision, string $reportId): array
    {
        $partyId = $this->party($userId);
        $record = AuditReport::query()->whereKey($reportId)->where('author_party_id', $partyId)
            ->whereExists(fn (Query $query): Query => $query->selectRaw('1')->from('audit_assignments')
                ->whereColumn('audit_assignments.id', 'audit_reports.assignment_id')->where('party_id', $partyId))->first()
            ?? throw new CommandRejection('AUDIT_REPORT_NOT_FOUND', 404);

        return $this->assignments->handle($userId, $contextRevision, $record->assignment_id, function (array $assignment) use ($reportId): array {
            $record = AuditReport::query()->lockForUpdate()->findOrFail($reportId);
            $this->author($record, $assignment);

            return $this->view($record);
        });
    }

    /** @return Report|null */
    public function forAssignment(int $userId, int $contextRevision, string $assignmentId): ?array
    {
        return $this->assignments->handle($userId, $contextRevision, $assignmentId, function (array $assignment): ?array {
            $record = $this->latest($assignment)->lockForUpdate()->first();
            if ($record === null) {
                return null;
            }
            $this->author($record, $assignment);

            return $this->view($record);
        });
    }

    /** @return array<string, mixed> */
    public function findOperation(int $userId, int $contextRevision, string $command, string $requestId): array
    {
        if ($command !== 'audit.start') {
            throw new CommandRejection('OPERATION_NOT_FOUND', 404);
        }

        $partyId = $this->party($userId);

        return $this->journal->find('party:'.$partyId, $command, $requestId,
            function (string $type, string $id) use ($userId, $contextRevision, $partyId): void {
                if ($type !== 'audit.assignment') {
                    throw new CommandRejection('OPERATION_NOT_FOUND', 404);
                }
                $this->assignments->handle($userId, $contextRevision, $id, function (array $assignment) use ($partyId): void {
                    if ($assignment['party_id'] !== $partyId) {
                        throw new CommandRejection('OPERATION_NOT_FOUND', 404);
                    }
                });
            });
    }

    /**
     * @param  AcceptedAssignment  $assignment
     * @return Builder<AuditReport>
     */
    private function latest(array $assignment): Builder
    {
        return AuditReport::query()->where('assignment_id', $assignment['id'])->where('assignment_revision', $assignment['revision'])
            ->where('author_party_id', $assignment['party_id'])
            ->whereNotExists(fn (Query $query): Query => $query->selectRaw('1')->from('audit_reports as amendments')
                ->whereColumn('amendments.amends_id', 'audit_reports.id'));
    }

    /** @param AcceptedAssignment $assignment */
    private function author(AuditReport $record, array $assignment): void
    {
        if ($record->author_party_id !== $assignment['party_id'] || $record->assignment_revision !== $assignment['revision']) {
            throw new CommandRejection('AUDIT_REPORT_REASSIGNMENT_REQUIRED', revision: $record->revision);
        }
    }

    private function append(AuditReport $record, string $partyId, int $userId, string $command): void
    {
        $snapshot = $this->snapshot($record);
        $version = new AuditReportVersion;
        $version->forceFill(['audit_report_id' => $record->id, 'revision' => $record->revision, 'status' => $record->status,
            'step' => $record->step, 'snapshot' => $snapshot, 'sha256' => $this->hash($snapshot),
            'actor_party_id' => $partyId, 'actor_user_id' => $userId, 'command' => $command])->save();
    }

    /** @return array<string, mixed> */
    private function snapshot(AuditReport $record): array
    {
        return ['id' => $record->id, 'revision' => $record->revision, 'status' => $record->status, 'step' => $record->step,
            'binding_sha256' => $record->binding_sha256, 'draft' => $record->draft];
    }

    /** @return Report */
    private function view(AuditReport $record): array
    {
        $version = AuditReportVersion::query()->where('audit_report_id', $record->id)->where('revision', $record->revision)->first();
        if (! hash_equals($record->binding_sha256, $this->hash($record->binding)) || $version === null
            || ($record->binding['engagement']['id'] ?? null) !== $record->engagement_acceptance_id
            || ! hash_equals($version->sha256, $this->hash($version->snapshot))
            || $this->json->encode($version->snapshot) !== $this->json->encode($this->snapshot($record))) {
            throw new RuntimeException('AUDIT_REPORT_INTEGRITY_FAILED');
        }

        return ['id' => $record->id, 'assignment_id' => $record->assignment_id, 'business_id' => $record->business_id,
            'application_id' => $record->application_id, 'application_revision' => $record->application_revision,
            'revision' => $record->revision, 'kind' => $record->kind, 'status' => $record->status, 'step' => $record->step,
            'amends_id' => $record->amends_id, 'binding_sha256' => $record->binding_sha256, 'draft' => $record->draft,
            'version' => ['id' => $version->id, 'sha256' => $version->sha256]];
    }

    /** @param Report $report */
    private function receipt(string $code, array $report): OperationResult
    {
        return new OperationResult($code, ['audit_id' => $report['id'], 'assignment_id' => $report['assignment_id']], $report['revision']);
    }

    private function party(int $userId): string
    {
        return $this->identities->forUser($userId)['party']['id'] ?? throw new IdentityViolation('IDENTITY_NOT_LINKED');
    }

    /** @param array<string, mixed> $value */
    private function hash(array $value): string
    {
        return hash('sha256', $this->json->encode($value));
    }
}
