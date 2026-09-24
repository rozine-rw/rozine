<?php

declare(strict_types=1);

namespace App\Infrastructure\Auditor;

use App\Application\Auditor\Contracts\AuditorProfileStore;
use App\Application\Identity\AuthorizeActiveRole;
use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Identity\Contracts\IdentityRepository;
use App\Application\Identity\WithVerifiedParties;
use App\Application\Operations\Contracts\OperationJournal;
use App\Domain\Auditor\AccreditationCertificate;
use App\Domain\Auditor\AccreditationProfile;
use App\Domain\Auditor\AccreditationView;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Models\AuditorCertificate;
use App\Models\AuditorProfile;
use App\Models\AuditorProfileVersion;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @phpstan-import-type State from AccreditationProfile
 * @phpstan-import-type Profile from AuditorProfileStore
 * @phpstan-import-type Download from AuditorProfileStore
 */
final class EloquentAuditorProfileStore implements AuditorProfileStore
{
    public function __construct(
        private AuthorizeActiveRole $roles,
        private AuthorizeStaffPermission $staff,
        private IdentityRepository $identities,
        private WithVerifiedParties $verifiedParties,
        private OperationJournal $journal,
        private AccreditationProfile $profiles,
        private AccreditationCertificate $certificates,
        private AccreditationView $views,
    ) {}

    /** @return array<string, mixed> */
    public function submit(int $userId, int $contextRevision, int $expectedRevision, string $licence, string $expiresOn, string $filename, string $content, string $requestId, bool $renew = false): array
    {
        $certificateId = strtolower((string) Str::ulid());
        $source = [];

        return $this->executeOwn($userId, $contextRevision, $expectedRevision, $renew ? 'accreditation.renew' : 'accreditation.submit', $requestId,
            ['licence' => $licence, 'expires_on' => $expiresOn, 'filename_sha256' => hash('sha256', $filename), 'sha256' => hash('sha256', $content)],
            function (array $state) use ($certificateId, $licence, $expiresOn, $filename, $content, &$source): array {
                $source = $this->certificates->describe($filename, $content);

                return $this->profiles->submit($state, $certificateId, $licence, $expiresOn, now()->toDateTimeImmutable());
            },
            function (AuditorProfile $record) use ($certificateId, $content, $userId, &$source): void {
                (new AuditorCertificate)->forceFill([...$source, 'id' => $certificateId, 'auditor_profile_id' => $record->id,
                    'content' => $content, 'actor_user_id' => $userId])->save();
            });
    }

    /** @return array<string, mixed> */
    public function withdraw(int $userId, int $contextRevision, int $expectedRevision, string $submissionId, string $requestId): array
    {
        return $this->executeOwn($userId, $contextRevision, $expectedRevision, 'accreditation.withdraw', $requestId,
            ['submission_id' => $submissionId], fn (array $state): array => $this->profiles->withdraw($state, $submissionId));
    }

    /** @return array<string, mixed> */
    public function availability(int $userId, int $contextRevision, int $expectedRevision, bool $accepting, string $requestId): array
    {
        return $this->executeOwn($userId, $contextRevision, $expectedRevision, 'availability.update', $requestId,
            ['accepting' => $accepting], fn (array $state): array => $this->profiles->availability($state, $accepting, now()->toDateTimeImmutable()));
    }

