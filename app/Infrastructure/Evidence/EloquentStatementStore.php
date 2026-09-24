<?php

declare(strict_types=1);

namespace App\Infrastructure\Evidence;

use App\Application\Auditor\WithAcceptedAuditAssignment;
use App\Application\Business\WithBusinessAuthority;
use App\Application\Evidence\Contracts\StatementStore;
use App\Application\Identity\Contracts\IdentityRepository;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Operations\Contracts\OperationJournal;
use App\Domain\Evidence\StatementReconciliation;
use App\Domain\Evidence\StatementSource;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Models\StatementEvidence;
use App\Models\StatementExtraction;
use App\Models\StatementOriginal;
use App\Models\StatementTranscription;
use RuntimeException;

/**
 * @phpstan-import-type Manifest from StatementStore
 * @phpstan-import-type Original from StatementStore
 * @phpstan-import-type Transcription from StatementStore
 * @phpstan-import-type AuditFile from StatementStore
 * @phpstan-import-type Rail from StatementReconciliation
 * @phpstan-import-type Statement from StatementReconciliation
 */
final class EloquentStatementStore implements StatementStore
{
    public function __construct(
        private WithBusinessAuthority $authority,
        private WithAcceptedAuditAssignment $assignments,
        private IdentityRepository $identities,
        private OperationJournal $journal,
        private StatementSource $sources,
        private StatementReconciliation $reconciler,
        private CanonicalJson $json,
    ) {}

    /** @return array<string, mixed> */
    public function ingest(int $userId, int $contextRevision, string $businessId, int $expectedRevision, string $filename, string $content, string $requestId): array
    {
        return $this->authority->handle($userId, $contextRevision, $businessId, 'application.save', null,
            function (array $business, array $identity) use ($userId, $contextRevision, $businessId, $expectedRevision, $filename, $content, $requestId): array {
                $partyId = $identity['party']['id'] ?? throw new IdentityViolation('IDENTITY_NOT_LINKED');
                $evidence = StatementEvidence::query()->where('business_id', $businessId)->lockForUpdate()->first();

                return $this->journal->execute('party:'.$partyId, $userId, 'statement.ingest', $requestId, 'business', $businessId,
                    ['identity_context_revision' => $contextRevision, 'expected_revision' => $expectedRevision,
                        'filename' => $filename, 'content_sha256' => hash('sha256', $content)],
                    function () use ($userId, $contextRevision, $businessId): void {
                        $this->authority->handle($userId, $contextRevision, $businessId, 'application.save', null, fn (): bool => true);
                    },
                    function () use ($businessId, $expectedRevision, $filename, $content, $evidence, $userId, $partyId): OperationResult {
                        $revision = $evidence->revision ?? 0;
                        if ($revision !== $expectedRevision) {
                            throw new CommandRejection('VERSION_CONFLICT', 409, $revision);
                        }
                        $source = $this->sources->describe($filename, $content);
                        if ($evidence === null) {
                            $evidence = new StatementEvidence;
                            $evidence->forceFill(['business_id' => $businessId, 'revision' => 0])->save();
                        }
                        $original = StatementOriginal::query()->where('statement_evidence_id', $evidence->id)->where('sha256', $source['sha256'])->first(['id']);
                        if ($original === null) {
                            $original = new StatementOriginal;
                            $original->forceFill([...$source, 'statement_evidence_id' => $evidence->id, 'evidence_revision' => $revision + 1,
                                'content' => $content, 'actor_user_id' => $userId, 'actor_party_id' => $partyId])->save();
                            (new StatementExtraction)->forceFill(['statement_original_id' => $original->id, 'revision' => 1,
                                'parser_version' => 'pending-1', 'status' => 'pending', 'reason_codes' => [], 'text' => null, 'record_count' => null])->save();
                            $evidence->forceFill(['revision' => $revision + 1])->save();
                        }

                        return new OperationResult('INGESTED_NOT_AUDIT_APPROVED',
                            ['document_id' => $original->id, 'evidence' => $this->manifest($evidence)], $evidence->revision);
                    });
            });
    }

    /** @return Manifest */
    public function get(int $userId, int $contextRevision, string $businessId): array
    {
        return $this->authority->handle($userId, $contextRevision, $businessId, 'business.view', null,
            fn (): array => $this->manifest(StatementEvidence::query()->where('business_id', $businessId)->first()));
    }

    /** @return Original */
    public function read(int $userId, int $contextRevision, string $businessId, string $documentId): array
    {
        return $this->authority->handle($userId, $contextRevision, $businessId, 'application.save', null,
            fn (): array => $this->original($businessId, $documentId));
    }

