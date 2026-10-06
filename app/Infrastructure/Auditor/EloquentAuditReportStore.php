<?php

declare(strict_types=1);

namespace App\Infrastructure\Auditor;

use App\Application\Auditor\BuildAuditReportPreview;
use App\Application\Auditor\Contracts\AuditLedgerEvidence;
use App\Application\Auditor\Contracts\AuditReportCryptography;
use App\Application\Auditor\Contracts\AuditReportPublicationStore;
use App\Application\Auditor\Contracts\AuditReportStore;
use App\Application\Auditor\Contracts\AuditStepUp;
use App\Application\Auditor\GetAuditProcedureSources;
use App\Application\Auditor\WithAcceptedAuditAssignment;
use App\Application\Business\Contracts\BusinessAuthorityStore;
use App\Application\Business\WithAuditApplicationBinding;
use App\Application\Identity\Contracts\IdentityRepository;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Operations\Contracts\OperationJournal;
use App\Domain\Auditor\AuditProcedure;
use App\Domain\Auditor\AuditReportDecision;
use App\Domain\Auditor\MonthlyReportReview;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Models\AuditReport;
use App\Models\AuditReportSeal;
use App\Models\AuditReportVersion;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as Query;
use RuntimeException;

/**
 * @phpstan-import-type AcceptedAssignment from \App\Application\Auditor\Contracts\AuditAssignmentStore
 * @phpstan-import-type AuditBinding from \App\Application\Business\Contracts\BusinessApplicationStore
 * @phpstan-import-type Report from AuditReportStore
 * @phpstan-import-type Draft from AuditProcedure
 * @phpstan-import-type Procedure from AuditReportStore
 * @phpstan-import-type Original from \App\Application\Evidence\Contracts\StatementStore
 * @phpstan-import-type Projection from GetAuditProcedureSources
 */
final class EloquentAuditReportStore implements AuditReportStore
{
    public function __construct(
        private WithAcceptedAuditAssignment $assignments,
        private WithAuditApplicationBinding $applications,
        private IdentityRepository $identities,
        private OperationJournal $journal,
        private CanonicalJson $json,
        private AuditProcedure $procedure,
        private GetAuditProcedureSources $sources,
        private AuditLedgerEvidence $ledgers,
        private AuditReportPublicationStore $publications,
        private BusinessAuthorityStore $businesses,
        private AuditStepUp $stepUpProofs,
        private BuildAuditReportPreview $previews,
        private AuditReportCryptography $cryptography,
    ) {}

    /** @return array{proof: string, expires_at: string} */
    public function stepUp(int $userId, int $contextRevision, string $reportId, int $expectedRevision, string $digest, string $code): array
    {
        $record = $this->owned($reportId, $this->party($userId));

        return $this->assignments->handle($userId, $contextRevision, $record->assignment_id,
            function (array $assignment) use ($userId, $contextRevision, $reportId, $expectedRevision, $digest, $code): array {
                $record = AuditReport::query()->lockForUpdate()->findOrFail($reportId);
                $this->author($record, $assignment);
                $this->sealablePreview($record, $userId, $contextRevision, $assignment, $expectedRevision, $digest);
                if (! $this->cryptography->available()) {
                    throw new CommandRejection('SEAL_KEY_UNAVAILABLE', 503);
                }

                return $this->stepUpProofs->issue($userId, $assignment['party_id'], $contextRevision, $record->id, $record->revision, $digest, $code);
            });
    }

