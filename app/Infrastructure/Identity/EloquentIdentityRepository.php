<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity;

use App\Application\Identity\Contracts\IdentityRepository;
use App\Domain\Identity\SandboxNotice;
use App\Models\Party;
use App\Models\RoleMembership;
use App\Models\SandboxNoticeAcknowledgement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Fortify\Features;

/** @phpstan-import-type AccessSnapshot from \App\Domain\Identity\ActiveRolePolicy */
final class EloquentIdentityRepository implements IdentityRepository
{
    public function accountName(int $userId): string
    {
        return User::query()->findOrFail($userId)->name;
    }

    public function register(string $name, string $email, string $password): int
    {
        return DB::transaction(function () use ($name, $email, $password): int {
            $party = Party::query()->create(['kind' => 'person']);
            $user = new User(['name' => $name, 'email' => $email, 'password' => $password]);
            $user->party()->associate($party);
            $user->save();
            $this->acknowledgeSandboxNotice($party->id);

            return $user->id;
        });
    }

    /** @return AccessSnapshot */
    public function forUser(int $userId): array
    {
        $user = User::query()->with(['party.memberships', 'party.verifiedIdentity'])->findOrFail($userId);
        $party = $user->party;

        return [
            'email_verified' => $user->email_verified_at !== null && $user->email_verified_at->lte(now()),
            'mfa_confirmed' => Features::enabled(Features::twoFactorAuthentication()) && $user->two_factor_secret !== null
                && $user->two_factor_confirmed_at !== null && $user->two_factor_confirmed_at->lte(now()),
            'active_membership_id' => $user->active_membership_id,
            'active_membership_revision' => $user->active_membership_revision,
            'context_revision' => $user->context_revision,
            'party' => $party === null ? null : [
                'id' => $party->id,
                'kind' => $party->kind,
                'verified' => $party->verified_at !== null && $party->verified_at->lte(now())
                    && ($party->kind !== 'person' || $party->verifiedIdentity !== null),
            ],
            'memberships' => $party === null ? [] : array_values($party->memberships
                ->map(fn (RoleMembership $membership): array => [
                    'id' => $membership->id,
                    'role' => $membership->role,
                    'status' => $membership->status,
                    'revision' => $membership->revision,
                ])->all()),
        ];
    }

    public function auditorPartyIsActive(string $partyId): bool
    {
        return Party::query()->whereKey($partyId)->where('kind', 'person')->where('verified_at', '<=', now())
            ->has('verifiedIdentity')
            ->whereIn('id', RoleMembership::query()->select('party_id')->where('role', 'auditor')->where('status', 'active'))
            ->whereNotIn('id', RoleMembership::query()->select('party_id')->where('role', '<>', 'auditor')->where('status', 'active'))
            ->exists();
    }

    public function partyKinds(array $partyIds): array
    {
        /** @var array<string, 'person'|'organization'> */
        return Party::query()->whereKey($partyIds)->pluck('kind', 'id')->all();
    }

    private function acknowledgeSandboxNotice(string $partyId): void
    {
        $at = now('UTC')->toDateTimeImmutable();
        $id = strtolower((string) Str::ulid());
        $digest = hash('sha256', implode("\n", ['sandbox-notice-acknowledgement-1', $id, $partyId,
            SandboxNotice::VERSION, SandboxNotice::sha256(), $at->format('Y-m-d\TH:i:s.u\Z')]));
        (new SandboxNoticeAcknowledgement)->forceFill(['id' => $id, 'party_id' => $partyId, 'version' => SandboxNotice::VERSION,
            'notice_sha256' => SandboxNotice::sha256(), 'sha256' => $digest, 'acknowledged_at' => $at])->save();
    }
}
