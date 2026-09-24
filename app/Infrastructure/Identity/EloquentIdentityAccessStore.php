<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity;

use App\Application\Identity\Contracts\IdentityAccessStore;
use App\Application\Identity\Contracts\IdentityRepository;
use App\Domain\Identity\ActiveRolePolicy;
use App\Domain\Identity\BookmarkDestination;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Identity\MembershipTransitions;
use App\Domain\Identity\StaffPermission;
use App\Models\IdentityAuditEvent;
use App\Models\IdentityOperator;
use App\Models\Party;
use App\Models\RoleBookmark;
use App\Models\RoleMembership;
use App\Models\StaffAccount;
use App\Models\User;
use App\Models\VerifiedOrganizationIdentity;
use App\Models\VerifiedPersonIdentity;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Fortify\Features;

/** @phpstan-import-type AccessSnapshot from ActiveRolePolicy */
final class EloquentIdentityAccessStore implements IdentityAccessStore
{
    public function __construct(
        private IdentityRepository $identities,
        private MembershipTransitions $transitions,
        private ActiveRolePolicy $roles,
        private BookmarkDestination $destinations,
    ) {}

    /** @return array<string, mixed> */
    public function configureOperator(int $userId, bool $enabled, string $reason, string $requestId): array
    {
        return DB::transaction(function () use ($userId, $enabled, $reason, $requestId): array {
            $user = User::query()->lockForUpdate()->findOrFail($userId);
            $hash = $this->requestHash(['operator.configure', $userId, $enabled, $reason], $reason, $requestId);

            if (($replay = $this->replay('console', $requestId, $hash)) !== null) {
                return $replay;
            }

            $party = $this->lockParty($user->party_id);

            if ($enabled && (! $this->emailVerified($user) || ! $this->mfaConfirmed($user))) {
                throw new IdentityViolation('OPERATOR_VERIFIED_EMAIL_AND_MFA_REQUIRED');
            }

            if ($enabled && $party !== null && ($party->kind !== 'person' || $party->verified_at !== null
                || $party->memberships()->exists() || $party->verifiedIdentity()->exists()
                || User::query()->where('party_id', $party->id)->whereKeyNot($userId)->exists())) {
                throw new IdentityViolation('DEDICATED_STAFF_ACCOUNT_REQUIRED');
            }

            $operator = IdentityOperator::query()->find($userId);
            if (! $enabled && $operator === null) {
                throw new IdentityViolation('IDENTITY_OPERATOR_NOT_FOUND', 404);
            }
            $before = ['enabled' => $operator->enabled ?? false, 'party_id' => $user->party_id];
            $operator ??= new IdentityOperator;
            $operator->forceFill(['user_id' => $userId, 'enabled' => $enabled])->save();

            if ($enabled) {
                $user->forceFill([
                    'party_id' => null, 'active_membership_id' => null,
                    'active_membership_revision' => null, 'context_revision' => $user->context_revision + 1,
                ])->save();
            }

            $result = ['code' => $enabled ? 'IDENTITY_OPERATOR_ENABLED' : 'IDENTITY_OPERATOR_DISABLED', 'user_id' => $userId];
            $this->record('console', null, 'user', (string) $userId, 'operator.configure', $reason, $requestId, $hash,
                $before, ['enabled' => $enabled, 'party_id' => $user->party_id], $result);

            return $result;
        }, 3);
    }