    /** @return array<string, mixed> */
    public function findOperation(int $userId, int $contextRevision, string $requestId, string $command = 'ingest'): array
    {
        if (! in_array($command, ['ingest', 'reconcile'], true)) {
            throw new CommandRejection('OPERATION_NOT_FOUND', 404);
        }
        $partyId = $this->identities->forUser($userId)['party']['id'] ?? throw new IdentityViolation('IDENTITY_NOT_LINKED');

        return $this->journal->find('party:'.$partyId, 'statement.'.$command, $requestId,
            function (string $type, string $id) use ($userId, $contextRevision, $partyId): void {
                if ($type !== 'business') {
                    throw new CommandRejection('OPERATION_NOT_FOUND', 404);
                }
                $this->authority->handle($userId, $contextRevision, $id, 'application.save', null,
                    function (array $business, array $identity) use ($partyId): void {
                        if (($identity['party']['id'] ?? null) !== $partyId) {
                            throw new CommandRejection('OPERATION_NOT_FOUND', 404);
                        }
                    });
            });
    }

    /**
     * @param  list<Rail>  $rails
     * @param  list<string>  $months
     * @param  list<Statement>  $statements
     * @return array<string, mixed>
     */
    public function recordTranscription(int $userId, int $contextRevision, string $businessId, int $expectedRevision, array $rails, array $months, array $statements, string $requestId): array
    {
        return $this->authority->handle($userId, $contextRevision, $businessId, 'application.save', null,
            function (array $business, array $identity) use ($userId, $contextRevision, $businessId, $expectedRevision, $rails, $months, $statements, $requestId): array {
                $partyId = $identity['party']['id'] ?? throw new IdentityViolation('IDENTITY_NOT_LINKED');
                $evidence = StatementEvidence::query()->where('business_id', $businessId)->lockForUpdate()->first();

                return $this->journal->execute('party:'.$partyId, $userId, 'statement.reconcile', $requestId, 'business', $businessId,
                    ['identity_context_revision' => $contextRevision, 'expected_revision' => $expectedRevision,
                        'rails' => $rails, 'months' => $months, 'statements' => $statements],
                    function () use ($userId, $contextRevision, $businessId): void {
                        $this->authority->handle($userId, $contextRevision, $businessId, 'application.save', null, fn (): bool => true);
                    },
                    function () use ($userId, $partyId, $businessId, $expectedRevision, $evidence, $rails, $months, $statements): OperationResult {
                        $revision = $evidence->revision ?? 0;
                        if ($revision !== $expectedRevision) {
                            throw new CommandRejection('VERSION_CONFLICT', 409, $revision);
                        }
                        if ($evidence === null) {
                            throw new CommandRejection('STATEMENT_ORIGINAL_REQUIRED', 422, $revision);
                        }
                        $originals = StatementOriginal::query()->where('statement_evidence_id', $evidence->id)->get(['id', 'sha256']);
                        $availableSources = array_values($originals->map(fn (StatementOriginal $original): string => $original->id)->all());
                        $observations = $this->reconciler->reconcile($rails, $months, $statements, $availableSources);
                        $sourceIds = array_unique(array_merge(...array_column($observations, 'source_ids')));
                        $hashes = [];
                        foreach ($originals as $original) {
                            if (in_array($original->id, $sourceIds, true)) {
                                $hashes[$original->id] = $original->sha256;
                            }
                        }
                        $payload = ['business_id' => $businessId, 'source_revision' => $revision, 'classification_version' => StatementReconciliation::VERSION,
                            'rails' => $rails, 'months' => $months, 'statements' => $statements, 'source_hashes' => $hashes, 'observations' => $observations];
                        $previous = StatementTranscription::query()->where('statement_evidence_id', $evidence->id)->orderByDesc('evidence_revision')->first(['id']);
                        $record = new StatementTranscription;
                        $record->forceFill(['statement_evidence_id' => $evidence->id, 'evidence_revision' => $revision + 1, 'amends_id' => $previous?->id,
                            'classification_version' => StatementReconciliation::VERSION, 'payload' => $payload, 'sha256' => hash('sha256', $this->json->encode($payload)),
                            'actor_user_id' => $userId, 'actor_party_id' => $partyId])->save();
                        $evidence->forceFill(['revision' => $revision + 1])->save();

                        return new OperationResult('STATEMENT_RECONCILED_UNVERIFIED', ['transcription' => ['id' => $record->id,
                            'sha256' => $record->sha256, 'amends_id' => $record->amends_id, 'verified' => false]], $evidence->revision);
                    });
            });
    }

    /** @return Transcription|null */
    public function transcription(int $userId, int $contextRevision, string $businessId, ?string $transcriptionId): ?array
    {
        return $this->authority->handle($userId, $contextRevision, $businessId, 'application.save', null,
            fn (): ?array => $this->storedTranscription($businessId, $transcriptionId));
    }

