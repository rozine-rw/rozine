<?php

declare(strict_types=1);

use App\Domain\Auditor\AuditorDispatch;
use App\Domain\Auditor\AuditorStanding;
use App\Domain\Operations\CommandRejection;
use Tests\Support\AuditorFixture;

it('admits the exact conservative radius boundary and prefers fewest active then oldest assignment then stable ID', function (): void {
    $policy = new AuditorDispatch(new AuditorStanding);
    $now = new DateTimeImmutable('2026-09-24T08:00:00Z');
    $a = AuditorFixture::candidate('partner-a');
    $b = AuditorFixture::candidate('partner-b');
    $c = AuditorFixture::candidate('partner-c');
    $a['active_count'] = 1;
    $b['last_assigned_at'] = '2026-09-23T08:00:00Z';
    expect($policy->reasons($c, $now))->toBe([])->and($policy->select([$a, $b, $c], $now))->toBe('partner-c');
    $c['last_assigned_at'] = '2026-09-24T07:00:00Z';
    expect($policy->select([$c, $b, $a], $now))->toBe('partner-b');
    $c['last_assigned_at'] = $b['last_assigned_at'];
    expect($policy->select([$c, $b], $now))->toBe('partner-b')->and($policy->select([$b, $c], $now))->toBe('partner-b');
});

it('excludes unsafe candidates without waiving standing capacity rotation conflicts or location checks', function (string $case, string $reason): void {
    $candidate = AuditorFixture::candidate();
    switch ($case) {
        case 'suspended': $candidate['standing']['status'] = 'suspended';
            break;
        case 'empty id': $candidate['id'] = '';
            break;
        case 'negative count': $candidate['active_count'] = -1;
            break;
        case 'negative reports': $candidate['consecutive_reports'] = -1;
            break;
        case 'invalid assignment time': $candidate['last_assigned_at'] = '2026-02-30T08:00:00Z';
            break;
        case 'future assignment': $candidate['last_assigned_at'] = '2026-09-24T08:00:01Z';
            break;
        case 'paused': $candidate['accepting'] = false;
            break;
        case 'full': $candidate['active_count'] = 3;
            break;
        case 'rotation': $candidate['consecutive_reports'] = 3;
            break;
        case 'financial interest': $candidate['financial_interest'] = true;
            break;
        case 'current role tie': $candidate['current_role_tie'] = true;
            break;
        case 'family tie': $candidate['family_or_business_conflict'] = true;
            break;
        case 'unresolved declaration': $candidate['unresolved_conflict'] = true;
            break;
        case 'role at cutoff': $candidate['role_tie_ended_at'] = '2024-09-24T08:00:00Z';
            break;
        case 'invalid role date': $candidate['role_tie_ended_at'] = 'unknown';
            break;
        case 'office not verified': $candidate['office']['verified_at'] = null;
            break;
        case 'office stale': $candidate['office']['verified_at'] = '2025-09-24T07:59:59Z';
            break;
        case 'premises future': $candidate['premises']['verified_at'] = '2026-09-24T08:00:01Z';
            break;
        case 'premises moved':
            $candidate['premises']['verified_at'] = '2026-09-23T08:00:00Z';
            $candidate['premises']['moved_at'] = '2026-09-24T08:00:00Z';
            break;
        case 'unknown move time': $candidate['office']['moved_at'] = 'unknown';
            break;
        case 'missing accuracy': $candidate['office']['uncertainty_m'] = null;
            break;
        case 'negative accuracy': $candidate['premises']['uncertainty_m'] = -1;
            break;
        case 'unbounded accuracy': $candidate['office']['uncertainty_m'] = PHP_INT_MAX;
            break;
        case 'unknown distance': $candidate['distance_upper_bound_m'] = null;
            break;
        case 'negative distance': $candidate['distance_upper_bound_m'] = -1;
            break;
        case 'far beyond radius': $candidate['distance_upper_bound_m'] = PHP_INT_MAX;
            break;
        case 'one metre outside': $candidate['distance_upper_bound_m'] = 29901;
            break;
    }
    $policy = new AuditorDispatch(new AuditorStanding);
    $now = new DateTimeImmutable('2026-09-24T08:00:00Z');
    expect($policy->reasons($candidate, $now))->toContain($reason)->and($policy->select([$candidate], $now))->toBeNull();
})->with([
    ['suspended', 'ACCREDITATION_SUSPENDED'], ['empty id', 'AUDITOR_FACTS_INVALID'], ['negative count', 'AUDITOR_FACTS_INVALID'],
    ['negative reports', 'AUDITOR_FACTS_INVALID'], ['invalid assignment time', 'AUDITOR_FACTS_INVALID'], ['future assignment', 'AUDITOR_FACTS_INVALID'],
    ['paused', 'AUDITOR_UNAVAILABLE'], ['full', 'AUDITOR_CAPACITY_REACHED'], ['rotation', 'AUDITOR_ROTATION_REQUIRED'],
    ['financial interest', 'AUDITOR_CONFLICT'], ['current role tie', 'AUDITOR_CONFLICT'], ['family tie', 'AUDITOR_CONFLICT'],
    ['unresolved declaration', 'AUDITOR_CONFLICT'], ['role at cutoff', 'AUDITOR_CONFLICT'], ['invalid role date', 'AUDITOR_CONFLICT'],
    ['office not verified', 'AUDITOR_LOCATION_REVIEW_REQUIRED'], ['office stale', 'AUDITOR_LOCATION_REVIEW_REQUIRED'],
    ['premises future', 'AUDITOR_LOCATION_REVIEW_REQUIRED'], ['premises moved', 'AUDITOR_LOCATION_REVIEW_REQUIRED'],
    ['unknown move time', 'AUDITOR_LOCATION_REVIEW_REQUIRED'], ['missing accuracy', 'AUDITOR_LOCATION_REVIEW_REQUIRED'],
    ['negative accuracy', 'AUDITOR_LOCATION_REVIEW_REQUIRED'], ['unbounded accuracy', 'AUDITOR_LOCATION_REVIEW_REQUIRED'],
    ['unknown distance', 'AUDITOR_OUTSIDE_RADIUS'], ['negative distance', 'AUDITOR_OUTSIDE_RADIUS'], ['far beyond radius', 'AUDITOR_OUTSIDE_RADIUS'],
    ['one metre outside', 'AUDITOR_OUTSIDE_RADIUS'],
]);