    /**
     * @param  list<string>  $roles
     * @return array<string, mixed>
     */
    public function configureStaff(int $userId, bool $enabled, string $reason, string $requestId, array $roles = []): array
    {
        $roles = StaffPermission::normalizeRoles($roles);

        return DB::transaction(function () use ($userId, $enabled, $reason, $requestId, $roles): array {
            $user = User::query()->lockForUpdate()->findOrFail($userId);
            $input = ['staff.configure', $userId, $enabled, $reason];
            if ($roles !== []) {
                $input[] = $roles;
            }
            $hash = $this->requestHash($input, $reason, $requestId);

            if (($replay = $this->replay('console', $requestId, $hash)) !== null) {
                return $replay;
            }

            $party = $this->lockParty($user->party_id);

            if ($enabled && (! $this->emailVerified($user) || ! $this->mfaConfirmed($user))) {
                throw new IdentityViolation('STAFF_VERIFIED_EMAIL_AND_MFA_REQUIRED');
            }

            if ($enabled && $party !== null && ($party->kind !== 'person' || $party->verified_at !== null
                || $party->memberships()->exists() || $party->verifiedIdentity()->exists()
                || User::query()->where('party_id', $party->id)->whereKeyNot($userId)->exists())) {
                throw new IdentityViolation('DEDICATED_STAFF_ACCOUNT_REQUIRED');
            }

            $staff = StaffAccount::query()->find($userId);
            if (! $enabled && $staff === null) {
                throw new IdentityViolation('STAFF_ACCOUNT_NOT_FOUND', 404);
            }
            $before = ['enabled' => $staff->enabled ?? false, 'party_id' => $user->party_id, 'roles' => $staff->roles ?? []];
            $staff ??= new StaffAccount;
            $staff->forceFill(['user_id' => $userId, 'enabled' => $enabled, 'roles' => $roles])->save();

            if ($enabled) {
                $user->forceFill([
                    'party_id' => null, 'active_membership_id' => null,
                    'active_membership_revision' => null, 'context_revision' => $user->context_revision + 1,
                ])->save();
            }

            $result = ['code' => $enabled ? 'STAFF_ACCESS_ENABLED' : 'STAFF_ACCESS_DISABLED', 'user_id' => $userId];
            $this->record('console', null, 'user', (string) $userId, 'staff.configure', $reason, $requestId, $hash,
                $before, ['enabled' => $enabled, 'party_id' => $user->party_id, 'roles' => $roles], $result);

            return $result;
        }, 3);
    }