    /** @return array<string, mixed> */
    public function review(int $actorId, string $partyId, int $expectedRevision, string $decision, ?string $submissionId, string $checkedAt, string $reference, string $reason, string $requestId): array
    {
        return $this->locked($partyId, function (?AuditorProfile $profile) use ($actorId, $partyId, $expectedRevision, $decision, $submissionId, $checkedAt, $reference, $reason, $requestId): array {
            return $this->journal->execute('staff:'.$actorId, $actorId, 'accreditation.review', $requestId, 'auditor.party', $partyId,
                ['expected_revision' => $expectedRevision, 'decision' => $decision, 'submission_id' => $submissionId,
                    'checked_at' => $checkedAt, 'reference' => $reference, 'reason' => $reason],
                function () use ($actorId, $partyId, $decision): void {
                    $this->staff->handle($actorId, 'audit.partners.verify', function () use ($partyId, $decision): void {
                        if (in_array($decision, ['approve', 'recheck'], true)) {
                            $this->verifiedParties->handle('person', $partyId, [$partyId], fn (): bool => true);
                        }
                    });
                }, function () use ($profile, $partyId, $actorId, $expectedRevision, $decision, $submissionId, $checkedAt, $reference, $reason): OperationResult {
                    $this->revision($profile, $expectedRevision);
                    $state = $this->profiles->review($profile->state ?? $this->profiles->empty(), $decision, $submissionId, $checkedAt, $reference, $reason, now()->toDateTimeImmutable());
                    $record = $this->persist($profile, $partyId, $state, $expectedRevision);
                    $this->history($record, $actorId, 'accreditation.review', $reason);

                    return new OperationResult('ACCREDITATION_REVIEWED', ['profile_id' => $record->id], $record->revision);
                });
        });
    }

    /** @return array<string, mixed> */
    public function accreditation(int $userId, int $contextRevision): array
    {
        $partyId = $this->party($userId);

        return $this->locked($partyId, fn (?AuditorProfile $profile): array => $this->roles->handle($userId, 'auditor', $partyId, $contextRevision,
            function () use ($profile, $contextRevision): array {
                $state = $profile->state ?? $this->profiles->empty();
                $hash = $state['submission']['status'] === 'pending'
                    ? AuditorCertificate::query()->where('auditor_profile_id', $profile?->id)->whereKey($state['submission']['id'])->value('sha256')
                    : null;

                return ['contract_version' => 'auditor-filing-v1', 'identity_context_revision' => $contextRevision,
                    'server_time' => now()->toIso8601String(),
                    ...$this->views->present($state, $profile->revision ?? 0, is_string($hash) ? $hash : null, now()->toDateTimeImmutable())];
            }));
    }

    /** @return Profile */
    public function get(int $userId, int $contextRevision): array
    {
        $partyId = $this->party($userId);

        return $this->locked($partyId, fn (?AuditorProfile $profile): array => $this->roles->handle($userId, 'auditor', $partyId, $contextRevision,
            fn (): array => $this->snapshot($profile, $partyId)));
    }

    /** @return array<string, mixed> */
    public function findOperation(int $userId, int $contextRevision, string $command, string $requestId): array
    {
        if (! in_array($command, ['accreditation.submit', 'accreditation.renew', 'accreditation.withdraw', 'availability.update'], true)) {
            throw new CommandRejection('OPERATION_NOT_FOUND', 404);
        }
        $partyId = $this->party($userId);

        return $this->locked($partyId, fn (): array => $this->journal->find('party:'.$partyId, $command, $requestId,
            function (string $type, string $id) use ($userId, $contextRevision, $partyId): void {
                if ($type !== 'auditor.party' || $id !== $partyId) {
                    throw new CommandRejection('OPERATION_NOT_FOUND', 404);
                }
                $this->roles->handle($userId, 'auditor', $partyId, $contextRevision, fn (): bool => true);
            }));
    }

