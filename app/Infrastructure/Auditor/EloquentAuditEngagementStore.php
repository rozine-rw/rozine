<?php

declare(strict_types=1);

namespace App\Infrastructure\Auditor;

use App\Application\Auditor\Contracts\AuditEngagementStore;
use App\Application\Environment\EnvironmentIsolation;
use App\Application\Identity\AuthorizeActiveRole;
use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Operations\Contracts\OperationJournal;
use App\Domain\Auditor\AuditEngagementDocuments;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Models\AuditEngagementAcceptance;
use App\Models\AuditEngagementRelease;
use Closure;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * @phpstan-import-type Release from AuditEngagementStore
 * @phpstan-import-type Acceptance from AuditEngagementStore
 * @phpstan-import-type Page from AuditEngagementStore
 * @phpstan-import-type Documents from AuditEngagementDocuments
 */
final class EloquentAuditEngagementStore implements AuditEngagementStore
{
    public function __construct(
        private AuthorizeActiveRole $roles,
        private AuthorizeStaffPermission $staff,
        private EnvironmentIsolation $isolation,
        private OperationJournal $journal,
        private CanonicalJson $json,
        private AuditEngagementDocuments $documents,
    ) {}

    /**
     * @param  array<string, mixed>  $documents
     * @return array<string, mixed>
     */
    public function record(int $actorId, int $expectedRevision, string $status, ?string $version, array $documents, bool $synthetic, string $approvalReference, string $reason, string $requestId): array
    {
        return $this->staff->handle($actorId, 'consent.documents.record', function () use ($actorId, $expectedRevision, $status, $version, $documents, $synthetic, $approvalReference, $reason, $requestId): array {
            if ($synthetic && ! $this->isolation->canSeed()) {
                throw new CommandRejection('SYNTHETIC_ENGAGEMENT_DENIED', 403);
            }
            DB::select('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))', ['audit-engagement-terms']);

            return $this->journal->execute('staff:'.$actorId, $actorId, 'audit.engagement.record', $requestId, 'audit.engagement.catalog', 'auditor-platform-terms',
                ['expected_revision' => $expectedRevision, 'status' => $status, 'version' => $version, 'documents' => $documents,
                    'synthetic' => $synthetic, 'approval_reference' => $approvalReference, 'reason' => $reason], function (): void {},
                function () use ($actorId, $expectedRevision, $status, $version, $documents, $synthetic, $approvalReference, $reason): OperationResult {
                    $revision = AuditEngagementRelease::query()->max('revision') ?? 0;
                    if ($revision !== $expectedRevision) {
                        throw new CommandRejection('VERSION_CONFLICT', revision: $revision);
                    }
                    if (! in_array($status, ['active', 'withdrawn'], true) || ($status === 'withdrawn' && ($version !== null || $documents !== []))) {
                        throw new CommandRejection('AUDIT_ENGAGEMENT_INPUT_INVALID', 422, $revision);
                    }
                    $approvalReference = $this->documents->text($approvalReference, 'approval_reference', 255);
                    $reason = $this->documents->text($reason, 'reason', 2000);
                    $normalized = $status === 'active' ? $this->documents->normalize($documents) : [];
                    $version = $status === 'active' ? $this->documents->version($version) : null;
                    if ($version !== null && AuditEngagementRelease::query()->where('version', $version)->exists()) {
                        throw new CommandRejection('AUDIT_ENGAGEMENT_VERSION_CONFLICT', revision: $revision);
                    }
                    $release = new AuditEngagementRelease;
                    $release->forceFill(['revision' => $revision + 1, 'status' => $status, 'version' => $version,
                        'procedure_version' => AuditEngagementDocuments::PROCEDURE, 'documents' => $normalized, 'synthetic' => $synthetic,
                        'approval_reference' => $approvalReference, 'reason' => $reason, 'actor_user_id' => $actorId]);
                    $release->forceFill(['sha256' => $this->releaseHash($release)])->save();

                    return new OperationResult('AUDIT_ENGAGEMENT_TERMS_RECORDED', ['release_id' => $release->id, 'sha256' => $release->sha256,
                        'status' => $status, 'synthetic' => $synthetic], $release->revision);
                });
        });
    }