    /** @return array<string, mixed> */
    public function resolvePerson(int $actorId, int $userId, string $identityReference, string $evidenceReference, string $reason, string $requestId): array
    {
        return DB::transaction(function () use ($actorId, $userId, $identityReference, $evidenceReference, $reason, $requestId): array {
            $users = User::query()->whereKey([$actorId, $userId])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $this->authorizeOperator($users->get($actorId));
            $user = $users->get($userId);

            if ($user === null) {
                throw new IdentityViolation('IDENTITY_RECORD_NOT_FOUND', 404);
            }

            $this->evidenceRequired($evidenceReference);
            if (mb_strlen($identityReference) > 255 || ! preg_match('/^[a-z0-9._-]+:[A-Za-z0-9._-]{1,128}$/D', $identityReference)) {
                throw new IdentityViolation('IDENTITY_REFERENCE_INVALID', 422);
            }

            $digest = hash('sha256', $identityReference);
            $hash = $this->requestHash(['person.resolve', $userId, $digest, $evidenceReference, $reason], $reason, $requestId);
            if (($replay = $this->replay('user:'.$actorId, $requestId, $hash)) !== null) {
                return $replay;
            }

            if (! $this->emailVerified($user) || IdentityOperator::query()->whereKey($userId)->exists()
                || StaffAccount::query()->whereKey($userId)->exists()) {
                throw new IdentityViolation('VERIFIED_PARTICIPANT_ACCOUNT_REQUIRED');
            }

            DB::select('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))', ['identity:'.$digest]);
            $identity = VerifiedPersonIdentity::query()->find($digest);
            $partyIds = array_filter([$user->party_id, $identity?->party_id]);
            $parties = Party::query()->whereKey($partyIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $source = $parties->get($user->party_id);
            $party = $identity === null ? $source : $parties->get($identity->party_id);

            if ($source !== null && ($source->kind !== 'person'
                || ($source->id !== $identity?->party_id && ($source->memberships()->exists() || $source->verifiedIdentity()->exists())))) {
                throw new IdentityViolation('IDENTITY_RECONCILIATION_REQUIRED', 409);
            }

            if ($identity !== null && ($party === null || $party->kind !== 'person' || $party->verified_at === null
                || $party->verified_at->gt(now()))) {
                throw new IdentityViolation('IDENTITY_VERIFICATION_REQUIRED');
            }

            $before = ['party_id' => $user->party_id];
            if ($identity === null) {
                $party ??= Party::query()->create(['kind' => 'person']);
                $party->forceFill(['verified_at' => now()])->save();
                (new VerifiedPersonIdentity)->forceFill([
                    'identity_digest' => $digest, 'party_id' => $party->id, 'evidence_reference' => $evidenceReference,
                ])->save();
            }

            /** @var Party $party */
            $user->forceFill([
                'party_id' => $party->id, 'active_membership_id' => null,
                'active_membership_revision' => null, 'context_revision' => $user->context_revision + 1,
            ])->save();
            $result = ['code' => 'VERIFIED_PERSON_RESOLVED', 'user_id' => $userId, 'party_id' => $party->id];
            $this->record('user:'.$actorId, $actorId, 'user', (string) $userId, 'person.resolve', $reason, $requestId, $hash,
                $before, ['party_id' => $party->id, 'evidence_reference' => $evidenceReference, 'identity_digest' => $digest], $result);

            return $result;
        }, 3);
    }

    /** @return array<string, mixed> */
    public function changeMembership(int $actorId, string $partyId, string $role, string $status, int $expectedRevision, string $evidenceReference, string $reason, string $requestId): array
    {
        return DB::transaction(function () use ($actorId, $partyId, $role, $status, $expectedRevision, $evidenceReference, $reason, $requestId): array {
            $this->authorizeOperator(User::query()->lockForUpdate()->find($actorId));
            $this->evidenceRequired($evidenceReference);
            $hash = $this->requestHash(['membership.change', $partyId, $role, $status, $expectedRevision, $evidenceReference, $reason], $reason, $requestId);
            if (($replay = $this->replay('user:'.$actorId, $requestId, $hash)) !== null) {
                return $replay;
            }

            $party = $this->lockParty($partyId);
            if ($party === null) {
                throw new IdentityViolation('IDENTITY_RECORD_NOT_FOUND', 404);
            }
            if ($party->kind !== 'person') {
                throw new IdentityViolation('PARTY_AUTHORITY_REQUIRED');
            }
            if (in_array($status, ['pending', 'active'], true) && ($party->verified_at === null
                || $party->verified_at->gt(now()) || ! $party->verifiedIdentity()->exists())) {
                throw new IdentityViolation('IDENTITY_VERIFICATION_REQUIRED');
            }

            $memberships = $party->memberships()->get();
            $revision = $this->transitions->nextRevision($role, $status, $expectedRevision,
                array_values($memberships->map(fn (RoleMembership $membership): array => [
                    'role' => $membership->role, 'status' => $membership->status, 'revision' => $membership->revision,
                ])->all()));
            $membership = $memberships->firstWhere('role', $role);
            $before = $membership === null ? [] : ['status' => $membership->status, 'revision' => $membership->revision];
            $membership ??= new RoleMembership;
            $membership->forceFill(['party_id' => $partyId, 'role' => $role, 'status' => $status, 'revision' => $revision])->save();
            $result = ['code' => 'MEMBERSHIP_UPDATED', 'party_id' => $partyId, 'membership' => [
                'id' => $membership->id, 'role' => $role, 'status' => $status, 'revision' => $revision,
            ]];
            $this->record('user:'.$actorId, $actorId, 'membership', $membership->id, 'membership.change', $reason, $requestId, $hash,
                $before, ['status' => $status, 'revision' => $revision, 'evidence_reference' => $evidenceReference], $result);

            return $result;
        }, 3);
    }

    public function selectRole(int $userId, string $role, int $expectedRevision, string $requestId): void
    {
        DB::transaction(function () use ($userId, $role, $expectedRevision, $requestId): void {
            $user = User::query()->lockForUpdate()->findOrFail($userId);
            $this->lockParty($user->party_id);
            $identity = $this->identities->forUser($userId);
            $membership = $this->roles->selectable($identity, $role);
            $reason = 'Participant selected the active role.';
            $hash = $this->requestHash(['role.select', $role, $expectedRevision], $reason, $requestId);
            if ($this->replay('user:'.$userId, $requestId, $hash) !== null) {
                return;
            }
            if ($identity['context_revision'] !== $expectedRevision) {
                throw new IdentityViolation('ACTIVE_ROLE_REVISION_CONFLICT', 409);
            }

            $before = ['membership_id' => $user->active_membership_id, 'context_revision' => $user->context_revision];
            $user->forceFill([
                'active_membership_id' => $membership['id'], 'active_membership_revision' => $membership['revision'],
                'context_revision' => $expectedRevision + 1,
            ])->save();
            $after = ['membership_id' => $membership['id'], 'membership_revision' => $membership['revision'], 'context_revision' => $expectedRevision + 1];
            $this->record('user:'.$userId, $userId, 'user', (string) $userId, 'role.select', $reason, $requestId, $hash,
                $before, $after, $after);
        }, 3);
    }

    /**
     * @template TResult
     *
     * @param  Closure(AccessSnapshot): TResult  $operation
     * @return TResult
     */
    public function withActiveRole(int $userId, string $role, ?string $recordPartyId, ?int $expectedContext, Closure $operation): mixed
    {
        return DB::transaction(function () use ($userId, $role, $recordPartyId, $expectedContext, $operation): mixed {
            $user = User::query()->lockForUpdate()->findOrFail($userId);
            $this->lockParty($user->party_id);
            $identity = $this->identities->forUser($userId);
            $this->roles->authorize($identity, $role, $recordPartyId, $expectedContext);

            return $operation($identity);
        }, 3);
    }

    /** @return array<string, mixed> */
    public function staffAccess(int $userId, bool $required = false): array
    {
        return DB::transaction(function () use ($userId, $required): array {
            $user = User::query()->lockForUpdate()->findOrFail($userId);
            $staff = StaffAccount::query()->find($userId);
            $allowed = $user->party_id === null && $this->emailVerified($user) && $this->mfaConfirmed($user)
                && $staff !== null && $staff->enabled;
            if ($required && ! $allowed) {
                throw new IdentityViolation('STAFF_ACCESS_REQUIRED');
            }

            return ['contract_version' => 'staff-access-v1', 'can_open_admin' => $allowed,
                'allowed_actions' => $allowed ? StaffPermission::forRoles($staff->roles) : []];
        }, 3);
    }

    /**
     * @template TResult
     *
     * @param  Closure(): TResult  $operation
     * @return TResult
     */
    public function withStaffPermission(int $userId, string $permission, Closure $operation): mixed
    {
        return DB::transaction(function () use ($userId, $permission, $operation): mixed {
            $access = $this->staffAccess($userId, true);
            if (! in_array($permission, $access['allowed_actions'], true)) {
                throw new IdentityViolation('STAFF_PERMISSION_REQUIRED');
            }

            return $operation();
        }, 3);
    }

    /** @return array<string, mixed> */
    public function bookmark(int $userId, string $role): array
    {
        return $this->withActiveRole($userId, $role, null, null,
            fn (array $identity): array => $this->currentBookmark($userId, $role, $identity));
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function saveBookmark(int $userId, string $role, string $route, array $parameters, array $query, int $expectedContext, string $requestId): array
    {
        return $this->withActiveRole($userId, $role, null, $expectedContext,
            function (array $identity) use ($userId, $role, $route, $parameters, $query, $expectedContext, $requestId): array {
                $destination = $this->destinations->validate($role, $route, $parameters, $query);
                $reason = 'Participant saved a role navigation position.';
                $hash = $this->requestHash(['bookmark.save', $role, $destination, $expectedContext], $reason, $requestId);
                if ($this->replay('user:'.$userId, $requestId, $hash) !== null) {
                    return $this->currentBookmark($userId, $role, $identity);
                }
                $bookmark = RoleBookmark::query()->where('user_id', $userId)->where('role', $role)->first();
                $before = $bookmark?->only(['route', 'parameters', 'query', 'membership_id', 'membership_revision']) ?? [];
                $bookmark ??= new RoleBookmark;
                $after = array_merge($destination, [
                    'membership_id' => $identity['active_membership_id'],
                    'membership_revision' => $identity['active_membership_revision'],
                ]);
                $bookmark->forceFill(array_merge($after, ['user_id' => $userId, 'role' => $role]))->save();
                $result = $this->currentBookmark($userId, $role, $identity);
                $this->record('user:'.$userId, $userId, 'bookmark', $bookmark->id, 'bookmark.save', $reason, $requestId, $hash,
                    $before, $after, $result);

                return $result;
            });
    }

    /**
     * @param  AccessSnapshot  $identity
     * @return array<string, mixed>
     */
    private function currentBookmark(int $userId, string $role, array $identity): array
    {
        $bookmark = RoleBookmark::query()->where('user_id', $userId)->where('role', $role)->first();
        $destination = $this->destinations->validate($role, $role.'.home', [], []);
        if ($bookmark !== null && $bookmark->membership_id === $identity['active_membership_id']
            && $bookmark->membership_revision === $identity['active_membership_revision']) {
            try {
                $destination = $this->destinations->validate($role, $bookmark->route, $bookmark->parameters, $bookmark->query);
            } catch (IdentityViolation) {
                // Removed or invalid destinations resolve to the authorized home.
            }
        }

        return array_merge(['contract_version' => 'role-bookmark-v1', 'role' => $role,
            'context_revision' => $identity['context_revision']], $destination);
    }

    /** @return array<string, mixed> */
    public function resolveOrganization(int $actorId, string $registryReference, string $evidenceReference, string $reason, string $requestId): array
    {
        return $this->withStaffPermission($actorId, 'businesses.verify', function () use ($actorId, $registryReference, $evidenceReference, $reason, $requestId): array {
            $this->evidenceRequired($evidenceReference);
            $reference = strtoupper($registryReference);
            if (! preg_match('/^RDB:[A-Z0-9][A-Z0-9.-]{1,127}$/D', $reference)) {
                throw new IdentityViolation('REGISTRY_REFERENCE_INVALID', 422);
            }
            $digest = hash('sha256', $reference);
            $hash = $this->requestHash(['organization.resolve', $digest, $evidenceReference, $reason], $reason, $requestId);
            if (($replay = $this->replay('user:'.$actorId, $requestId, $hash)) !== null) {
                return $replay;
            }
            DB::select('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))', ['organization:'.$digest]);
            $identity = VerifiedOrganizationIdentity::query()->find($digest);
            $party = $identity === null ? null : $this->lockParty($identity->party_id);
            if ($identity !== null && ($party === null || $party->kind !== 'organization' || $party->verified_at === null || $party->verified_at->gt(now()))) {
                throw new IdentityViolation('ORGANIZATION_VERIFICATION_REQUIRED');
            }
            $before = ['party_id' => $party?->id];
            if ($identity === null) {
                $party = new Party;
                $party->forceFill(['kind' => 'organization', 'verified_at' => now()])->save();
                (new VerifiedOrganizationIdentity)->forceFill([
                    'registry_digest' => $digest, 'party_id' => $party->id, 'evidence_reference' => $evidenceReference,
                ])->save();
            }
            $result = ['code' => 'ORGANIZATION_RESOLVED', 'party_id' => $party->id];
            $this->record('user:'.$actorId, $actorId, 'party', $party->id, 'organization.resolve', $reason, $requestId, $hash,
                $before, ['party_id' => $party->id, 'evidence_reference' => $evidenceReference], $result);

            return $result;
        });
    }

    /**
     * @template TResult
     *
     * @param  list<string>  $personPartyIds
     * @param  Closure(): TResult  $operation
     * @return TResult
     */
    public function withVerifiedParties(string $entityKind, string $entityPartyId, array $personPartyIds, Closure $operation, ?string $registryReference = null): mixed
    {
        return DB::transaction(function () use ($entityKind, $entityPartyId, $personPartyIds, $operation, $registryReference): mixed {
            if (! in_array($entityKind, ['person', 'organization'], true) || $personPartyIds === []
                || ($entityKind === 'person' && ! in_array($entityPartyId, $personPartyIds, true))) {
                throw new IdentityViolation('PARTY_AUTHORITY_REQUIRED');
            }
            $personIds = array_values(array_unique($personPartyIds));
            $ids = array_values(array_unique([$entityPartyId, ...$personIds]));
            $parties = Party::query()->whereKey($ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if ($parties->count() !== count($ids)) {
                throw new IdentityViolation('PARTY_AUTHORITY_REQUIRED');
            }
            foreach ($parties as $party) {
                $expectedKind = $party->id === $entityPartyId ? $entityKind : 'person';
                if ($party->kind !== $expectedKind || $party->verified_at === null || $party->verified_at->gt(now())) {
                    throw new IdentityViolation('PARTY_AUTHORITY_REQUIRED');
                }
            }
            if (VerifiedPersonIdentity::query()->whereIn('party_id', $personIds)->count() !== count($personIds)
                || ($entityKind === 'organization' && ! VerifiedOrganizationIdentity::query()->where('party_id', $entityPartyId)->exists())) {
                throw new IdentityViolation('PARTY_AUTHORITY_REQUIRED');
            }
            if ($registryReference !== null && ($entityKind !== 'organization'
                || ! VerifiedOrganizationIdentity::query()->where('party_id', $entityPartyId)->whereKey(hash('sha256', strtoupper($registryReference)))->exists())) {
                throw new IdentityViolation('ORGANIZATION_REFERENCE_MISMATCH');
            }

            return $operation();
        }, 3);
    }

    /**
     * @template TResult
     *
     * @param  list<string>  $personPartyIds
     * @param  Closure(AccessSnapshot): TResult  $operation
     * @return TResult
     */
    public function withEntityRole(int $userId, string $role, int $expectedContext, string $entityKind, string $entityPartyId, array $personPartyIds, Closure $operation, ?string $registryReference = null): mixed
    {
        return DB::transaction(function () use ($userId, $role, $expectedContext, $entityKind, $entityPartyId, $personPartyIds, $operation, $registryReference): mixed {
            $user = User::query()->lockForUpdate()->findOrFail($userId);
            if ($user->party_id === null || ! in_array($user->party_id, $personPartyIds, true)) {
                throw new IdentityViolation('MANDATE_REQUIRED');
            }

            return $this->withVerifiedParties($entityKind, $entityPartyId, $personPartyIds, function () use ($userId, $role, $expectedContext, $operation): mixed {
                $identity = $this->identities->forUser($userId);
                $this->roles->authorize($identity, $role, null, $expectedContext);

                return $operation($identity);
            }, $registryReference);
        }, 3);
    }

    private function authorizeOperator(?User $user): void
    {
        if ($user === null || $user->party_id !== null || ! $this->emailVerified($user)
            || ! $this->mfaConfirmed($user) || ! IdentityOperator::query()->whereKey($user->id)->where('enabled', true)->exists()) {
            throw new IdentityViolation('IDENTITY_OPERATOR_REQUIRED');
        }
    }

    private function emailVerified(User $user): bool
    {
        return $user->email_verified_at !== null && $user->email_verified_at->lte(now());
    }

    private function mfaConfirmed(User $user): bool
    {
        return Features::enabled(Features::twoFactorAuthentication()) && $user->two_factor_secret !== null
            && $user->two_factor_confirmed_at !== null && $user->two_factor_confirmed_at->lte(now());
    }

    private function lockParty(?string $partyId): ?Party
    {
        return $partyId === null ? null : Party::query()->lockForUpdate()->find($partyId);
    }

    private function evidenceRequired(string $evidence): void
    {
        if (trim($evidence) === '' || mb_strlen($evidence) > 255) {
            throw new IdentityViolation('IDENTITY_EVIDENCE_REQUIRED', 422);
        }
    }

    /** @param list<mixed> $input */
    private function requestHash(array $input, string $reason, string $requestId): string
    {
        if (trim($reason) === '' || mb_strlen($reason) > 1000 || ! Str::isUuid($requestId)) {
            throw new IdentityViolation('IDENTITY_COMMAND_INVALID', 422);
        }

        return hash('sha256', json_encode($input, JSON_THROW_ON_ERROR));
    }

    /** @return array<string, mixed>|null */
    private function replay(string $actorKey, string $requestId, string $hash): ?array
    {
        $event = IdentityAuditEvent::query()->where('actor_key', $actorKey)->where('request_id', $requestId)->first();
        if ($event !== null && ! hash_equals($event->request_hash, $hash)) {
            throw new IdentityViolation('IDEMPOTENCY_KEY_REUSED', 409);
        }

        return $event?->result;
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @param  array<string, mixed>  $result
     */
    private function record(string $actorKey, ?int $actorId, string $targetType, string $targetId, string $action, string $reason, string $requestId, string $hash, array $before, array $after, array $result): void
    {
        (new IdentityAuditEvent)->forceFill([
            'actor_key' => $actorKey, 'actor_user_id' => $actorId, 'target_type' => $targetType, 'target_id' => $targetId,
            'action' => $action, 'reason' => $reason, 'request_id' => $requestId, 'request_hash' => $hash,
            'before' => $before, 'after' => $after, 'result' => $result, 'policy_version' => ActiveRolePolicy::POLICY_VERSION,
        ])->save();
    }
}