    /** @param array<string, mixed> $fields
     * @return array<string, mixed>
     */
    public function seal(int $userId, int $contextRevision, string $reportId, int $expectedRevision, array $fields, string $proof, string $requestId): array
    {
        $record = $this->owned($reportId, $this->party($userId));

        return $this->assignments->handle($userId, $contextRevision, $record->assignment_id,
            function (array $assignment) use ($userId, $contextRevision, $reportId, $expectedRevision, $fields, $proof, $requestId): array {
                $record = AuditReport::query()->lockForUpdate()->findOrFail($reportId);
                $this->author($record, $assignment);
                $this->view($record);

                return $this->journal->execute('party:'.$assignment['party_id'], $userId, 'audit.seal', $requestId, 'audit.report', $reportId,
                    $this->inputFingerprint(['identity_context_revision' => $contextRevision, 'expected_revision' => $expectedRevision, 'fields' => $fields,
                        'proof_sha256' => hash('sha256', $proof)]), function (): void {},
                    function () use ($record, $userId, $contextRevision, $assignment, $expectedRevision, $fields, $proof): OperationResult {
                        $preview = $this->sealablePreview($record, $userId, $contextRevision, $assignment, $expectedRevision, $fields['digest'] ?? '');
                        $expected = ['digest' => $preview['digest'], 'procedure_version' => $preview['payload']['procedure_version'],
                            'findings_version' => $preview['findings_version'], 'evidence_version' => $preview['evidence_version'],
                            'evidence_ids' => $preview['evidence_ids'], 'note' => $record->draft['note']];
                        $actual = $fields;
                        if (isset($actual['evidence_ids']) && is_array($actual['evidence_ids'])) {
                            sort($actual['evidence_ids']);
                        }
                        try {
                            $matches = $this->json->encode($actual) === $this->json->encode($expected);
                        } catch (CommandRejection) {
                            $matches = false;
                        }
                        if (! $matches) {
                            throw new CommandRejection('DIGEST_STALE', revision: $record->revision);
                        }
                        $at = now('UTC')->format('Y-m-d\TH:i:s\Z');
                        $business = $this->businesses->withAudit($userId, $contextRevision, $record->business_id, [$assignment['party_id']], true, fn (array $context): array => $context['business']);
                        $payload = ['business' => $business, 'auditor_name' => $this->identities->accountName($userId), 'report' => $preview['payload'], 'digest' => $preview['digest'], 'sources' => $preview['sources'],
                            'assignment' => $assignment, 'sealed_revision' => $record->revision + 1, 'sealed_at' => $at,
                            'author_party_id' => $assignment['party_id'], 'actor_user_id' => $userId, 'synthetic' => true,
                            'publication_policy' => $record->kind === 'monthly' ? MonthlyReportReview::POLICY : MonthlyReportReview::LEGACY_POLICY];
                        $signature = $this->cryptography->sign($payload);
                        $proofId = $this->stepUpProofs->consume($userId, $assignment['party_id'], $contextRevision, $record->id, $record->revision, $preview['digest'], $proof);
                        $seal = new AuditReportSeal;
                        $seal->forceFill(['audit_report_id' => $record->id, 'report_revision' => $record->revision + 1,
                            'audit_signing_key_id' => $signature['key_id'], 'step_up_proof_id' => $proofId, 'author_party_id' => $assignment['party_id'], 'actor_user_id' => $userId,
                            'digest' => $preview['digest'], 'payload' => $payload, 'jws' => $signature['jws'], 'created_at' => $at])->save();
                        $record->forceFill(['revision' => $record->revision + 1, 'status' => 'sealed',
                            'draft' => [...$record->draft, 'seal_id' => $seal->id]])->save();
                        $this->append($record, $assignment['party_id'], $userId, 'audit.seal');
                        $this->publications->open($record->id, $seal->digest, $payload);

                        return new OperationResult('AUDIT_SEALED', ['audit_id' => $record->id, 'assignment_id' => $record->assignment_id,
                            'sealed' => ['sealed_at' => $at, 'digest' => $seal->digest, 'licence' => $preview['payload']['licence'],
                                'signature_ref' => $seal->id, 'report_id' => $record->id, 'key_id' => $seal->audit_signing_key_id]], $record->revision);
                    });
            });
    }

