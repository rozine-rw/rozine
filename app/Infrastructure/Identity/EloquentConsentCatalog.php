<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity;

use App\Application\Environment\EnvironmentIsolation;
use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Identity\Contracts\ConsentCatalog;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Operations\Contracts\OperationJournal;
use App\Domain\Identity\ConsentDocuments;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Models\ConsentRelease;
use Closure;
use Illuminate\Support\Facades\DB;

/**
 * @phpstan-import-type DocumentInput from ConsentDocuments
 * @phpstan-import-type DisclosureInput from ConsentDocuments
 * @phpstan-import-type Document from ConsentDocuments
 * @phpstan-import-type Disclosure from ConsentDocuments
 * @phpstan-import-type Release from ConsentCatalog
 */
final class EloquentConsentCatalog implements ConsentCatalog
{
    public function __construct(
        private AuthorizeStaffPermission $staff,
        private OperationJournal $journal,
        private ConsentDocuments $documents,
        private EnvironmentIsolation $isolation,
        private CanonicalJson $json,
    ) {}

    /**
     * @param  list<DocumentInput>  $documents
     * @param  list<DisclosureInput>  $disclosures
     * @return array<string, mixed>
     */
    public function record(int $actorId, int $expectedRevision, string $status, array $documents, array $disclosures, bool $synthetic, string $approvalReference, string $reason, string $requestId): array
    {
        if ($expectedRevision < 0 || ! in_array($status, ['active', 'withdrawn'], true)
            || trim($approvalReference) === '' || mb_strlen($approvalReference) > 255 || trim($reason) === '' || mb_strlen($reason) > 2000
            || ($status === 'withdrawn' && ($documents !== [] || $disclosures !== []))) {
            throw new CommandRejection('CONSENT_RELEASE_INVALID', 422);
        }
        $documents = $status === 'active' ? $this->documents->documents($documents) : [];
        $disclosures = $status === 'active' ? $this->documents->disclosures($disclosures) : [];

        return DB::transaction(function () use ($actorId, $expectedRevision, $status, $documents, $disclosures, $synthetic, $approvalReference, $reason, $requestId): array {
            DB::select('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))', ['consent-catalog']);

            return $this->journal->execute('staff:'.$actorId, $actorId, 'consent.release.record', $requestId, 'consent.catalog', 'business-application',
                ['expected_revision' => $expectedRevision, 'status' => $status, 'documents' => $documents, 'disclosures' => $disclosures,
                    'synthetic' => $synthetic, 'approval_reference' => $approvalReference, 'reason' => $reason],
                function () use ($actorId, $synthetic): void {
                    $this->staff->handle($actorId, 'consent.documents.record', fn (): bool => true);
                    if ($synthetic && ! $this->isolation->canSeed()) {
                        throw new CommandRejection('SYNTHETIC_CONSENT_DENIED', 403);
                    }
                }, function () use ($actorId, $expectedRevision, $status, $documents, $disclosures, $synthetic, $approvalReference, $reason): OperationResult {
                    $revision = ConsentRelease::query()->max('revision') ?? 0;
                    if ($revision !== $expectedRevision) {
                        throw new CommandRejection('VERSION_CONFLICT', 409, $revision);
                    }
                    $this->assertVersionsUnchanged($documents, $disclosures, $synthetic);
                    $release = new ConsentRelease;
                    $release->forceFill(['revision' => $revision + 1, 'status' => $status, 'documents' => $documents, 'disclosures' => $disclosures,
                        'synthetic' => $synthetic, 'approval_reference' => $approvalReference, 'reason' => $reason, 'actor_user_id' => $actorId,
                        'policy_version' => 'engineering-2026-09-23.4'])->save();

                    return new OperationResult('CONSENT_RELEASE_RECORDED', ['release' => ['id' => $release->id,
                        'revision' => $release->revision, 'status' => $status, 'synthetic' => $synthetic]], $release->revision, ['consent.documents.record']);
                });
        }, 3);
    }

    /**
     * @template TResult
     *
     * @param  Closure(Release|null): TResult  $operation
     * @return TResult
     */
    public function withCurrent(Closure $operation): mixed
    {
        return DB::transaction(function () use ($operation): mixed {
            DB::select('SELECT pg_advisory_xact_lock_shared(hashtextextended(?, 0))', ['consent-catalog']);
            $release = ConsentRelease::query()->orderByDesc('revision')->first();
            if ($release === null || $release->status !== 'active' || ($release->synthetic && ! $this->isolation->canSeed())) {
                return $operation(null);
            }

            return $operation(['id' => $release->id, 'revision' => $release->revision, 'documents' => $release->documents,
                'disclosures' => $release->disclosures, 'synthetic' => $release->synthetic]);
        }, 3);
    }

    /**
     * @param  list<Document>  $documents
     * @param  list<Disclosure>  $disclosures
     */
    private function assertVersionsUnchanged(array $documents, array $disclosures, bool $synthetic): void
    {
        foreach ($documents as $document) {
            $this->assertVersion('documents', 'kind', $document['kind'], $document['version'], $document, $synthetic);
        }
        foreach ($disclosures as $disclosure) {
            $this->assertVersion('disclosures', 'key', $disclosure['key'], $disclosure['version'], $disclosure, $synthetic);
        }
    }

    /** @param array<string, mixed> $item */
    private function assertVersion(string $column, string $key, string $identity, string $version, array $item, bool $synthetic): void
    {
        $previous = ConsentRelease::query()->whereJsonContains($column, [[$key => $identity, 'version' => $version]])->first();
        if ($previous === null) {
            return;
        }
        $items = $column === 'documents' ? $previous->documents : $previous->disclosures;
        foreach ($items as $record) {
            if (($record[$key] ?? null) === $identity && $record['version'] === $version
                && ($this->json->encode($record) !== $this->json->encode($item) || $previous->synthetic !== $synthetic)) {
                throw new CommandRejection('DOCUMENT_VERSION_CONFLICT');
            }
        }
    }
}