    /** @return Page */
    public function get(int $userId, int $contextRevision): array
    {
        return $this->roles->handle($userId, 'auditor', null, $contextRevision, function (array $identity): array {
            $partyId = $identity['party']['id'] ?? throw new IdentityViolation('IDENTITY_NOT_LINKED');

            return $this->withRelease(function (?array $release) use ($partyId): array {
                $acceptance = $release === null ? null : AuditEngagementAcceptance::query()->where('party_id', $partyId)
                    ->where('audit_engagement_release_id', $release['id'])->first();

                return ['release' => $release, 'acceptance' => $acceptance === null ? null : $this->acceptance($acceptance)];
            });
        });
    }

    /** @return array<string, mixed> */
    public function accept(int $userId, int $contextRevision, string $releaseId, int $expectedRevision, string $sha256, bool $accepted, string $requestId): array
    {
        return $this->roles->handle($userId, 'auditor', null, $contextRevision, function (array $identity) use ($userId, $contextRevision, $releaseId, $expectedRevision, $sha256, $accepted, $requestId): array {
            $partyId = $identity['party']['id'] ?? throw new IdentityViolation('IDENTITY_NOT_LINKED');

            return $this->withRelease(function (?array $release) use ($partyId, $userId, $contextRevision, $releaseId, $expectedRevision, $sha256, $accepted, $requestId): array {
                return $this->journal->execute('party:'.$partyId, $userId, 'audit.engagement.accept', $requestId, 'audit.engagement.release', $releaseId,
                    ['identity_context_revision' => $contextRevision, 'expected_revision' => $expectedRevision, 'sha256' => $sha256, 'accepted' => $accepted], function (): void {},
                    function () use ($release, $partyId, $userId, $contextRevision, $releaseId, $expectedRevision, $sha256, $accepted): OperationResult {
                        if ($release === null) {
                            throw new CommandRejection('AUDIT_ENGAGEMENT_TERMS_REQUIRED');
                        }
                        if ($release['id'] !== $releaseId || $release['revision'] !== $expectedRevision || ! hash_equals($release['sha256'], $sha256)) {
                            throw new CommandRejection('AUDIT_ENGAGEMENT_VERSION_CONFLICT', revision: $release['revision']);
                        }
                        if (! $accepted) {
                            throw new CommandRejection('AUDIT_ENGAGEMENT_ACCEPTANCE_REQUIRED', 422, $release['revision'], ['accepted' => ['Read and explicitly accept both engagement documents.']]);
                        }
                        $existing = AuditEngagementAcceptance::query()->where('party_id', $partyId)->where('audit_engagement_release_id', $releaseId)->first();
                        if ($existing !== null) {
                            return new OperationResult('AUDIT_ENGAGEMENT_ACCEPTED', ['acceptance' => $this->acceptance($existing)], $release['revision']);
                        }
                        $payload = ['release_id' => $releaseId, 'release_revision' => $release['revision'], 'release_sha256' => $release['sha256'],
                            'party_id' => $partyId, 'actor_user_id' => $userId, 'identity_context_revision' => $contextRevision,
                            'accepted_at' => now('UTC')->format('Y-m-d\TH:i:s\Z'), 'accepted' => true, 'synthetic' => $release['synthetic']];
                        $acceptance = new AuditEngagementAcceptance;
                        $acceptance->forceFill(['audit_engagement_release_id' => $releaseId, 'release_revision' => $release['revision'], 'release_sha256' => $release['sha256'],
                            'party_id' => $partyId, 'actor_user_id' => $userId, 'payload' => $payload, 'sha256' => $this->hash($payload)])->save();

                        return new OperationResult('AUDIT_ENGAGEMENT_ACCEPTED', ['acceptance' => $this->acceptance($acceptance)], $release['revision']);
                    });
            });
        });
    }

