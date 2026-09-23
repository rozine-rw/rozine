<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity;

use App\Application\Identity\Contracts\IdentityRepository;
use App\Models\Party;
use App\Models\RoleMembership;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** @phpstan-import-type IdentitySnapshot from IdentityRepository */
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

    /** @return IdentitySnapshot */
    public function forUser(int $userId): array
    {
        $user = User::query()->with('party.memberships')->findOrFail($userId);
        $party = $user->party;

        return [
            'email_verified' => $user->email_verified_at !== null && $user->email_verified_at->lte(now()),
            'party' => $party === null ? null : [
                'id' => $party->id,
                'kind' => $party->kind,
                'verified' => $party->verified_at !== null && $party->verified_at->lte(now()),
            ],
            'memberships' => $party === null ? [] : array_values($party->memberships
                ->map(fn (RoleMembership $membership): array => [
                    'role' => $membership->role,
                    'status' => $membership->status,
                ])->all()),
        ];
    }
}
