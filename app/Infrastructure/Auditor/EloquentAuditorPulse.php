<?php

declare(strict_types=1);

namespace App\Infrastructure\Auditor;

use App\Application\Auditor\Contracts\AuditorPulse;
use Illuminate\Support\Facades\DB;

/**
 * One indexed read per source, by the account's Party: the account's identity context, the
 * partner's profile revision, every assignment version naming them, their reports, their newest
 * notice and how many are unread.
 */
final class EloquentAuditorPulse implements AuditorPulse
{
    public function version(int $userId): string
    {
        $account = DB::table('users')->where('id', $userId)->first(['party_id', 'context_revision']);
        if ($account === null || ! is_string($account->party_id)) {
            return hash('sha256', 'none');
        }
        $partyId = $account->party_id;
        $facts = [$account->context_revision,
            DB::table('auditor_profiles')->where('party_id', $partyId)->value('revision'),
            DB::table('audit_assignment_versions')->where('party_id', $partyId)->count(),
            DB::table('audit_assignment_versions')->where('party_id', $partyId)->max('id'),
            DB::table('audit_assignments')->where('party_id', $partyId)->max('updated_at'),
            DB::table('audit_reports')->where('author_party_id', $partyId)->max('updated_at'),
            DB::table('party_notices')->where('party_id', $partyId)->max('created_at'),
            DB::table('party_notices')->where('party_id', $partyId)->whereNull('read_at')->count()];

        return hash('sha256', (string) json_encode($facts));
    }
}