    /** @return Download */
    public function readCertificate(int $userId, ?int $contextRevision, string $partyId, string $certificateId, bool $staff): array
    {
        return $this->locked($partyId, function (?AuditorProfile $profile) use ($userId, $contextRevision, $partyId, $certificateId, $staff): array {
            $read = function () use ($profile, $certificateId): array {
                $certificate = $profile === null ? null : AuditorCertificate::query()->where('auditor_profile_id', $profile->id)->whereKey($certificateId)->first();
                if ($certificate === null) {
                    throw new CommandRejection('ACCREDITATION_CERTIFICATE_NOT_FOUND', 404);
                }
                $content = $certificate->content;
                if (strlen($content) !== $certificate->size_bytes || ! hash_equals($certificate->sha256, hash('sha256', $content))) {
                    throw new CommandRejection('ACCREDITATION_CERTIFICATE_INTEGRITY_FAILED');
                }

                return ['filename' => $certificate->filename, 'media_type' => $certificate->media_type, 'content' => $content, 'sha256' => $certificate->sha256];
            };
            if ($staff) {
                return $this->staff->handle($userId, 'audit.partners.verify', $read);
            }
            if ($contextRevision === null) {
                throw new IdentityViolation('ACTIVE_ROLE_REVISION_CONFLICT', 409);
            }

            return $this->roles->handle($userId, 'auditor', $partyId, $contextRevision, $read);
        });
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  Closure(State): State  $change
     * @param  Closure(AuditorProfile): void|null  $afterSave
     * @return array<string, mixed>
     */
    private function executeOwn(int $userId, int $contextRevision, int $expectedRevision, string $command, string $requestId, array $input, Closure $change, ?Closure $afterSave = null): array
    {
        $partyId = $this->party($userId);

        return $this->locked($partyId, fn (?AuditorProfile $profile): array => $this->journal->execute('party:'.$partyId, $userId, $command, $requestId, 'auditor.party', $partyId,
            ['identity_context_revision' => $contextRevision, 'expected_revision' => $expectedRevision, ...$input],
            function () use ($userId, $contextRevision, $partyId): void {
                $this->roles->handle($userId, 'auditor', $partyId, $contextRevision, fn (): bool => true);
            }, function () use ($profile, $partyId, $userId, $expectedRevision, $command, $change, $afterSave): OperationResult {
                $this->revision($profile, $expectedRevision);
                $state = $change($profile->state ?? $this->profiles->empty());
                $record = $this->persist($profile, $partyId, $state, $expectedRevision);
                if ($afterSave !== null) {
                    $afterSave($record);
                }
                $this->history($record, $userId, $command);
                $code = match ($command) {
                    'accreditation.submit', 'accreditation.renew' => 'ACCREDITATION_SUBMITTED',
                    'accreditation.withdraw' => 'ACCREDITATION_WITHDRAWN',
                    default => 'AVAILABILITY_UPDATED',
                };

                return new OperationResult($code, ['profile_id' => $record->id,
                    'submission_id' => $state['submission']['status'] === 'pending' ? $state['submission']['id'] : null], $record->revision);
            }));
    }

    /**
     * Acquires the aggregate before identity locks for both staff and participant commands.
     *
     * @template TResult
     *
     * @param  Closure(AuditorProfile|null): TResult  $operation
     * @return TResult
     */
    private function locked(string $partyId, Closure $operation): mixed
    {
        return DB::transaction(function () use ($partyId, $operation): mixed {
            DB::select('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))', ['auditor-party:'.$partyId]);

            return $operation(AuditorProfile::query()->where('party_id', $partyId)->lockForUpdate()->first());
        }, 3);
    }

    private function party(int $userId): string
    {
        return $this->identities->forUser($userId)['party']['id'] ?? throw new IdentityViolation('IDENTITY_NOT_LINKED');
    }

    private function revision(?AuditorProfile $profile, int $expected): void
    {
        if (($profile->revision ?? 0) !== $expected) {
            throw new CommandRejection('VERSION_CONFLICT', 409, $profile->revision ?? 0);
        }
    }

    /** @param State $state */
    private function persist(?AuditorProfile $profile, string $partyId, array $state, int $previousRevision): AuditorProfile
    {
        $record = $profile ?? new AuditorProfile;
        $record->forceFill(['party_id' => $partyId, 'revision' => $previousRevision + 1, 'state' => $state])->save();

        return $record;
    }

    private function history(AuditorProfile $profile, int $userId, string $command, ?string $reason = null): void
    {
        (new AuditorProfileVersion)->forceFill(['auditor_profile_id' => $profile->id, 'revision' => $profile->revision,
            'snapshot' => $this->snapshot($profile, $profile->party_id), 'actor_user_id' => $userId, 'command' => $command,
            'reason' => $reason, 'policy_version' => 'engineering-2026-09-23.4'])->save();
    }

    /** @return Profile */
    private function snapshot(?AuditorProfile $profile, string $partyId): array
    {
        return ['id' => $profile?->id, 'party_id' => $partyId, 'revision' => $profile->revision ?? 0,
            'state' => $profile->state ?? $this->profiles->empty()];
    }
}