    /** @return array<string, mixed> */
    public function findOperation(int $userId, int $contextRevision, string $requestId): array
    {
        return $this->roles->handle($userId, 'auditor', null, $contextRevision, function (array $identity) use ($requestId): array {
            $partyId = $identity['party']['id'] ?? throw new IdentityViolation('IDENTITY_NOT_LINKED');

            return $this->journal->find('party:'.$partyId, 'audit.engagement.accept', $requestId, function (string $type): void {
                if ($type !== 'audit.engagement.release') {
                    throw new CommandRejection('OPERATION_NOT_FOUND', 404);
                }
            });
        });
    }

    /**
     * @template TResult
     *
     * @param  list<string>  $partyIds
     * @param  Closure(array<string, Acceptance>): TResult  $operation
     * @return TResult
     */
    public function withCurrentAcceptances(array $partyIds, Closure $operation): mixed
    {
        return $this->withRelease(function (?array $release) use ($partyIds, $operation): mixed {
            $acceptances = [];
            if ($release !== null) {
                $records = AuditEngagementAcceptance::query()->where('audit_engagement_release_id', $release['id'])
                    ->whereIn('party_id', $partyIds)->orderBy('party_id')->get();
                foreach ($records as $record) {
                    $acceptances[$record->party_id] = $this->acceptance($record);
                }
            }

            return $operation($acceptances);
        });
    }

    /**
     * @template TResult
     *
     * @param  Closure(Release|null): TResult  $operation
     * @return TResult
     */
    private function withRelease(Closure $operation): mixed
    {
        return DB::transaction(function () use ($operation): mixed {
            DB::select('SELECT pg_advisory_xact_lock_shared(hashtextextended(?, 0))', ['audit-engagement-terms']);
            $record = AuditEngagementRelease::query()->orderByDesc('revision')->first();
            if ($record === null || $record->status !== 'active' || ($record->synthetic && ! $this->isolation->canSeed())) {
                return $operation(null);
            }
            if (! hash_equals($record->sha256, $this->releaseHash($record)) || $record->version === null
                || $record->procedure_version !== AuditEngagementDocuments::PROCEDURE
                || ! isset($record->documents['master_services'], $record->documents['agreed_procedures'])) {
                throw new RuntimeException('AUDIT_ENGAGEMENT_INTEGRITY_FAILED');
            }

            return $operation(['id' => $record->id, 'revision' => $record->revision, 'version' => $record->version,
                'procedure_version' => $record->procedure_version, 'documents' => $record->documents, 'sha256' => $record->sha256,
                'synthetic' => $record->synthetic]);
        }, 3);
    }

    /** @return Acceptance */
    private function acceptance(AuditEngagementAcceptance $record): array
    {
        $payload = $record->payload;
        if (! hash_equals($record->sha256, $this->hash($payload)) || $payload['party_id'] !== $record->party_id
            || $payload['actor_user_id'] !== $record->actor_user_id
            || $payload['release_id'] !== $record->audit_engagement_release_id || $payload['release_revision'] !== $record->release_revision
            || $payload['release_sha256'] !== $record->release_sha256 || ($payload['accepted'] ?? false) !== true) {
            throw new RuntimeException('AUDIT_ENGAGEMENT_ACCEPTANCE_INTEGRITY_FAILED');
        }

        return ['id' => $record->id, 'release_id' => $record->audit_engagement_release_id, 'release_revision' => $record->release_revision,
            'release_sha256' => $record->release_sha256, 'accepted_at' => $payload['accepted_at'], 'sha256' => $record->sha256];
    }

    private function releaseHash(AuditEngagementRelease $record): string
    {
        return $this->hash(['revision' => $record->revision, 'status' => $record->status, 'version' => $record->version,
            'procedure_version' => $record->procedure_version, 'documents' => $record->documents, 'synthetic' => $record->synthetic]);
    }

    /** @param array<string, mixed> $value */
    private function hash(array $value): string
    {
        return hash('sha256', $this->json->encode($value));
    }
}
