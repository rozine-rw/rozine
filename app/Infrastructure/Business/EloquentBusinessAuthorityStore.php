<?php

declare(strict_types=1);

namespace App\Infrastructure\Business;

use App\Application\Business\Contracts\BusinessAuthorityStore;
use App\Application\Identity\AuthorizeEntityRole;
use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Identity\Contracts\IdentityRepository;
use App\Application\Identity\WithVerifiedParties;
use App\Application\Operations\Contracts\OperationJournal;
use App\Domain\Business\MandateAuthority;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Models\BusinessMandate;
use App\Models\BusinessProfile;
use Closure;
use Illuminate\Support\Facades\DB;

/**
 * @phpstan-import-type Profile from MandateAuthority
 * @phpstan-import-type Terms from MandateAuthority
 * @phpstan-import-type Business from BusinessAuthorityStore
 * @phpstan-import-type AccessSnapshot from \App\Domain\Identity\ActiveRolePolicy
 */
final class EloquentBusinessAuthorityStore implements BusinessAuthorityStore
{
    public function __construct(
        private AuthorizeStaffPermission $staff,
        private AuthorizeEntityRole $roles,
        private IdentityRepository $identities,
        private WithVerifiedParties $verifiedParties,
        private MandateAuthority $authority,
        private OperationJournal $journal,
    ) {}

    /**
     * @param  Profile  $profile
     * @param  Terms  $terms
     * @return array<string, mixed>
     */
    public function configure(int $actorId, string $entityKind, string $entityPartyId, array $profile, array $terms, int $expectedRevision, string $evidenceReference, string $reason, string $requestId): array
    {
        $profile = $this->authority->profile($entityKind, $profile, now()->year);
        $terms = $this->authority->normalize($entityKind, $entityPartyId, $terms);
        if ($expectedRevision < 0 || trim($evidenceReference) === '' || mb_strlen($evidenceReference) > 255 || trim($reason) === '' || mb_strlen($reason) > 2000) {
            throw new CommandRejection('MANDATE_EVIDENCE_REQUIRED', 422);
        }

        return DB::transaction(function () use ($actorId, $entityKind, $entityPartyId, $profile, $terms, $expectedRevision, $evidenceReference, $reason, $requestId): array {
            DB::select('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))', ['business-entity:'.$entityPartyId]);
            $business = BusinessProfile::query()->where('entity_party_id', $entityPartyId)->lockForUpdate()->first();

            return $this->journal->execute('staff:'.$actorId, $actorId, 'business.authority.configure', $requestId, 'business.entity', $entityPartyId,
                ['entity_kind' => $entityKind, 'profile' => $profile, 'terms' => $terms, 'expected_revision' => $expectedRevision,
                    'evidence_reference' => $evidenceReference, 'reason' => $reason],
                function (string $type, string $id) use ($actorId, $entityKind, $profile, $terms): void {
                    $this->staff->handle($actorId, 'businesses.verify', function () use ($entityKind, $id, $profile, $terms): void {
                        if ($terms['status'] === 'active') {
                            $this->verifiedParties->handle($entityKind, $id, array_column($terms['people'], 'party_id'), fn (): bool => true,
                                $profile['company_code'] === null ? null : 'RDB:'.$profile['company_code']);
                        }
                    });
                }, function () use ($business, $actorId, $entityKind, $entityPartyId, $profile, $terms, $expectedRevision, $evidenceReference, $reason): OperationResult {
                    if (($business->revision ?? 0) !== $expectedRevision) {
                        throw new CommandRejection('VERSION_CONFLICT', 409, $business->revision ?? 0);
                    }
                    if (($business === null && $terms['status'] === 'revoked') || ($business !== null && $business->entity_kind !== $entityKind)) {
                        throw new CommandRejection('MANDATE_REQUIRED', 403);
                    }
                    $record = $business ?? new BusinessProfile;
                    $version = ($business->mandate_version ?? 0) + 1;
                    $record->forceFill(['entity_party_id' => $entityPartyId, 'entity_kind' => $entityKind, 'profile' => $profile,
                        'revision' => $expectedRevision + 1, 'mandate_version' => $version])->save();
                    (new BusinessMandate)->forceFill(['business_id' => $record->id, 'version' => $version, 'terms' => $terms, 'profile' => $profile,
                        'actor_user_id' => $actorId, 'evidence_reference' => $evidenceReference, 'reason' => $reason,
                        'policy_version' => 'engineering-2026-09-23.4'])->save();

                    return new OperationResult('BUSINESS_AUTHORITY_RECORDED', ['business' => [
                        'id' => $record->id, 'revision' => $record->revision, 'mandate_version' => $version,
                    ]], $record->revision, ['businesses.view', 'businesses.verify']);
                });
        }, 3);
    }

