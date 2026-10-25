<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity;

use App\Application\Identity\Contracts\IdentityAccessStore;
use App\Application\Identity\Contracts\IdentityRepository;
use App\Application\Identity\VerifyAuthenticator;
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
use App\Models\StaffPersonIdentity;
use App\Models\User;
use App\Models\VerifiedOrganizationIdentity;
use App\Models\VerifiedPersonIdentity;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Laravel\Fortify\Features;

/** @phpstan-import-type AccessSnapshot from ActiveRolePolicy */
final class EloquentIdentityAccessStore implements IdentityAccessStore
{
    private const STAFF_APPROVAL_POLICY = 'staff-superadmin-approval-v1';

    private const STAFF_RECOVERY_POLICY = 'staff-two-operator-recovery-v1';

    public function __construct(
        private IdentityRepository $identities,
        private MembershipTransitions $transitions,
        private ActiveRolePolicy $roles,
        private BookmarkDestination $destinations,
        private VerifyAuthenticator $authenticator,
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

            if ($enabled) {
                $this->requireDedicated($user, $party);
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
            if (in_array('superadmin', $roles, true) && ($staff === null || ! in_array('superadmin', $staff->roles, true)
                || ($enabled && ! $staff->enabled))) {
                throw new IdentityViolation('STAFF_SUPERADMIN_APPROVAL_REQUIRED', 409);
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

    /**
     * A superadmin suspends or reactivates another operator's staff access with a reason (ADM-R02).
     * Roles are kept, so reactivating restores exactly what was suspended; reactivation still needs
     * a verified email and confirmed MFA. Nobody changes their own access here.
     *
     * @return array<string, mixed>
     */
    public function setStaffEnabled(int $actorId, int $userId, bool $enabled, string $reason, string $requestId): array
    {
        return DB::transaction(function () use ($actorId, $userId, $enabled, $reason, $requestId): array {
            $hash = $this->requestHash(['staff.enable', $userId, $enabled, $reason], $reason, $requestId);
            User::query()->whereKey([$actorId, $userId])->orderBy('id')->lockForUpdate()->get();
            $this->requireStaffPermission($actorId, 'staff.manage');
            if (($replay = $this->replay('user:'.$actorId, $requestId, $hash)) !== null) {
                return $replay;
            }
            if ($actorId === $userId) {
                throw new IdentityViolation('STAFF_SELF_CHANGE_FORBIDDEN', 409);
            }
            $user = User::query()->lockForUpdate()->find($userId);
            $staff = StaffAccount::query()->lockForUpdate()->find($userId);
            if ($user === null || $staff === null) {
                throw new IdentityViolation('STAFF_ACCOUNT_NOT_FOUND', 404);
            }
            if ($staff->enabled === $enabled) {
                throw new IdentityViolation('STAFF_STATE_UNCHANGED', 409);
            }
            if ($enabled && (! $this->emailVerified($user) || ! $this->mfaConfirmed($user))) {
                throw new IdentityViolation('STAFF_VERIFIED_EMAIL_AND_MFA_REQUIRED');
            }
            if ($enabled && in_array('superadmin', $staff->roles, true)) {
                return $this->requestSuperadmin($actorId, $user, $staff, 'reactivate', $reason, $requestId, $hash);
            }
            $before = ['enabled' => $staff->enabled, 'party_id' => $user->party_id, 'roles' => $staff->roles];
            $staff->forceFill(['enabled' => $enabled])->save();
            $result = ['code' => $enabled ? 'STAFF_ACCESS_ENABLED' : 'STAFF_ACCESS_DISABLED', 'user_id' => $userId];
            $this->record('user:'.$actorId, $actorId, 'user', (string) $userId, 'staff.configure', $reason, $requestId, $hash,
                $before, ['enabled' => $enabled, 'party_id' => $user->party_id, 'roles' => $staff->roles], $result);

            return $result;
        }, 3);
    }

    /** @return array<string, mixed> */
    public function grantStaff(int $actorId, string $email, string $role, string $reason, string $requestId): array
    {
        return DB::transaction(function () use ($actorId, $email, $role, $reason, $requestId): array {
            $email = mb_strtolower(trim($email));
            $hash = $this->requestHash(['staff.grant', $email, $role, $reason], $reason, $requestId);
            $candidate = User::query()->whereRaw('lower(email) = ?', [$email])->first();
            User::query()->whereKey([$actorId, ...($candidate === null ? [] : [$candidate->id])])->orderBy('id')->lockForUpdate()->get();
            $this->requireStaffPermission($actorId, 'staff.manage');
            if (($replay = $this->replay('user:'.$actorId, $requestId, $hash)) !== null) {
                return $replay;
            }
            $roles = StaffPermission::normalizeRoles([$role]);
            $user = $candidate === null ? null : User::query()->find($candidate->id);
            if ($user === null || mb_strtolower($user->email) !== $email) {
                throw new IdentityViolation('STAFF_EMAIL_NOT_FOUND', 404);
            }
            if ($user->id === $actorId) {
                throw new IdentityViolation('STAFF_SELF_CHANGE_FORBIDDEN', 409);
            }
            $staff = StaffAccount::query()->lockForUpdate()->find($user->id);
            if ($staff !== null) {
                throw new IdentityViolation('STAFF_ALREADY_OPERATOR', 409);
            }
            $this->requireStaffRecipient($user);
            if ($role === 'superadmin') {
                return $this->requestSuperadmin($actorId, $user, null, 'grant', $reason, $requestId, $hash);
            }

            return $this->enableStaff($actorId, $user, null, $roles, $reason, $requestId, $hash);
        }, 3);
    }

    /** @return array<string, mixed> */
    public function approveStaff(int $actorId, string $proposalId, string $reason, string $requestId): array
    {
        return DB::transaction(function () use ($actorId, $proposalId, $reason, $requestId): array {
            $hash = $this->requestHash(['staff.superadmin.approve', $proposalId, $reason], $reason, $requestId);
            $proposal = IdentityAuditEvent::query()->where('action', 'staff.superadmin.request')->where('target_type', 'user')->find($proposalId);
            $recipientId = $proposal === null ? null : (int) $proposal->target_id;
            $requesterId = $proposal?->actor_user_id;
            User::query()->whereKey(array_filter([$actorId, $requesterId, $recipientId]))->orderBy('id')->lockForUpdate()->get();
            $this->requireStaffPermission($actorId, 'staff.manage');
            if (($replay = $this->replay('user:'.$actorId, $requestId, $hash)) !== null) {
                return $replay;
            }
            if ($proposal === null || $requesterId === null || $recipientId === null) {
                throw new IdentityViolation('STAFF_APPROVAL_NOT_FOUND', 404);
            }
            if ($actorId === $requesterId || $actorId === $recipientId || $requesterId === $recipientId) {
                throw new IdentityViolation('STAFF_SELF_APPROVAL_FORBIDDEN', 409);
            }
            $this->requireStaffPermission($requesterId, 'staff.manage');
            if (IdentityAuditEvent::query()->where('action', 'staff.configure')->where('after->proposal_id', $proposalId)->exists()) {
                throw new IdentityViolation('STAFF_APPROVAL_COMPLETED', 409);
            }
            $user = User::query()->find($recipientId);
            $staff = StaffAccount::query()->lockForUpdate()->find($recipientId);
            if ($user === null || ($proposal->after['approval_policy_version'] ?? null) !== self::STAFF_APPROVAL_POLICY
                || $this->staffBaseline($user, $staff) != $proposal->before) {
                throw new IdentityViolation('STAFF_APPROVAL_STALE', 409);
            }
            $this->requireStaffRecipient($user);
            $operation = $proposal->after['operation'] ?? null;
            if (($operation === 'grant' && $staff !== null) || ($operation === 'reactivate' && ($staff === null || $staff->enabled
                || ! in_array('superadmin', $staff->roles, true))) || ! in_array($operation, ['grant', 'reactivate'], true)) {
                throw new IdentityViolation('STAFF_APPROVAL_STALE', 409);
            }

            return $this->enableStaff($actorId, $user, $staff, $operation === 'grant' ? ['superadmin'] : $staff->roles,
                $reason, $requestId, $hash, ['proposal_id' => $proposalId, 'requested_by' => $requesterId, 'request_reason' => $proposal->reason,
                    'approval_policy_version' => self::STAFF_APPROVAL_POLICY]);
        }, 3);
    }

    /** @param list<int> $recipients
     * @return array<string, mixed>
     */
    public function requestStaffRecovery(int $operatorId, #[\SensitiveParameter] string $password, #[\SensitiveParameter] string $code, string $kind, array $recipients, string $reason, string $requestId): array
    {
        sort($recipients);
        if (! in_array($kind, ['genesis', 'recovery'], true) || count($recipients) !== 2
            || count(array_unique($recipients)) !== 2 || min($recipients) < 1) {
            throw new IdentityViolation('STAFF_RECOVERY_INPUT_INVALID', 422);
        }
        $hash = $this->requestHash(['staff.recovery.request', $kind, $recipients, $reason], $reason, $requestId);
        $this->recoveryThrottle($operatorId);
        $result = DB::transaction(function () use ($operatorId, $password, $code, $kind, $recipients, $reason, $requestId, $hash): array {
            $holders = $this->lockRecoveryAccounts([$operatorId, ...$recipients]);
            $operator = $this->recoveryOperator($operatorId, $password, $code);
            if (($replay = $this->replay('recovery:'.$operatorId, $requestId, $hash)) !== null) {
                return $replay;
            }
            $state = $this->recoveryState($kind, $recipients, $holders);
            $people = array_column($state['recipients'], 'person');
            if (in_array($operator['person']['digest'], array_column($people, 'digest'), true)) {
                throw new IdentityViolation('STAFF_SELF_APPROVAL_FORBIDDEN', 409);
            }
            $proposalId = strtolower((string) Str::ulid());
            $payload = ['policy' => self::STAFF_RECOVERY_POLICY, 'kind' => $kind, 'recipients' => $recipients,
                'requester' => $operator, 'registry_sha256' => $this->recoveryRegistryDigest(),
                'expires_at' => now()->addMinutes(10)->toIso8601String(), 'reason' => $reason];
            $digest = hash('sha256', json_encode([$state, $payload], JSON_THROW_ON_ERROR));
            $result = ['code' => 'STAFF_RECOVERY_APPROVAL_REQUIRED', 'proposal_id' => $proposalId,
                'kind' => $kind, 'recipients' => $recipients, 'expires_at' => $payload['expires_at'], 'payload_sha256' => $digest];
            $this->record('recovery:'.$operatorId, $operatorId, 'staff_recovery', $proposalId, 'staff.recovery.request',
                $reason, $requestId, $hash, $state, [...$payload, 'payload_sha256' => $digest], $result, $proposalId);

            return $result;
        });
        RateLimiter::clear('staff-recovery:'.$operatorId);

        return $result;
    }

    /** @param list<int> $expectedRecipients
     * @return array<string, mixed>
     */
    public function approveStaffRecovery(int $operatorId, #[\SensitiveParameter] string $password, #[\SensitiveParameter] string $code, string $proposalId, string $payloadSha256, string $reason, string $requestId, string $expectedKind, array $expectedRecipients): array
    {
        sort($expectedRecipients);
        $hash = $this->requestHash(['staff.recovery.approve', $proposalId, $payloadSha256, $expectedKind, $expectedRecipients, $reason], $reason, $requestId);
        $this->recoveryThrottle($operatorId);
        $result = DB::transaction(function () use ($operatorId, $password, $code, $proposalId, $payloadSha256, $reason, $requestId, $hash, $expectedKind, $expectedRecipients): array {
            $proposal = IdentityAuditEvent::query()->where('action', 'staff.recovery.request')->where('target_type', 'staff_recovery')->find($proposalId);
            if ($proposal === null) {
                throw new IdentityViolation('STAFF_RECOVERY_NOT_FOUND', 404);
            }
            /** @var list<int> $recipients */
            $recipients = $proposal->after['recipients'];
            $requesterId = (int) $proposal->actor_user_id;
            $holders = $this->lockRecoveryAccounts([$operatorId, $requesterId, ...$recipients]);
            $operator = $this->recoveryOperator($operatorId, $password, $code);
            if (($replay = $this->replay('recovery:'.$operatorId, $requestId, $hash)) !== null) {
                return $replay;
            }
            if (IdentityAuditEvent::query()->where('action', 'staff.recovery.complete')->where('after->recovery_proposal_id', $proposalId)->exists()) {
                throw new IdentityViolation('STAFF_RECOVERY_COMPLETED', 409);
            }
            if ($operatorId === $requesterId) {
                throw new IdentityViolation('STAFF_SELF_APPROVAL_FORBIDDEN', 409);
            }
            if (($proposal->after['policy'] ?? null) !== self::STAFF_RECOVERY_POLICY
                || $expectedKind !== $proposal->after['kind'] || $expectedRecipients !== $recipients
                || ! preg_match('/^[a-f0-9]{64}$/D', $payloadSha256)
                || ! hash_equals($proposal->after['payload_sha256'], $payloadSha256)) {
                throw new IdentityViolation('STAFF_RECOVERY_PAYLOAD_MISMATCH', 409);
            }
            if (now()->gte(CarbonImmutable::parse($proposal->after['expires_at']))) {
                throw new IdentityViolation('STAFF_RECOVERY_EXPIRED', 409);
            }
            $requester = $this->recoveryOperatorBinding($requesterId);
            if ($requester != $proposal->after['requester']
                || ! hash_equals($this->recoveryRegistryDigest(), $proposal->after['registry_sha256'])) {
                throw new IdentityViolation('STAFF_RECOVERY_STALE', 409);
            }
            $kind = $proposal->after['kind'];
            $state = $this->recoveryState($kind, $recipients, $holders);
            if ($state != $proposal->before) {
                throw new IdentityViolation('STAFF_RECOVERY_STALE', 409);
            }
            $people = [$requester['person']['digest'], $operator['person']['digest'],
                ...array_column(array_column($state['recipients'], 'person'), 'digest')];
            if (count(array_unique($people)) !== 4) {
                throw new IdentityViolation('STAFF_SELF_APPROVAL_FORBIDDEN', 409);
            }
            $grants = [];
            foreach ($recipients as $recipientId) {
                $user = User::query()->findOrFail($recipientId);
                $staff = StaffAccount::query()->findOrFail($recipientId);
                $childId = (string) Str::uuid();
                $roles = StaffPermission::normalizeRoles([...$staff->roles, 'superadmin']);
                $grants[] = $this->enableStaff($operatorId, $user, $staff, $roles, $reason, $childId,
                    $this->requestHash(['staff.recovery.grant', $proposalId, $recipientId, $reason], $reason, $childId),
                    ['recovery_proposal_id' => $proposalId, 'requested_by' => $requesterId,
                        'approval_policy_version' => self::STAFF_RECOVERY_POLICY]);
            }
            $result = ['code' => 'STAFF_RECOVERY_COMPLETED', 'proposal_id' => $proposalId, 'recipients' => $recipients];
            $this->record('recovery:'.$operatorId, $operatorId, 'staff_recovery', $proposalId, 'staff.recovery.complete',
                $reason, $requestId, $hash, $state, ['recovery_proposal_id' => $proposalId, 'kind' => $kind,
                    'policy' => self::STAFF_RECOVERY_POLICY, 'requester' => $requester, 'approver' => $operator,
                    'payload_sha256' => $payloadSha256, 'grants' => $grants], $result);

            return $result;
        });
        RateLimiter::clear('staff-recovery:'.$operatorId);

        return $result;
    }

    private function recoveryThrottle(int $operatorId): void
    {
        if (RateLimiter::tooManyAttempts('staff-recovery:'.$operatorId, 5)) {
            throw new IdentityViolation('STAFF_RECOVERY_AUTHENTICATION_THROTTLED', 429);
        }
        RateLimiter::hit('staff-recovery:'.$operatorId, 60);
    }

    /**
     * All access writers lock their User rows. Serializing recovery and locking every role holder
     * prevents an ordinary two-manager approval from racing the below-two-manager decision.
     * A changed roster is refused rather than taking extra User locks out of order.
     *
     * @param  list<int>  $participants
     * @return list<int>
     */
    private function lockRecoveryAccounts(array $participants): array
    {
        DB::select('SELECT pg_advisory_xact_lock(18492, 7301)');
        $holders = $this->recoveryHolders();
        User::query()->whereKey(array_unique([...$holders, ...$participants]))->orderBy('id')->lockForUpdate()->get();
        if ($holders !== $this->recoveryHolders()) {
            throw new IdentityViolation('STAFF_RECOVERY_STALE', 409);
        }

        return $holders;
    }

    /** @return list<int> */
    private function recoveryHolders(): array
    {
        return array_values(StaffAccount::query()->whereJsonContains('roles', 'superadmin')->orderBy('user_id')->get()
            ->map(fn (StaffAccount $staff): int => $staff->user_id)->all());
    }

    private function recoveryRegistryDigest(): string
    {
        $registry = config('staff-recovery.operators', []);
        if (! is_array($registry) || $registry === []) {
            throw new IdentityViolation('STAFF_RECOVERY_NOT_ENROLLED', 403);
        }
        ksort($registry);

        return hash('sha256', json_encode($registry, JSON_THROW_ON_ERROR));
    }

    /** @return array<string, mixed> */
    private function recoveryOperator(int $operatorId, #[\SensitiveParameter] string $password, #[\SensitiveParameter] string $code): array
    {
        $operator = $this->recoveryOperatorBinding($operatorId);
        $user = User::query()->findOrFail($operatorId);
        if (! Hash::check($password, $user->password)) {
            throw new IdentityViolation('STAFF_RECOVERY_AUTHENTICATION_REQUIRED', 403);
        }
        $this->authenticator->handle($operatorId, $code);

        return $operator;
    }

    /** @return array<string, mixed> */
    private function recoveryOperatorBinding(int $operatorId): array
    {
        $this->recoveryRegistryDigest();
        $expected = config('staff-recovery.operators.'.$operatorId);
        $user = User::query()->find($operatorId);
        if (! is_string($expected) || ! preg_match('/^[a-f0-9]{64}$/D', $expected) || $user === null) {
            throw new IdentityViolation('STAFF_RECOVERY_NOT_ENROLLED', 403);
        }
        $person = $this->recoveryPerson($user);
        if (! hash_equals($expected, $person['digest'])) {
            throw new IdentityViolation('STAFF_RECOVERY_NOT_ENROLLED', 403);
        }

        return ['user_id' => $operatorId, 'person' => $person, 'credentials' => $this->authenticator->binding($operatorId),
            'email' => $user->email, 'email_verified_at' => $user->email_verified_at?->toIso8601String()];
    }

    /** @return array{id: string, revision: int, digest: string} */
    private function recoveryPerson(User $user): array
    {
        if ($user->party_id !== null || ! $this->emailVerified($user) || ! $this->mfaConfirmed($user)
            || ! StaffAccount::query()->whereKey($user->id)->exists()) {
            throw new IdentityViolation('STAFF_RECOVERY_ACCOUNT_INELIGIBLE', 409);
        }
        $person = StaffPersonIdentity::query()->where('staff_user_id', $user->id)->orderByDesc('revision')->first();
        if ($person === null || $person->status !== 'active' || $person->identity_digest === null || $person->created_at->isFuture()) {
            throw new IdentityViolation('STAFF_RECOVERY_PERSON_REQUIRED', 409);
        }

        return ['id' => $person->id, 'revision' => $person->revision, 'digest' => $person->identity_digest];
    }

    /** @param list<int> $recipients
     * @param  list<int>  $holders
     * @return array<string, mixed>
     */
    private function recoveryState(string $kind, array $recipients, array $holders): array
    {
        $managers = [];
        $effective = [];
        foreach ($holders as $id) {
            $user = User::query()->findOrFail($id);
            $managers[] = ['user_id' => $id, 'state' => $this->staffBaseline($user, StaffAccount::query()->find($id)),
                'credentials' => hash('sha256', $user->password.'|'.$user->two_factor_secret.'|'.$user->two_factor_confirmed_at?->toIso8601String()),
                'email' => $user->email, 'email_verified_at' => $user->email_verified_at?->toIso8601String()];
            if (in_array('staff.manage', $this->staffAccess($id, lock: false)['allowed_actions'], true)) {
                $effective[] = $id;
            }
        }
        $historical = $holders !== [] || IdentityAuditEvent::query()->where('action', 'staff.configure')
            ->whereJsonContains('after->roles', 'superadmin')->exists()
            || IdentityAuditEvent::query()->where('action', 'staff.recovery.complete')->exists();
        if (count($effective) >= 2 || ($kind === 'genesis' && $historical) || ($kind === 'recovery' && ! $historical)) {
            throw new IdentityViolation('STAFF_RECOVERY_NOT_REQUIRED', 409);
        }
        if (count($effective) === 1 && ! in_array($effective[0], $recipients, true)) {
            throw new IdentityViolation('STAFF_RECOVERY_EXISTING_MANAGER_REQUIRED', 409);
        }
        $targets = [];
        foreach ($recipients as $id) {
            $user = User::query()->find($id);
            if ($user === null) {
                throw new IdentityViolation('STAFF_RECOVERY_ACCOUNT_INELIGIBLE', 409);
            }
            $targets[] = ['user_id' => $id, 'person' => $this->recoveryPerson($user),
                'state' => $this->staffBaseline($user, StaffAccount::query()->find($id)),
                'credentials' => $this->authenticator->binding($id), 'email' => $user->email,
                'email_verified_at' => $user->email_verified_at?->toIso8601String()];
        }
        if (count(array_unique(array_column(array_column($targets, 'person'), 'digest'))) !== 2) {
            throw new IdentityViolation('STAFF_SELF_APPROVAL_FORBIDDEN', 409);
        }

        return ['managers' => $managers, 'effective_managers' => $effective, 'recipients' => $targets];
    }

    private function requireStaffRecipient(User $user): void
    {
        if (! $this->emailVerified($user) || ! $this->mfaConfirmed($user)) {
            throw new IdentityViolation('STAFF_VERIFIED_EMAIL_AND_MFA_REQUIRED');
        }
        $this->requireDedicated($user, $this->lockParty($user->party_id));
    }

    /** @return array<string, mixed> */
    private function staffBaseline(User $user, ?StaffAccount $staff): array
    {
        return ['staff_exists' => $staff !== null, 'enabled' => $staff->enabled ?? false, 'party_id' => $user->party_id,
            'roles' => $staff->roles ?? [], 'context_revision' => $user->context_revision,
            'access_event_id' => IdentityAuditEvent::query()->where('action', 'staff.configure')->where('target_type', 'user')
                ->where('target_id', (string) $user->id)->orderByDesc('created_at')->orderByDesc('id')->value('id')];
    }

    /** @return array<string, mixed> */
    private function requestSuperadmin(int $actorId, User $user, ?StaffAccount $staff, string $operation, string $reason, string $requestId, string $hash): array
    {
        $proposalId = strtolower((string) Str::ulid());
        $before = $this->staffBaseline($user, $staff);
        $result = ['code' => 'STAFF_SUPERADMIN_APPROVAL_REQUIRED', 'user_id' => $user->id, 'proposal_id' => $proposalId];
        $this->record('user:'.$actorId, $actorId, 'user', (string) $user->id, 'staff.superadmin.request', $reason, $requestId, $hash,
            $before, [...$before, 'operation' => $operation, 'requested_roles' => $operation === 'grant' ? ['superadmin'] : $staff->roles,
                'approval_policy_version' => self::STAFF_APPROVAL_POLICY], $result, $proposalId);

        return $result;
    }

    /**
     * @param  list<string>  $roles
     * @param  array<string, mixed>  $approval
     * @return array<string, mixed>
     */
    private function enableStaff(int $actorId, User $user, ?StaffAccount $staff, array $roles, string $reason, string $requestId, string $hash, array $approval = []): array
    {
        $before = ['enabled' => $staff->enabled ?? false, 'party_id' => $user->party_id, 'roles' => $staff->roles ?? []];
        $new = $staff === null;
        $staff ??= new StaffAccount;
        $staff->forceFill(['user_id' => $user->id, 'enabled' => true, 'roles' => $roles])->save();
        if ($new) {
            $user->forceFill(['party_id' => null, 'active_membership_id' => null, 'active_membership_revision' => null,
                'context_revision' => $user->context_revision + 1])->save();
        }
        $result = ['code' => 'STAFF_ACCESS_ENABLED', 'user_id' => $user->id];
        $this->record('user:'.$actorId, $actorId, 'user', (string) $user->id, 'staff.configure', $reason, $requestId, $hash,
            $before, ['enabled' => true, 'party_id' => $user->party_id, 'roles' => $roles, ...$approval], $result);

        return $result;
    }

    /** @return array<string, mixed> */
    public function resolvePerson(int $actorId, int $userId, string $identityReference, string $evidenceReference, string $reason, string $requestId): array
    {
        return DB::transaction(function () use ($actorId, $userId, $identityReference, $evidenceReference, $reason, $requestId): array {
            $users = User::query()->whereKey([$actorId, $userId])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $this->authorizeOperator($users->get($actorId));

            return $this->resolveVerifiedPerson($actorId, $users->get($userId), $identityReference, $evidenceReference, $reason, $requestId, 'person.resolve');
        }, 3);
    }

    /**
     * @param  Closure(string): array<string, mixed>  $approve
     * @return array<string, mixed>
     */
    public function verifyInvestor(int $actorId, int $userId, string $identityReference, string $evidenceReference, string $reason, string $requestId, Closure $approve): array
    {
        return DB::transaction(function () use ($actorId, $userId, $identityReference, $evidenceReference, $reason, $requestId, $approve): array {
            // Both accounts in ascending ID first; the reviewer's current permission is then read under that lock.
            $users = User::query()->whereKey([$actorId, $userId])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $this->requireStaffPermission($actorId, 'investors.verify');

            return $this->resolveVerifiedPerson($actorId, $users->get($userId), $identityReference, $evidenceReference, $reason, $requestId, 'investor.verify',
                function (Party $party) use ($approve): array {
                    [$membership, , $revision] = $this->transitionMembership($party, 'investor', 'active', 0);

                    return ['membership' => ['id' => $membership->id, 'role' => 'investor', 'status' => 'active', 'revision' => $revision],
                        'verification' => $approve($party->id)];
                });
        }, 3);
    }

    /**
     * The one verified-person writer, after the caller has locked the actor and the account. Lock order
     * continues identity advisory lock, then parties; `$approve` runs last, in the same transaction, and
     * only for a person no other Party has been verified as: staff approval never relinks an account.
     *
     * @param  (Closure(Party): array<string, mixed>)|null  $approve
     * @return array<string, mixed>
     */
    private function resolveVerifiedPerson(int $actorId, ?User $user, string $identityReference, string $evidenceReference, string $reason, string $requestId, string $action, ?Closure $approve = null): array
    {
        if ($user === null) {
            throw new IdentityViolation('IDENTITY_RECORD_NOT_FOUND', 404);
        }

        $this->evidenceRequired($evidenceReference);
        if (mb_strlen($identityReference) > 255 || ! preg_match('/^[a-z0-9._-]+:[A-Za-z0-9._-]{1,128}$/D', $identityReference)) {
            throw new IdentityViolation('IDENTITY_REFERENCE_INVALID', 422);
        }

        $digest = hash('sha256', $identityReference);
        $hash = $this->requestHash([$action, $user->id, $digest, $evidenceReference, $reason], $reason, $requestId);
        if (($replay = $this->replay('user:'.$actorId, $requestId, $hash)) !== null) {
            return $replay;
        }

        if (! $this->emailVerified($user) || IdentityOperator::query()->whereKey($user->id)->exists()
            || StaffAccount::query()->whereKey($user->id)->exists()) {
            throw new IdentityViolation('VERIFIED_PARTICIPANT_ACCOUNT_REQUIRED');
        }

        DB::select('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))', ['identity:'.$digest]);
        $identity = VerifiedPersonIdentity::query()->find($digest);
        $partyIds = array_filter([$user->party_id, $identity?->party_id]);
        $parties = Party::query()->whereKey($partyIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        $source = $parties->get($user->party_id);
        $party = $identity === null ? $source : $parties->get($identity->party_id);

        // A staff approval of an ID already verified for someone else is a duplicate, not an operator's reconciliation.
        if ($approve !== null && $identity !== null && $identity->party_id !== $user->party_id) {
            throw new IdentityViolation('IDENTITY_VERIFIED_FOR_ANOTHER_PERSON', 409);
        }

        if (($approve !== null && $identity !== null) || ($source !== null && ($source->kind !== 'person'
            || ($source->id !== $identity?->party_id && ($source->memberships()->exists() || $source->verifiedIdentity()->exists()))))) {
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
        $facts = $approve === null ? [] : $approve($party);
        $result = ['code' => $approve === null ? 'VERIFIED_PERSON_RESOLVED' : 'INVESTOR_VERIFIED', 'user_id' => $user->id, 'party_id' => $party->id, ...$facts];
        $this->record('user:'.$actorId, $actorId, 'user', (string) $user->id, $action, $reason, $requestId, $hash,
            $before, ['party_id' => $party->id, 'evidence_reference' => $evidenceReference, 'identity_digest' => $digest, ...$facts], $result);

        return $result;
    }

    /** @return array<string, mixed> */
    public function recordStaffPerson(int $actorId, int $staffUserId, ?string $identityReference, string $evidenceReference, string $reason, string $requestId): array
    {
        return DB::transaction(function () use ($actorId, $staffUserId, $identityReference, $evidenceReference, $reason, $requestId): array {
            $users = User::query()->whereKey([$actorId, $staffUserId])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $this->authorizeOperator($users->get($actorId));
            if ($users->get($staffUserId) === null || ! StaffAccount::query()->whereKey($staffUserId)->exists()) {
                throw new IdentityViolation('STAFF_ACCOUNT_NOT_FOUND', 404);
            }
            $this->evidenceRequired($evidenceReference);
            if ($identityReference !== null && (mb_strlen($identityReference) > 255
                || ! preg_match('/^[a-z0-9._-]+:[A-Za-z0-9._-]{1,128}$/D', $identityReference))) {
                throw new IdentityViolation('IDENTITY_REFERENCE_INVALID', 422);
            }

            $digest = $identityReference === null ? null : hash('sha256', $identityReference);
            $hash = $this->requestHash(['staff.person.record', $staffUserId, $digest, $evidenceReference, $reason], $reason, $requestId);
            if (($replay = $this->replay('user:'.$actorId, $requestId, $hash)) !== null) {
                return $replay;
            }

            $current = StaffPersonIdentity::query()->where('staff_user_id', $staffUserId)->orderByDesc('revision')->first();
            if ($digest === null && $current?->status !== 'active') {
                throw new IdentityViolation('STAFF_PERSON_NOT_RESOLVED', 409);
            }
            $revision = ($current->revision ?? 0) + 1;
            $status = $digest === null ? 'revoked' : 'active';
            (new StaffPersonIdentity)->forceFill(['staff_user_id' => $staffUserId, 'revision' => $revision, 'status' => $status,
                'identity_digest' => $digest, 'evidence_reference' => $evidenceReference, 'recorded_by' => $actorId, 'created_at' => now()])->save();

            $result = ['code' => $digest === null ? 'STAFF_PERSON_REVOKED' : 'STAFF_PERSON_RECORDED', 'user_id' => $staffUserId];
            $this->record('user:'.$actorId, $actorId, 'user', (string) $staffUserId, 'staff.person.record', $reason, $requestId, $hash,
                ['revision' => $current?->revision, 'status' => $current?->status],
                ['revision' => $revision, 'status' => $status, 'evidence_reference' => $evidenceReference, 'identity_digest' => $digest], $result);

            return $result;
        }, 3);
    }

    public function staffPerson(int $staffUserId): ?array
    {
        $current = StaffPersonIdentity::query()->where('staff_user_id', $staffUserId)->orderByDesc('revision')->first();
        if ($current === null || $current->identity_digest === null) {
            return null;
        }

        return ['resolution_id' => $current->id, 'party_id' => VerifiedPersonIdentity::query()->find($current->identity_digest)?->party_id];
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

            [$membership, $before, $revision] = $this->transitionMembership($party, $role, $status, $expectedRevision);
            $result = ['code' => 'MEMBERSHIP_UPDATED', 'party_id' => $partyId, 'membership' => [
                'id' => $membership->id, 'role' => $role, 'status' => $status, 'revision' => $revision,
            ]];
            $this->record('user:'.$actorId, $actorId, 'membership', $membership->id, 'membership.change', $reason, $requestId, $hash,
                $before, ['status' => $status, 'revision' => $revision, 'evidence_reference' => $evidenceReference], $result);

            return $result;
        }, 3);
    }

    /**
     * The membership-transition core, for a Party already locked by the caller.
     *
     * @return array{RoleMembership, array<string, mixed>, int}
     */
    private function transitionMembership(Party $party, string $role, string $status, int $expectedRevision): array
    {
        $memberships = $party->memberships()->get();
        $revision = $this->transitions->nextRevision($role, $status, $expectedRevision,
            array_values($memberships->map(fn (RoleMembership $membership): array => [
                'role' => $membership->role, 'status' => $membership->status, 'revision' => $membership->revision,
            ])->all()));
        $membership = $memberships->firstWhere('role', $role);
        $before = $membership === null ? [] : ['status' => $membership->status, 'revision' => $membership->revision];
        $membership ??= new RoleMembership;
        $membership->forceFill(['party_id' => $party->id, 'role' => $role, 'status' => $status, 'revision' => $revision])->save();

        return [$membership, $before, $revision];
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
    public function staffAccess(int $userId, bool $required = false, bool $lock = true): array
    {
        return DB::transaction(function () use ($userId, $required, $lock): array {
            $query = User::query();
            if ($lock) {
                $query->lockForUpdate();
            }
            $user = $query->findOrFail($userId);
            $staff = StaffAccount::query()->find($userId);
            $allowed = $user->party_id === null && $this->emailVerified($user) && $this->mfaConfirmed($user)
                && $staff !== null && $staff->enabled;
            if ($required && ! $allowed) {
                throw new IdentityViolation('STAFF_ACCESS_REQUIRED');
            }

            return ['contract_version' => 'staff-access-v1', 'can_open_admin' => $allowed,
                'allowed_actions' => $allowed ? StaffPermission::forRoles($staff->roles) : [], 'roles' => $allowed ? $staff->roles : []];
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
            $this->requireStaffPermission($userId, $permission);

            return $operation();
        }, 3);
    }

    /** Locks the staff account (a no-op when the caller already holds it in its own order) and checks the permission. */
    private function requireStaffPermission(int $userId, string $permission): void
    {
        if (! in_array($permission, $this->staffAccess($userId, true)['allowed_actions'], true)) {
            throw new IdentityViolation('STAFF_PERMISSION_REQUIRED');
        }
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
     * @param  list<string>  $additionalPartyIds
     * @return TResult
     */
    public function withEntityRole(int $userId, string $role, int $expectedContext, string $entityKind, string $entityPartyId, array $personPartyIds, Closure $operation, ?string $registryReference = null, array $additionalPartyIds = []): mixed
    {
        return DB::transaction(function () use ($userId, $role, $expectedContext, $entityKind, $entityPartyId, $personPartyIds, $operation, $registryReference, $additionalPartyIds): mixed {
            $user = User::query()->lockForUpdate()->findOrFail($userId);
            if ($user->party_id === null || ! in_array($user->party_id, $personPartyIds, true)) {
                throw new IdentityViolation('MANDATE_REQUIRED');
            }
            if ($additionalPartyIds !== []) {
                $ids = array_values(array_unique([$entityPartyId, ...$personPartyIds, ...$additionalPartyIds]));
                Party::query()->whereKey($ids)->orderBy('id')->lockForUpdate()->get();
            }

            return $this->withVerifiedParties($entityKind, $entityPartyId, $personPartyIds, function () use ($userId, $role, $expectedContext, $operation): mixed {
                $identity = $this->identities->forUser($userId);
                $this->roles->authorize($identity, $role, null, $expectedContext);

                return $operation($identity);
            }, $registryReference);
        }, 3);
    }

    /**
     * Locks all required and optional Parties once, in stable order. An ineligible candidate is
     * excluded without making another candidate unavailable. Participant authority is separate
     * from Business membership; assignment ownership remains the Auditor adapter's responsibility.
     *
     * @template TResult
     *
     * @param  list<string>  $personPartyIds
     * @param  list<string>  $candidateIds
     * @param  Closure(list<string>, string|null, bool): TResult  $operation
     * @return TResult
     */
    public function withAuditAccess(?int $userId, ?int $contextRevision, string $entityKind, string $entityPartyId, array $personPartyIds, array $candidateIds, bool $requireVerified, Closure $operation, ?string $registryReference = null): mixed
    {
        return DB::transaction(function () use ($userId, $contextRevision, $entityKind, $entityPartyId, $personPartyIds, $candidateIds, $requireVerified, $operation, $registryReference): mixed {
            $user = $userId === null ? null : User::query()->lockForUpdate()->findOrFail($userId);
            if ($userId === null && $contextRevision !== null) {
                throw new IdentityViolation('ACTIVE_ROLE_REQUIRED');
            }
            if ($userId !== null && $contextRevision === null) {
                $this->withStaffPermission($userId, 'audit.assignments.manage', fn (): bool => true);
            }
            $ids = array_values(array_unique([$entityPartyId, ...$personPartyIds, ...$candidateIds, ...($user?->party_id === null ? [] : [$user->party_id])]));
            $parties = Party::query()->whereKey($ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if ($contextRevision !== null) {
                $this->roles->authorize($this->identities->forUser($userId), 'auditor', null, $contextRevision);
            }
            $verified = true;
            try {
                $this->withVerifiedParties($entityKind, $entityPartyId, $personPartyIds, fn (): bool => true, $registryReference);
            } catch (IdentityViolation $exception) {
                if ($requireVerified) {
                    throw $exception;
                }
                $verified = false;
            }
            $verifiedPeople = VerifiedPersonIdentity::query()->whereIn('party_id', $candidateIds)->pluck('party_id')->all();
            $memberships = RoleMembership::query()->whereIn('party_id', $candidateIds)->where('status', 'active')->get()->groupBy('party_id');
            $eligible = [];
            foreach (array_unique($candidateIds) as $id) {
                $party = $parties->get($id);
                if ($party !== null && $party->kind === 'person' && $party->verified_at !== null && $party->verified_at->lte(now())
                    && in_array($id, $verifiedPeople, true) && $memberships->has($id)
                    && $memberships[$id]->contains('role', 'auditor') && $memberships[$id]->every(fn (RoleMembership $membership): bool => $membership->role === 'auditor')) {
                    $eligible[] = $id;
                }
            }

            return $operation($eligible, $user?->party_id, $verified);
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

    /**
     * Staff access goes only to a dedicated account: one with no party, or a lone unverified person
     * party with no memberships or verified identity.
     */
    private function requireDedicated(User $user, ?Party $party): void
    {
        if ($party !== null && ($party->kind !== 'person' || $party->verified_at !== null
            || $party->memberships()->exists() || $party->verifiedIdentity()->exists()
            || User::query()->where('party_id', $party->id)->whereKeyNot($user->id)->exists())) {
            throw new IdentityViolation('DEDICATED_STAFF_ACCOUNT_REQUIRED');
        }
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
    private function record(string $actorKey, ?int $actorId, string $targetType, string $targetId, string $action, string $reason, string $requestId, string $hash, array $before, array $after, array $result, ?string $id = null): void
    {
        (new IdentityAuditEvent)->forceFill([
            'id' => $id ?? strtolower((string) Str::ulid()), 'actor_key' => $actorKey, 'actor_user_id' => $actorId, 'target_type' => $targetType, 'target_id' => $targetId,
            'action' => $action, 'reason' => $reason, 'request_id' => $requestId, 'request_hash' => $hash,
            'before' => $before, 'after' => $after, 'result' => $result, 'policy_version' => ActiveRolePolicy::POLICY_VERSION,
        ])->save();
    }
}
