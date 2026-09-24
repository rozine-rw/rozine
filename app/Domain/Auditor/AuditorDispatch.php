<?php

declare(strict_types=1);

namespace App\Domain\Auditor;

use App\Domain\Operations\CommandRejection;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Inputs are server-resolved records under the assignment transaction, never request assertions.
 * Distance is a conservative geodesic upper bound in whole metres, not a rounded UI distance.
 *
 * @phpstan-import-type Standing from AuditorStanding
 *
 * @phpstan-type Location array{verified_at: string|null, moved_at: string|null, uncertainty_m: int|null}
 * @phpstan-type Candidate array{id: string, standing: Standing, accepting: bool, active_count: int, consecutive_reports: int, last_assigned_at: string|null, office: Location, premises: Location, distance_upper_bound_m: int|null, financial_interest: bool, current_role_tie: bool, role_tie_ended_at: string|null, family_or_business_conflict: bool, unresolved_conflict: bool}
 */
final class AuditorDispatch
{
    public function __construct(private AuditorStanding $standing) {}

    /**
     * @param  Candidate  $candidate
     * @return list<string>
     */
    public function reasons(array $candidate, DateTimeImmutable $now): array
    {
        $now = $now->setTimezone(new DateTimeZone('UTC'));
        $reasons = [];
        try {
            $this->standing->requireCurrent($candidate['standing'], $now);
        } catch (CommandRejection $rejection) {
            $reasons[] = $rejection->reason;
        }
        $assigned = $this->timestamp($candidate['last_assigned_at']);
        if ($candidate['id'] === '' || $candidate['active_count'] < 0 || $candidate['consecutive_reports'] < 0
            || ($candidate['last_assigned_at'] !== null && ($assigned === null || $assigned > $now))) {
            $reasons[] = 'AUDITOR_FACTS_INVALID';
        }
        if (! $candidate['accepting']) {
            $reasons[] = 'AUDITOR_UNAVAILABLE';
        }
        if ($candidate['active_count'] >= 3) {
            $reasons[] = 'AUDITOR_CAPACITY_REACHED';
        }
        if ($candidate['consecutive_reports'] >= 3) {
            $reasons[] = 'AUDITOR_ROTATION_REQUIRED';
        }
        $tie = $this->timestamp($candidate['role_tie_ended_at']);
        $cutoffMonth = $now->modify('first day of this month')->modify('-24 months');
        $cutoff = $cutoffMonth->setDate((int) $cutoffMonth->format('Y'), (int) $cutoffMonth->format('m'), min((int) $now->format('d'), (int) $cutoffMonth->format('t')));
        if ($candidate['financial_interest'] || $candidate['current_role_tie'] || $candidate['family_or_business_conflict'] || $candidate['unresolved_conflict']
            || ($candidate['role_tie_ended_at'] !== null && ($tie === null || $tie >= $cutoff))) {
            $reasons[] = 'AUDITOR_CONFLICT';
        }
        if (! $this->locationCurrent($candidate['office'], $now) || ! $this->locationCurrent($candidate['premises'], $now)) {
            $reasons[] = 'AUDITOR_LOCATION_REVIEW_REQUIRED';
        } elseif ($candidate['distance_upper_bound_m'] === null || $candidate['distance_upper_bound_m'] < 0
            || $candidate['distance_upper_bound_m'] > 30000
            || $candidate['distance_upper_bound_m'] + $candidate['office']['uncertainty_m'] + $candidate['premises']['uncertainty_m'] > 30000) {
            $reasons[] = 'AUDITOR_OUTSIDE_RADIUS';
        }

        return $reasons;
    }

    /**
     * No candidate means Audit Operations must resolve the queue; no conflict or radius is waived.
     *
     * @param  list<Candidate>  $candidates
     */
    public function select(array $candidates, DateTimeImmutable $now): ?string
    {
        $seen = $eligible = [];
        foreach ($candidates as $candidate) {
            if (isset($seen[$candidate['id']])) {
                throw new CommandRejection('AUDITOR_CANDIDATE_CONFLICT', 422);
            }
            $seen[$candidate['id']] = true;
            if ($this->reasons($candidate, $now) === []) {
                $eligible[] = $candidate;
            }
        }
        usort($eligible, fn (array $left, array $right): int => ($left['active_count'] <=> $right['active_count'])
            ?: strcmp($left['last_assigned_at'] ?? '', $right['last_assigned_at'] ?? '') ?: strcmp($left['id'], $right['id']));

        return $eligible[0]['id'] ?? null;
    }

    /** @param Location $location */
    private function locationCurrent(array $location, DateTimeImmutable $now): bool
    {
        $verified = $this->timestamp($location['verified_at']);
        $moved = $this->timestamp($location['moved_at']);

        return $location['uncertainty_m'] !== null && $location['uncertainty_m'] >= 0 && $location['uncertainty_m'] <= 30000
            && $verified !== null && $verified <= $now && $verified >= $now->modify('-365 days')
            && ($location['moved_at'] === null || ($moved !== null && $moved <= $verified));
    }

    private function timestamp(?string $value): ?DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s\Z', $value, new DateTimeZone('UTC'));

        return $date !== false && $date->format('Y-m-d\TH:i:s\Z') === $value ? $date : null;
    }
}
