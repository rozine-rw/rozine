<?php

declare(strict_types=1);

namespace App\Domain\Auditor;

use App\Domain\Operations\CommandRejection;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Inputs are server-resolved records under the assignment transaction, never request assertions.
 * A partner reaches a Business when the Business's registered district is one of the districts the
 * partner accepts (Erastus, 24 Nov 2026). This replaces the 30 km radius between a staff-verified
 * office and premises (Robert's #154 answer), so no coordinates are captured or verified.
 *
 * @phpstan-import-type Standing from AuditorStanding
 * @phpstan-import-type Facts from AuditorIndependence
 * @phpstan-import-type Acceptance from AuditEngagementDocuments
 *
 * @phpstan-type Candidate array{id: string, standing: Standing, engagement: Acceptance|null, accepting: bool, active_count: int, consecutive_reports: int, last_assigned_at: string|null, districts: list<string>, business_district: string, accepted_this_week: int, max_jobs_per_week: int, financial_interest: bool, current_role_tie: bool, role_tie_ended_at: string|null, family_or_business_conflict: bool, unresolved_conflict: bool}
 */
final class AuditorDispatch
{
    /** The weekly limit a partner starts with and the bounds they may set it within (design: "Max jobs per week", 4). */
    public const int DEFAULT_MAX_JOBS_PER_WEEK = 4;

    public const int MIN_JOBS_PER_WEEK = 1;

    public const int MAX_JOBS_PER_WEEK = 20;

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
        if ($candidate['id'] === '' || $candidate['active_count'] < 0 || $candidate['consecutive_reports'] < 0 || $candidate['accepted_this_week'] < 0
            || ($candidate['last_assigned_at'] !== null && ($assigned === null || $assigned > $now))) {
            $reasons[] = 'AUDITOR_FACTS_INVALID';
        }
        if (! $candidate['accepting']) {
            $reasons[] = 'AUDITOR_UNAVAILABLE';
        }
        if ($candidate['engagement'] === null) {
            $reasons[] = 'AUDIT_ENGAGEMENT_ACCEPTANCE_REQUIRED';
        }
        if ($candidate['active_count'] >= 3) {
            $reasons[] = 'AUDITOR_CAPACITY_REACHED';
        }
        if ($candidate['accepted_this_week'] >= $candidate['max_jobs_per_week']) {
            $reasons[] = 'AUDITOR_WEEKLY_LIMIT_REACHED';
        }
        if ($candidate['consecutive_reports'] >= 3) {
            $reasons[] = 'AUDITOR_ROTATION_REQUIRED';
        }
        if ($this->hasConflict($candidate, $now)) {
            $reasons[] = 'AUDITOR_CONFLICT';
        }
        if (! self::covers($candidate['districts'], $candidate['business_district'])) {
            $reasons[] = 'AUDITOR_OUTSIDE_COVERAGE';
        }

        return $reasons;
    }

    /** @param Facts $facts */
    public function hasConflict(array $facts, DateTimeImmutable $now): bool
    {
        $now = $now->setTimezone(new DateTimeZone('UTC'));
        $tie = $this->timestamp($facts['role_tie_ended_at']);
        $cutoffMonth = $now->modify('first day of this month')->modify('-24 months');
        $cutoff = $cutoffMonth->setDate((int) $cutoffMonth->format('Y'), (int) $cutoffMonth->format('m'), min((int) $now->format('d'), (int) $cutoffMonth->format('t')));

        return $facts['financial_interest'] || $facts['current_role_tie'] || $facts['family_or_business_conflict'] || $facts['unresolved_conflict']
            || ($facts['role_tie_ended_at'] !== null && ($tie === null || $tie >= $cutoff));
    }

    /**
     * No candidate means Audit Operations must resolve the queue; no conflict or coverage is waived.
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

    /**
     * Whether a partner who accepts these districts reaches a Business registered in this one.
     *
     * @param  list<string>  $districts
     */
    public static function covers(array $districts, string $businessDistrict): bool
    {
        return $businessDistrict !== '' && in_array($businessDistrict, $districts, true);
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