    /** @param AcceptedAssignment $assignment
     * @return array<string, mixed>
     */
    private function sealablePreview(AuditReport $record, int $userId, int $contextRevision, array $assignment, int $expectedRevision, string $digest): array
    {
        if ($record->revision !== $expectedRevision) {
            throw new CommandRejection('VERSION_CONFLICT', revision: $record->revision);
        }
        if ($record->status !== 'draft' || $record->step !== 'seal') {
            throw new CommandRejection('AUDIT_PROCEDURE_INCOMPLETE', revision: $record->revision);
        }
        $report = $this->view($record);
        $sources = $this->procedureSources($record, $userId, $contextRevision, $assignment);
        /** @var Draft $draft */
        $draft = $record->draft;
        $unavailable = $this->procedure->unavailable($record->kind, $record->step, $draft, 'seal', $sources);
        if ($unavailable !== null) {
            throw new CommandRejection($unavailable, revision: $record->revision);
        }
        $preview = $this->previews->handle($report, $sources);
        if ($preview['note_missing']) {
            throw new CommandRejection('AUDIT_NOTE_REQUIRED', 422, $record->revision, ['note' => ['Explain the factual difference before sealing.']]);
        }
        if (! hash_equals($preview['digest'], $digest)) {
            throw new CommandRejection('DIGEST_STALE', revision: $record->revision);
        }
        $ids = array_values(array_unique(array_filter([$sources['verification']['id'] ?? null, $sources['source_facts']['source']['id'] ?? null,
            ...array_column($sources['documents'], 'id'), ...array_column($sources['ledger_documents'] ?? [], 'id')])));
        sort($ids);

        return [...$preview, 'sources' => $sources, 'evidence_ids' => $ids];
    }

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
                                $period = $current['kind'] === 'routine' && isset($current['original_dispatch_at'])
                                    ? CarbonImmutable::parse($current['original_dispatch_at'])->setTimezone('Africa/Kigali')->startOfMonth()->subMonth()->format('Y-m') : null;
                                $binding = ['assignment' => ['id' => $current['id'], 'revision' => $current['revision'], 'party_id' => $current['party_id']],
                                    'engagement' => $current['engagement'], 'application' => $application, 'period' => $period];
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

    /**
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    public function saveStep(int $userId, int $contextRevision, string $reportId, int $expectedRevision, string $step, array $fields, string $requestId): array
    {
        $partyId = $this->party($userId);
        $record = $this->owned($reportId, $partyId);

        return $this->assignments->handle($userId, $contextRevision, $record->assignment_id,
            function (array $assignment) use ($userId, $contextRevision, $reportId, $expectedRevision, $step, $fields, $requestId): array {
                $record = AuditReport::query()->lockForUpdate()->findOrFail($reportId);
                $this->author($record, $assignment);
                $this->view($record);

                return $this->journal->execute('party:'.$assignment['party_id'], $userId, 'audit.save_step', $requestId, 'audit.report', $reportId,
                    $this->inputFingerprint(['identity_context_revision' => $contextRevision, 'expected_revision' => $expectedRevision, 'step' => $step, 'fields' => $fields]),
                    function (): void {}, function () use ($record, $assignment, $userId, $contextRevision, $expectedRevision, $step, $fields): OperationResult {
                        if ($record->revision !== $expectedRevision) {
                            throw new CommandRejection('VERSION_CONFLICT', revision: $record->revision);
                        }
                        if ($record->status !== 'draft') {
                            throw new CommandRejection('AUDIT_REPORT_NOT_EDITABLE', revision: $record->revision);
                        }
                        /** @var Draft $draft */
                        $draft = $record->draft;
                        $next = $this->procedure->save($record->kind, $record->step, $draft, $step, $fields,
                            $this->procedureSources($record, $userId, $contextRevision, $assignment));
                        $record->forceFill(['revision' => $record->revision + 1, 'step' => $next['step'], 'draft' => $next['draft']])->save();
                        $this->append($record, $assignment['party_id'], $userId, 'audit.save_step');

