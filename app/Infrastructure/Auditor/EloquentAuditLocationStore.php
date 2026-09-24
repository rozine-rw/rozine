<?php

declare(strict_types=1);

namespace App\Infrastructure\Auditor;

use App\Application\Auditor\Contracts\AuditLocationStore;
use App\Application\Business\WithBusinessReview;
use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Identity\WithVerifiedParties;
use App\Application\Operations\Contracts\OperationJournal;
use App\Domain\Auditor\VerifiedAuditLocation;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Models\AuditLocation;
use App\Models\AuditLocationVersion;
use Closure;
use Illuminate\Support\Facades\DB;

/**
 * @phpstan-import-type State from VerifiedAuditLocation
 * @phpstan-import-type Location from AuditLocationStore
 */
final class EloquentAuditLocationStore implements AuditLocationStore
{
    public function __construct(
        private AuthorizeStaffPermission $staff,
        private WithVerifiedParties $parties,
        private WithBusinessReview $businesses,
        private OperationJournal $journal,
        private VerifiedAuditLocation $locations,
    ) {}

    /** @return array<string, mixed> */
    public function verify(int $actorId, string $kind, string $subjectId, int $expectedRevision, string $latitude, string $longitude, int $uncertainty, string $verifiedAt, string $reference, string $reason, string $requestId): array
    {
        return $this->execute($actorId, $kind, $subjectId, $expectedRevision, 'audit.location.verify', $requestId,
            ['latitude' => $latitude, 'longitude' => $longitude, 'uncertainty_m' => $uncertainty, 'verified_at' => $verifiedAt, 'reference' => $reference, 'reason' => $reason], $reason,
            fn (array $state): array => $this->locations->verify($state, $latitude, $longitude, $uncertainty, $verifiedAt, $reference, $reason, now()->toDateTimeImmutable()));
    }

    /** @return array<string, mixed> */
    public function moved(int $actorId, string $kind, string $subjectId, int $expectedRevision, string $movedAt, string $reason, string $requestId): array
    {
        return $this->execute($actorId, $kind, $subjectId, $expectedRevision, 'audit.location.moved', $requestId,
            ['moved_at' => $movedAt, 'reason' => $reason], $reason,
            fn (array $state): array => $this->locations->moved($state, $movedAt, $reason, now()->toDateTimeImmutable()));
    }

    /** @return Location */
    public function get(int $actorId, string $kind, string $subjectId): array
    {
        return $this->withAccess($actorId, $kind, $subjectId, false, fn (?AuditLocation $location): array => $this->snapshot($location, $kind, $subjectId));
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  Closure(State): State  $change
     * @return array<string, mixed>
     */
    private function execute(int $actorId, string $kind, string $subjectId, int $expectedRevision, string $command, string $requestId, array $input, string $reason, Closure $change): array
    {
        return $this->withAccess($actorId, $kind, $subjectId, $command === 'audit.location.verify',
            fn (?AuditLocation $location): array => $this->journal->execute('staff:'.$actorId, $actorId, $command, $requestId, 'audit.location.'.$kind, $subjectId,
                ['expected_revision' => $expectedRevision, ...$input],
                function () use ($actorId, $kind): void {
                    $this->staff->handle($actorId, $kind === 'office' ? 'audit.partners.verify' : 'businesses.verify', fn (): bool => true);
                }, function () use ($location, $kind, $subjectId, $actorId, $expectedRevision, $command, $reason, $change): OperationResult {
                    if (($location->revision ?? 0) !== $expectedRevision) {
                        throw new CommandRejection('VERSION_CONFLICT', 409, $location->revision ?? 0);
                    }
                    if ($location === null && $command === 'audit.location.moved') {
                        throw new CommandRejection('AUDIT_LOCATION_NOT_FOUND', 404);
                    }
                    $state = $change($location->state ?? $this->locations->empty());
                    $record = $location ?? new AuditLocation;
                    $record->forceFill([$kind === 'office' ? 'office_party_id' : 'business_id' => $subjectId,
                        'revision' => $expectedRevision + 1, 'state' => $state])->save();
                    (new AuditLocationVersion)->forceFill(['audit_location_id' => $record->id, 'revision' => $record->revision,
                        'snapshot' => $this->snapshot($record, $kind, $subjectId), 'actor_user_id' => $actorId, 'command' => $command,
                        'reason' => $reason, 'policy_version' => 'engineering-2026-09-23.4'])->save();

                    return new OperationResult($command === 'audit.location.verify' ? 'AUDIT_LOCATION_VERIFIED' : 'AUDIT_LOCATION_INVALIDATED',
                        ['location_id' => $record->id], $record->revision);
                }));
    }

    /**
     * Authority remains locked around every operation and replay. Premises take the Business lock
     * first; office writes lock only their own location before staff and verified identity records.
     *
     * @template TResult
     *
     * @param  Closure(AuditLocation|null): TResult  $operation
     * @return TResult
     */
    private function withAccess(int $actorId, string $kind, string $subjectId, bool $requireVerified, Closure $operation): mixed
    {
        if (! in_array($kind, ['office', 'premises'], true)) {
            throw new CommandRejection('AUDIT_LOCATION_KIND_INVALID', 422);
        }
        if ($kind === 'premises') {
            return $this->businesses->handle($actorId, $subjectId, $requireVerified,
                fn (): mixed => $this->locked($kind, $subjectId, $operation));
        }

        return $this->locked($kind, $subjectId, fn (?AuditLocation $location): mixed => $this->staff->handle($actorId, 'audit.partners.verify',
            fn (): mixed => $requireVerified
                ? $this->parties->handle('person', $subjectId, [$subjectId], fn (): mixed => $operation($location))
                : $operation($location)));
    }

    /**
     * @template TResult
     *
     * @param  Closure(AuditLocation|null): TResult  $operation
     * @return TResult
     */
    private function locked(string $kind, string $subjectId, Closure $operation): mixed
    {
        return DB::transaction(function () use ($kind, $subjectId, $operation): mixed {
            DB::select('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))', ['audit-location:'.$kind.':'.$subjectId]);
            $location = AuditLocation::query()->where($kind === 'office' ? 'office_party_id' : 'business_id', $subjectId)->lockForUpdate()->first();

            return $operation($location);
        }, 3);
    }

    /** @return Location */
    private function snapshot(?AuditLocation $location, string $kind, string $subjectId): array
    {
        return ['id' => $location?->id, 'kind' => $kind, 'subject_id' => $subjectId,
            'revision' => $location->revision ?? 0, 'state' => $location->state ?? $this->locations->empty()];
    }
}