it('accepts exactly current location verification and an expired lookback with capacity remaining', function (): void {
    $candidate = AuditorFixture::candidate();
    $candidate['office']['verified_at'] = $candidate['office']['moved_at'] = '2025-09-24T08:00:00Z';
    $candidate['role_tie_ended_at'] = '2024-09-24T07:59:59Z';
    $candidate['active_count'] = $candidate['consecutive_reports'] = 2;
    expect((new AuditorDispatch(new AuditorStanding))->select([$candidate], new DateTimeImmutable('2026-09-24T08:00:00Z')))->toBe('partner-a');
});

it('uses a full calendar lookback at leap day instead of rolling February into March', function (): void {
    $candidate = AuditorFixture::candidate();
    $candidate['standing']['checked_at'] = $candidate['office']['verified_at'] = $candidate['premises']['verified_at'] = '2028-02-29T08:00:00Z';
    $candidate['role_tie_ended_at'] = '2026-02-28T08:00:00Z';
    $policy = new AuditorDispatch(new AuditorStanding);
    $now = new DateTimeImmutable('2028-02-29T08:00:00Z');
    expect($policy->reasons($candidate, $now))->toBe(['AUDITOR_CONFLICT']);
    $candidate['role_tie_ended_at'] = '2026-02-28T07:59:59Z';
    expect($policy->reasons($candidate, $now))->toBe([]);
});

it('leaves an empty queue for Audit Operations and rejects conflicting candidate inventory', function (): void {
    $policy = new AuditorDispatch(new AuditorStanding);
    $now = new DateTimeImmutable('2026-09-24T08:00:00Z');
    expect($policy->select([], $now))->toBeNull()
        ->and(fn () => $policy->select([AuditorFixture::candidate(), AuditorFixture::candidate()], $now))
        ->toThrow(CommandRejection::class, 'AUDITOR_CANDIDATE_CONFLICT');
});