    /** @return AuditFile */
    public function audit(int $userId, int $contextRevision, string $assignmentId): array
    {
        return $this->assignments->handle($userId, $contextRevision, $assignmentId, fn (array $assignment): array => [
            'assignment' => $assignment,
            'evidence' => $this->manifest(StatementEvidence::query()->where('business_id', $assignment['business_id'])->first()),
            'transcription' => $this->storedTranscription($assignment['business_id'], null),
        ]);
    }

    /** @return Original */
    public function auditRead(int $userId, int $contextRevision, string $assignmentId, string $documentId): array
    {
        return $this->assignments->handle($userId, $contextRevision, $assignmentId, function (array $assignment) use ($documentId): array {
            $original = $this->original($assignment['business_id'], $documentId);

            return [...$original, 'filename' => $this->displayName($documentId, $original['media_type'])];
        });
    }

    /** @return Transcription|null */
    public function auditTranscription(int $userId, int $contextRevision, string $assignmentId, ?string $transcriptionId): ?array
    {
        return $this->assignments->handle($userId, $contextRevision, $assignmentId,
            fn (array $assignment): ?array => $this->storedTranscription($assignment['business_id'], $transcriptionId));
    }

    /** @return Original */
    private function original(string $businessId, string $documentId): array
    {
        $evidence = StatementEvidence::query()->where('business_id', $businessId)->first();
        $original = StatementOriginal::query()->where('statement_evidence_id', $evidence?->id)->whereKey($documentId)->first()
            ?? throw new CommandRejection('STATEMENT_NOT_FOUND', 404);
        $content = $original->content;
        if (! hash_equals($original->sha256, hash('sha256', $content)) || strlen($content) !== $original->size_bytes) {
            throw new RuntimeException('Statement original integrity check failed.');
        }

        return ['filename' => $original->filename, 'media_type' => $original->media_type, 'sha256' => $original->sha256, 'content' => $content];
    }

    /** @return Transcription|null */
    private function storedTranscription(string $businessId, ?string $transcriptionId): ?array
    {
        $evidence = StatementEvidence::query()->where('business_id', $businessId)->first();
        $query = StatementTranscription::query()->where('statement_evidence_id', $evidence?->id)->orderByDesc('evidence_revision');
        if ($transcriptionId !== null) {
            $query->whereKey($transcriptionId);
        }
        $record = $query->first();
        if ($record === null) {
            if ($transcriptionId !== null) {
                throw new CommandRejection('STATEMENT_TRANSCRIPTION_NOT_FOUND', 404);
            }

            return null;
        }
        $payload = $record->payload;
        if (! hash_equals($record->sha256, hash('sha256', $this->json->encode($payload))) || $payload['business_id'] !== $businessId) {
            throw new RuntimeException('Statement transcription integrity check failed.');
        }

        return ['id' => $record->id, 'revision' => $record->evidence_revision, 'amends_id' => $record->amends_id,
            'sha256' => $record->sha256, 'current' => $record->evidence_revision === $evidence?->revision, 'verified' => false, 'payload' => $payload];
    }

    private function displayName(string $documentId, string $mediaType): string
    {
        return 'statement-'.$documentId.($mediaType === 'application/pdf' ? '.pdf' : '.csv');
    }

    /** @return Manifest */
    private function manifest(?StatementEvidence $evidence): array
    {
        if ($evidence === null) {
            return ['revision' => 0, 'documents' => []];
        }
        $originals = StatementOriginal::query()->where('statement_evidence_id', $evidence->id)->orderBy('evidence_revision')
            ->get(['id', 'sha256', 'media_type', 'size_bytes', 'created_at']);
        $extractions = StatementExtraction::query()->whereIn('statement_original_id', $originals->modelKeys())
            ->orderByDesc('revision')->get(['id', 'statement_original_id', 'revision', 'parser_version', 'status', 'reason_codes', 'record_count'])
            ->unique('statement_original_id')->keyBy('statement_original_id');
        $documents = [];
        foreach ($originals as $original) {
            $extraction = $extractions->get($original->id) ?? throw new RuntimeException('Statement extraction is missing.');
            $documents[] = ['id' => $original->id, 'filename' => $this->displayName($original->id, $original->media_type), 'sha256' => $original->sha256,
                'media_type' => $original->media_type, 'size_bytes' => $original->size_bytes, 'received_at' => $original->created_at->toIso8601String(),
                'extraction' => ['id' => $extraction->id, 'revision' => $extraction->revision, 'parser_version' => $extraction->parser_version,
                    'status' => $extraction->status, 'reason_codes' => $extraction->reason_codes, 'record_count' => $extraction->record_count]];
        }

        return ['revision' => $evidence->revision, 'documents' => $documents];
    }
}
