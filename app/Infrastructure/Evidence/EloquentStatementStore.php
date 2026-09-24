<?php

declare(strict_types=1);

namespace App\Infrastructure\Evidence;

use App\Application\Business\WithBusinessAuthority;
use App\Application\Evidence\Contracts\StatementStore;
use App\Application\Evidence\Contracts\StatementTextExtractor;
use App\Application\Identity\Contracts\IdentityRepository;
use App\Application\Operations\Contracts\OperationJournal;
use App\Domain\Evidence\StatementSource;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Models\StatementEvidence;
use App\Models\StatementExtraction;
use App\Models\StatementOriginal;
use RuntimeException;

/**
 * @phpstan-import-type Manifest from StatementStore
 * @phpstan-import-type Original from StatementStore
 */
final class EloquentStatementStore implements StatementStore
{
    public function __construct(
        private WithBusinessAuthority $authority,
        private IdentityRepository $identities,
        private OperationJournal $journal,
        private StatementSource $sources,
        private StatementTextExtractor $extractor,
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
                            $extraction = $this->extractor->extract($content, $source['media_type']);
                            $original = new StatementOriginal;
                            $original->forceFill([...$source, 'statement_evidence_id' => $evidence->id, 'evidence_revision' => $revision + 1,
                                'content' => $content, 'actor_user_id' => $userId, 'actor_party_id' => $partyId])->save();
                            (new StatementExtraction)->forceFill([...$extraction, 'statement_original_id' => $original->id, 'revision' => 1])->save();
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
        return $this->authority->handle($userId, $contextRevision, $businessId, 'business.view', null,
            function () use ($businessId, $documentId): array {
                $evidence = StatementEvidence::query()->where('business_id', $businessId)->first();
                $original = StatementOriginal::query()->where('statement_evidence_id', $evidence?->id)->whereKey($documentId)->first()
                    ?? throw new CommandRejection('STATEMENT_NOT_FOUND', 404);
                $content = $original->content;
                if (! hash_equals($original->sha256, hash('sha256', $content)) || strlen($content) !== $original->size_bytes) {
                    throw new RuntimeException('Statement original integrity check failed.');
                }

                return ['filename' => $original->filename, 'media_type' => $original->media_type, 'sha256' => $original->sha256, 'content' => $content];
            });
    }

    /** @return array<string, mixed> */
    public function findOperation(int $userId, int $contextRevision, string $requestId): array
    {
        $partyId = $this->identities->forUser($userId)['party']['id'] ?? throw new IdentityViolation('IDENTITY_NOT_LINKED');

        return $this->journal->find('party:'.$partyId, 'statement.ingest', $requestId,
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

    /** @return Manifest */
    private function manifest(?StatementEvidence $evidence): array
    {
        if ($evidence === null) {
            return ['revision' => 0, 'documents' => []];
        }
        $originals = StatementOriginal::query()->where('statement_evidence_id', $evidence->id)->orderBy('evidence_revision')
            ->get(['id', 'filename', 'sha256', 'media_type', 'size_bytes', 'created_at']);
        $extractions = StatementExtraction::query()->whereIn('statement_original_id', $originals->modelKeys())
            ->orderByDesc('revision')->get(['id', 'statement_original_id', 'revision', 'parser_version', 'status', 'reason_codes', 'record_count'])
            ->unique('statement_original_id')->keyBy('statement_original_id');
        $documents = [];
        foreach ($originals as $original) {
            $extraction = $extractions->get($original->id) ?? throw new RuntimeException('Statement extraction is missing.');
            $documents[] = ['id' => $original->id, 'filename' => $original->filename, 'sha256' => $original->sha256,
                'media_type' => $original->media_type, 'size_bytes' => $original->size_bytes, 'received_at' => $original->created_at->toIso8601String(),
                'extraction' => ['id' => $extraction->id, 'revision' => $extraction->revision, 'parser_version' => $extraction->parser_version,
                    'status' => $extraction->status, 'reason_codes' => $extraction->reason_codes, 'record_count' => $extraction->record_count]];
        }

        return ['revision' => $evidence->revision, 'documents' => $documents];
    }
}
