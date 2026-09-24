<?php

declare(strict_types=1);

namespace Tests\Support;

/** @phpstan-import-type Candidate from \App\Domain\Auditor\AuditorDispatch */
final class AuditorFixture
{
    /** @return Candidate */
    public static function candidate(string $id = 'partner-a'): array
    {
        $location = ['verified_at' => '2026-09-24T08:00:00Z', 'moved_at' => null, 'uncertainty_m' => 50];

        return ['id' => $id, 'standing' => ['status' => 'active', 'licence' => 'SYNTHETIC-CPA', 'expires_on' => '2028-12-31',
            'checked_at' => '2026-09-24T08:00:00Z', 'check_reference' => 'synthetic-register-check'],
            'accepting' => true, 'active_count' => 0, 'consecutive_reports' => 0, 'last_assigned_at' => null,
            'office' => $location, 'premises' => $location, 'distance_upper_bound_m' => 29900,
            'financial_interest' => false, 'current_role_tie' => false, 'role_tie_ended_at' => null,
            'family_or_business_conflict' => false, 'unresolved_conflict' => false];
    }
}