                        return new OperationResult('AUDIT_STEP_SAVED', ['audit_id' => $record->id, 'assignment_id' => $assignment['id'], 'step' => $record->step], $record->revision);
                    });
            });
    }

    /** @return array<string, mixed> */
    public function ingestLedger(int $userId, int $contextRevision, string $reportId, int $expectedRevision, string $filename, string $content, ?string $replaces, string $requestId): array
    {
        $record = $this->owned($reportId, $this->party($userId));

        return $this->assignments->handle($userId, $contextRevision, $record->assignment_id,
            function (array $assignment) use ($userId, $contextRevision, $reportId, $expectedRevision, $filename, $content, $replaces, $requestId): array {
                $record = AuditReport::query()->lockForUpdate()->findOrFail($reportId);
                $this->author($record, $assignment);
                $this->view($record);

                return $this->journal->execute('party:'.$assignment['party_id'], $userId, 'audit.save_step', $requestId, 'audit.report', $reportId,
                    $this->inputFingerprint(['identity_context_revision' => $contextRevision, 'expected_revision' => $expectedRevision, 'step' => 'ledger',
                        'upload' => ['filename' => $filename, 'sha256' => hash('sha256', $content), 'replaces' => $replaces]]),
                    function (): void {}, function () use ($record, $assignment, $userId, $expectedRevision, $filename, $content, $replaces): OperationResult {
                        if ($record->revision !== $expectedRevision) {
                            throw new CommandRejection('VERSION_CONFLICT', revision: $record->revision);
                        }
                        if ($record->status !== 'draft' || $record->kind !== 'flash' || $record->step !== 'ledger') {
                            throw new CommandRejection('AUDIT_LEDGER_NOT_AVAILABLE', revision: $record->revision);
                        }
                        try {
                            $ingested = $this->ledgers->retain($record->id, $record->revision, $record->author_party_id, $userId, $record->draft['documents'] ?? [], $filename, $content, $replaces);
                        } catch (CommandRejection $failure) {
                            throw new CommandRejection($failure->reason, $failure->status, $record->revision,
                                isset($failure->fieldErrors['file']) ? ['document' => $failure->fieldErrors['file']] : $failure->fieldErrors, $failure->data);
                        }
                        $draft = $record->draft;
                        $draft['documents'] = array_values(array_filter($draft['documents'] ?? [],
                            fn (array $document): bool => $document['id'] !== $replaces && $document['id'] !== $ingested['id']));
                        $draft['documents'][] = $ingested;
                        $record->forceFill(['revision' => $record->revision + 1, 'draft' => $draft])->save();
                        $this->append($record, $assignment['party_id'], $userId, 'audit.save_step');

                        return new OperationResult('INGESTED_NOT_AUDIT_APPROVED', ['audit_id' => $record->id, 'assignment_id' => $assignment['id'],
                            'step' => 'ledger', 'document_id' => $ingested['id']], $record->revision);
                    });
            });
    }

    /** @return Original */
    public function readLedger(int $userId, int $contextRevision, string $reportId, string $documentId): array
    {
        $record = $this->owned($reportId, $this->party($userId));

        return $this->assignments->handle($userId, $contextRevision, $record->assignment_id,
            function (array $assignment) use ($reportId, $documentId): array {
                $record = AuditReport::query()->sharedLock()->findOrFail($reportId);
                $this->author($record, $assignment);
                $this->view($record);

                return $this->ledgers->read($record->id, $documentId);
            });
    }

    /** @return array<string, mixed> */
    public function decide(int $userId, int $contextRevision, string $reportId, int $expectedRevision, bool $reject, mixed $reasonCode, mixed $reason, string $requestId): array
    {
        $record = $this->owned($reportId, $this->party($userId));
        $command = $reject ? 'audit.reject' : 'audit.request_changes';

        return $this->assignments->handle($userId, $contextRevision, $record->assignment_id,
            function (array $assignment) use ($userId, $contextRevision, $reportId, $expectedRevision, $reject, $reasonCode, $reason, $requestId, $command): array {
                $record = AuditReport::query()->lockForUpdate()->findOrFail($reportId);
                $this->author($record, $assignment);
                $this->view($record);

                return $this->journal->execute('party:'.$assignment['party_id'], $userId, $command, $requestId, 'audit.report', $reportId,
                    $this->decisionInput($contextRevision, $expectedRevision, $reasonCode, $reason),
                    function (): void {}, function () use ($record, $assignment, $userId, $expectedRevision, $reject, $reasonCode, $reason, $command): OperationResult {
                        if ($record->revision !== $expectedRevision) {
                            throw new CommandRejection('VERSION_CONFLICT', revision: $record->revision);
                        }
                        try {
                            $decision = AuditReportDecision::reason($record->kind, $record->status, $reject, $reasonCode, $reason);
                        } catch (CommandRejection $failure) {
                            throw new CommandRejection($failure->reason, $failure->status, $record->revision, $failure->fieldErrors);
                        }
                        $draft = $record->draft;
                        $draft['decision'] = [...$decision, 'recorded_at' => now('UTC')->format('Y-m-d\TH:i:s\Z')];
                        $record->forceFill(['revision' => $record->revision + 1, 'status' => $reject ? 'rejected' : 'changes_requested', 'draft' => $draft])->save();
                        $this->append($record, $assignment['party_id'], $userId, $command);

                        return $this->receipt($reject ? 'AUDIT_REJECTED' : 'AUDIT_CHANGES_REQUESTED', $this->view($record));
                    });
            });
    }

    /** @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function inputFingerprint(array $input): array
    {
        try {
            $this->json->encode($input);

            return $input;
        } catch (CommandRejection) {
            return ['invalid_input_sha256' => hash('sha256', serialize($input))];
        }
    }

    /**
     * Preserve existing receipt fingerprints; previously unhashable input still receives recorded validation.
     *
     * @return array<string, mixed>
     */
    private function decisionInput(int $contextRevision, int $expectedRevision, mixed $reasonCode, mixed $reason): array
    {
        $input = ['identity_context_revision' => $contextRevision, 'expected_revision' => $expectedRevision, 'reason_code' => $reasonCode, 'reason' => $reason];
        try {
            $this->json->encode($input);

            return $input;
        } catch (CommandRejection) {
            return ['identity_context_revision' => $contextRevision, 'expected_revision' => $expectedRevision,
                'invalid_reason_input_sha256' => hash('sha256', serialize([$reasonCode, $reason]))];
        }
    }

    /** @return array<string, mixed> */
    public function amend(int $userId, int $contextRevision, string $reportId, int $expectedRevision, string $requestId): array
    {
        $record = $this->owned($reportId, $this->party($userId));

        return $this->assignments->handle($userId, $contextRevision, $record->assignment_id,
            function (array $assignment) use ($userId, $contextRevision, $reportId, $expectedRevision, $requestId): array {
                $record = AuditReport::query()->lockForUpdate()->findOrFail($reportId);
                $this->author($record, $assignment);
                $parent = $this->view($record);

                return $this->journal->execute('party:'.$assignment['party_id'], $userId, 'audit.amend', $requestId, 'audit.report', $reportId,
                    ['identity_context_revision' => $contextRevision, 'expected_revision' => $expectedRevision], function (): void {},
                    function () use ($record, $parent, $assignment, $userId, $expectedRevision): OperationResult {
                        if ($record->revision !== $expectedRevision) {
                            throw new CommandRejection('VERSION_CONFLICT', revision: $record->revision);
                        }
                        if (! AuditReportDecision::amendable($record->status)) {
                            throw new CommandRejection('AUDIT_REPORT_NOT_AMENDABLE', revision: $record->revision);
                        }
                        $this->publications->requireAmendable($record->id);
                        $existing = AuditReport::query()->where('amends_id', $record->id)->first();
                        if ($existing !== null) {
                            return $this->receipt('AUDIT_AMENDMENT_CREATED', $this->view($existing));
                        }
                        $binding = [...$record->binding, 'engagement' => $assignment['engagement'],
                            'amends' => ['id' => $record->id, 'revision' => $record->revision, 'version' => $parent['version']]];
                        $child = new AuditReport;
                        $child->forceFill([...$record->only(['assignment_id', 'assignment_revision', 'author_party_id', 'business_id', 'application_id',
                            'application_revision', 'application_version_id', 'submission_id', 'quote_id', 'kind']),
                            'engagement_acceptance_id' => $assignment['engagement']['id'], 'amends_id' => $record->id,
                            'revision' => 1, 'status' => 'draft', 'step' => $record->kind === 'flash' ? 'review' : 'statements',
                            'binding' => $binding, 'binding_sha256' => $this->hash($binding), 'draft' => ['note' => '', 'completed_steps' => [], 'fields' => []]])->save();
                        $this->append($child, $assignment['party_id'], $userId, 'audit.amend');

                        return $this->receipt('AUDIT_AMENDMENT_CREATED', $this->view($child));
                    });
            });
    }

    /** @return Report */
    public function get(int $userId, int $contextRevision, string $reportId): array
    {
        $partyId = $this->party($userId);
        $record = $this->owned($reportId, $partyId);

        return $this->assignments->handle($userId, $contextRevision, $record->assignment_id, function (array $assignment) use ($reportId): array {
            $record = AuditReport::query()->lockForUpdate()->findOrFail($reportId);
            $this->author($record, $assignment);

            return $this->view($record);
        });
    }

    /** @return Procedure */
    public function procedure(int $userId, int $contextRevision, string $reportId): array
    {
        $record = $this->owned($reportId, $this->party($userId));

        return $this->assignments->handle($userId, $contextRevision, $record->assignment_id,
            function (array $assignment) use ($userId, $contextRevision, $reportId): array {
                $record = AuditReport::query()->sharedLock()->findOrFail($reportId);
                $this->author($record, $assignment);

                $sealed = $record->status === 'sealed' ? $this->publications->forAuditor($record->id) : null;

                return ['report' => $this->view($record), 'sources' => $sealed['sources'] ?? $this->procedureSources($record, $userId, $contextRevision, $assignment),
                    'mfa_confirmed' => $this->identities->forUser($userId)['mfa_confirmed'], 'sealed' => $sealed,
                    'signing_available' => $record->status === 'draft' && $this->cryptography->available()];
            });
    }

    /**
     * @param  AcceptedAssignment  $assignment
     * @return Projection
     */
    private function procedureSources(AuditReport $record, int $userId, int $contextRevision, array $assignment): array
    {
        $sources = $this->sources->handle($userId, $contextRevision, $assignment, $record->kind, $record->binding['period'] ?? null);
        $pins = [];
        foreach ($record->draft['documents'] ?? [] as $document) {
            $pins['ledger:'.$document['id']] = ['id' => $document['id'], 'revision' => $document['revision'], 'sha256' => $document['sha256']];
        }
        ksort($pins);

        return [...$sources, 'ledger_documents' => $this->ledgers->documents($record->id, $record->draft['documents'] ?? []), 'ledger_sources' => $pins];
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
        if (! in_array($command, ['audit.start', 'audit.save_step', 'audit.request_changes', 'audit.reject', 'audit.amend', 'audit.seal'], true)) {
            throw new CommandRejection('OPERATION_NOT_FOUND', 404);
        }

        $partyId = $this->party($userId);

        return $this->journal->find('party:'.$partyId, $command, $requestId,
            function (string $type, string $id) use ($userId, $contextRevision, $partyId, $command): void {
                if ($command !== 'audit.start') {
                    if ($type !== 'audit.report') {
                        throw new CommandRejection('OPERATION_NOT_FOUND', 404);
                    }
                    $this->get($userId, $contextRevision, $id);

                    return;
                }
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

    private function owned(string $reportId, string $partyId): AuditReport
    {
        return AuditReport::query()->whereKey($reportId)->where('author_party_id', $partyId)
            ->whereExists(fn (Query $query): Query => $query->selectRaw('1')->from('audit_assignments')
                ->whereColumn('audit_assignments.id', 'audit_reports.assignment_id')->where('party_id', $partyId))->first()
            ?? throw new CommandRejection('AUDIT_REPORT_NOT_FOUND', 404);
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
            'period' => $record->binding['period'] ?? null,
            'amends_id' => $record->amends_id, 'amendment_id' => AuditReport::query()->where('amends_id', $record->id)->value('id'),
            'binding_sha256' => $record->binding_sha256, 'draft' => $record->draft,
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
