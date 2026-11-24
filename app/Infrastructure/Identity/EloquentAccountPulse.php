<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity;

use App\Application\Identity\Contracts\AccountPulse;
use Illuminate\Support\Facades\DB;

/**
 * One indexed read per source, by the account's Party: the account itself, its Party's verification,
 * its identity check, its role application, its roles, its access requests and its newest notice.
 */
final class EloquentAccountPulse implements AccountPulse
{
    public function version(int $userId): string
    {
        $account = DB::table('users')->where('id', $userId)->first(['party_id', 'context_revision', 'updated_at', 'email_verified_at', 'two_factor_confirmed_at']);
        if ($account === null) {
            return hash('sha256', 'none');
        }
        $partyId = $account->party_id;
        $facts = [$account->context_revision, $account->updated_at, $account->email_verified_at, $account->two_factor_confirmed_at];
        if (is_string($partyId)) {
            $facts[] = DB::table('parties')->where('id', $partyId)->value('verified_at');
            $facts[] = DB::table('investor_verifications')->where('party_id', $partyId)->get(['revision', 'status'])->toJson();
            $facts[] = DB::table('role_applications')->where('party_id', $partyId)->get(['revision', 'status'])->toJson();
            $facts[] = DB::table('role_memberships')->where('party_id', $partyId)->orderBy('id')->get(['role', 'status', 'updated_at'])->toJson();
            $facts[] = DB::table('role_access_requests')->where('party_id', $partyId)->count();
            $facts[] = DB::table('party_notices')->where('party_id', $partyId)->max('created_at');
        }

        return hash('sha256', (string) json_encode($facts));
    }
}
