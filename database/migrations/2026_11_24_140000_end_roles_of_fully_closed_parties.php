<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Accounts closed before #755 kept their Party's roles, so a closed account could still show as an
 * active Auditor (QA #746). Closing now ends them; this ends them for Parties whose every account was
 * already closed. Memberships are revoked, never deleted, and nothing else changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('role_memberships')->where('status', '!=', 'revoked')
            ->whereExists(fn ($users) => $users->selectRaw('1')->from('users')->whereColumn('users.party_id', 'role_memberships.party_id'))
            ->whereNotExists(fn ($open) => $open->selectRaw('1')->from('users')->whereColumn('users.party_id', 'role_memberships.party_id')
                ->whereNull('users.closed_at'))
            ->update(['status' => 'revoked', 'revision' => DB::raw('revision + 1'), 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Revoked memberships are not restored: a closed account must not regain a role.
    }
};