    /**
     * @template TResult
     *
     * @param  Closure(Business, AccessSnapshot): TResult  $operation
     * @return TResult
     */
    public function withAuthority(int $userId, int $contextRevision, string $businessId, string $permission, ?int $mandateVersion, Closure $operation): mixed
    {
        return DB::transaction(function () use ($userId, $contextRevision, $businessId, $permission, $mandateVersion, $operation): mixed {
            $business = BusinessProfile::query()->lockForUpdate()->find($businessId);
            $mandate = $business === null ? null : BusinessMandate::query()->where('business_id', $business->id)->where('version', $business->mandate_version)->first();
            $actor = $this->identities->forUser($userId)['party']['id'] ?? null;
            $people = $mandate?->terms['people'] ?? [];
            $visible = array_filter($people, fn (array $person): bool => $person['party_id'] === $actor && in_array('business.view', $person['permissions'], true));
            if ($business === null || $mandate === null || $visible === []) {
                throw new CommandRejection('BUSINESS_NOT_FOUND', 404);
            }
            $terms = $mandate->terms;
            $now = now('UTC')->format('Y-m-d\TH:i:s\Z');
            if ($terms['status'] !== 'active' || $terms['effective_at'] > $now || ($terms['expires_at'] !== null && $terms['expires_at'] <= $now)) {
                throw new CommandRejection('MANDATE_REQUIRED', 403);
            }

            return $this->roles->handle($userId, 'business', $contextRevision, $business->entity_kind, $business->entity_party_id,
                array_column($people, 'party_id'), function (array $identity) use ($business, $terms, $people, $permission, $mandateVersion, $operation): mixed {
                    $this->authority->requirePermission($people, $identity['party']['id'] ?? '', $permission);
                    if ($mandateVersion !== null && $mandateVersion !== $business->mandate_version) {
                        throw new CommandRejection('MANDATE_STALE', 409, $business->revision);
                    }

                    return $operation(['id' => $business->id, 'entity_kind' => $business->entity_kind, 'entity_party_id' => $business->entity_party_id,
                        'profile' => $business->profile, 'revision' => $business->revision, 'mandate_version' => $business->mandate_version, 'mandate' => $terms], $identity);
                }, $business->profile['company_code'] === null ? null : 'RDB:'.$business->profile['company_code']);
        }, 3);
    }

    /**
     * @template TResult
     *
     * @param  Closure(Business): TResult  $operation
     * @return TResult
     */
    public function withReview(int $actorId, string $businessId, bool $requireVerified, Closure $operation, array $additionalPersonPartyIds = []): mixed
    {
        return DB::transaction(function () use ($actorId, $businessId, $requireVerified, $operation, $additionalPersonPartyIds): mixed {
            $business = BusinessProfile::query()->lockForUpdate()->find($businessId);

            return $this->staff->handle($actorId, 'businesses.verify', function () use ($business, $requireVerified, $operation, $additionalPersonPartyIds): mixed {
                $mandate = $business === null ? null : BusinessMandate::query()->where('business_id', $business->id)->where('version', $business->mandate_version)->first();
                if ($business === null || $mandate === null) {
                    throw new CommandRejection('BUSINESS_NOT_FOUND', 404);
                }
                $terms = $mandate->terms;
                $record = ['id' => $business->id, 'entity_kind' => $business->entity_kind, 'entity_party_id' => $business->entity_party_id,
                    'profile' => $business->profile, 'revision' => $business->revision, 'mandate_version' => $business->mandate_version, 'mandate' => $terms];
                if (! $requireVerified) {
                    return $operation($record);
                }
                $now = now('UTC')->format('Y-m-d\TH:i:s\Z');
                if ($terms['status'] !== 'active' || $terms['effective_at'] > $now || ($terms['expires_at'] !== null && $terms['expires_at'] <= $now)) {
                    throw new CommandRejection('MANDATE_REQUIRED', 403);
                }

                return $this->verifiedParties->handle($business->entity_kind, $business->entity_party_id, array_values(array_unique([...array_column($terms['people'], 'party_id'), ...$additionalPersonPartyIds])),
                    fn (): mixed => $operation($record), $business->profile['company_code'] === null ? null : 'RDB:'.$business->profile['company_code']);
            });
        }, 3);
    }
}
