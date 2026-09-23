<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity;

use App\Application\Identity\Contracts\IdentityRepository;
use App\Models\Party;
use App\Models\RoleMembership;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\Features;

/** @phpstan-import-type AccessSnapshot from \App\Domain\Identity\ActiveRolePolicy */
final class EloquentIdentityRepository implements IdentityRepository
{
    public function register(string $name, string $email, string $password): int
    {
        return DB::transaction(function () use ($name, $email, $password): int {
            $party = Party::query()->create(['kind' => 'person']);
            $user = new User(['name' => $name, 'email' => $email, 'password' => $password]);
            $user->party()->associate($party);
            $user->save();

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
}
