<?php

declare(strict_types=1);

namespace App\Infrastructure\Auditor;

use App\Application\Auditor\Contracts\AuditCoverage;
use App\Application\Auditor\Contracts\AuditEngagementStore;
use App\Domain\Auditor\AuditorDispatch;
use App\Domain\Auditor\VerifiedAuditLocation;
use App\Domain\Auditor\Wgs84Distance;
use App\Models\AuditAssignment;
use App\Models\AuditLocation;
use App\Models\AuditorProfile;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Coverage from the same facts dispatch reads: the partners' profiles, offices and engagement
 * acceptances, and each Business's premises, run through `AuditorDispatch::reasons` with no
 * Business-specific conflict or rotation.
 */
final class EloquentAuditCoverage implements AuditCoverage
{
    public function __construct(private AuditEngagementStore $engagements, private AuditorDispatch $dispatch,
        private VerifiedAuditLocation $locations, private Wgs84Distance $distance) {}

    public function districts(DateTimeImmutable $now): array
    {
        $businesses = DB::table('business_profiles')->orderBy('id')->selectRaw("id, profile->>'district' AS district")->get();
        if ($businesses->isEmpty()) {
            return [];
        }
        $profiles = AuditorProfile::query()->orderBy('party_id')->get();
        $ids = array_values($profiles->map(fn (AuditorProfile $profile): string => $profile->party_id)->all());
        $acceptances = $this->engagements->withCurrentAcceptances($ids, fn (array $acceptances): array => $acceptances);
        $offices = AuditLocation::query()->whereIn('office_party_id', $ids)->get()->keyBy('office_party_id');
        $premises = AuditLocation::query()->whereIn('business_id', $businesses->pluck('id'))->get()->keyBy('business_id');
        $active = AuditAssignment::query()->whereIn('party_id', $ids)->where('status', 'accepted')->get(['party_id'])->countBy('party_id');
        $open = AuditAssignment::query()->whereIn('business_id', $businesses->pluck('id'))->whereIn('status', ['offered', 'accepted', 'operations'])
            ->get(['business_id'])->countBy('business_id');

        $reached = [];
        foreach ($businesses as $business) {
            $site = $premises->get((string) $business->id)->state ?? $this->locations->empty();
            $partners = [];
            foreach ($profiles as $profile) {
                $office = $offices->get($profile->party_id)->state ?? $this->locations->empty();
                $candidate = ['id' => $profile->party_id, 'standing' => $profile->state['standing'], 'engagement' => $acceptances[$profile->party_id] ?? null,
                    'accepting' => $profile->state['accepting'], 'active_count' => (int) $active->get($profile->party_id, 0), 'consecutive_reports' => 0,
                    'last_assigned_at' => null, 'office' => $office, 'premises' => $site,
                    'distance_upper_bound_m' => $office['point'] === null || $site['point'] === null ? null : $this->distance->upperBound($office['point'], $site['point']),
                    'financial_interest' => false, 'current_role_tie' => false, 'role_tie_ended_at' => null, 'family_or_business_conflict' => false, 'unresolved_conflict' => false];
                if ($this->dispatch->reasons($candidate, $now) === []) {
                    $partners[] = $profile->party_id;
                }
            }
            $reached[(string) $business->district][(string) $business->id] = $partners;
        }

        $districts = [];
        /** @var array<string, list<string>> $provinces */
        $provinces = config('rwanda.districts');
        foreach ($provinces as $province => $names) {
            foreach ($names as $district) {
                if (! isset($reached[$district])) {
                    continue;
                }
                $partners = array_unique(array_merge(...array_values($reached[$district])));
                $districts[] = ['district' => $district, 'province' => $province, 'businesses' => count($reached[$district]),
                    'active_partners' => count($partners), 'audits_open' => array_sum(array_map(fn (string $id): int => (int) $open->get($id, 0), array_keys($reached[$district]))),
                    'covered' => array_filter($reached[$district], fn (array $partners): bool => $partners === []) === []];
            }
        }

        return $districts;
    }
}
