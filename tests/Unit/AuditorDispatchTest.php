<?php

declare(strict_types=1);

use App\Domain\Auditor\AuditorDispatch;
use App\Domain\Auditor\AuditorStanding;
use App\Domain\Operations\CommandRejection;
use Tests\Support\AuditorFixture;

it('prefers fewest active then oldest assignment then stable ID', function (): void {
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

it('excludes unsafe candidates without waiving standing capacity rotation conflicts coverage or the weekly limit', function (string $case, string $reason): void {
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
        case 'unsigned engagement': $candidate['engagement'] = null;
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
        case 'no districts': $candidate['districts'] = [];
            break;
        case 'other district': $candidate['business_district'] = 'Rubavu';
            break;
        case 'no business district': $candidate['business_district'] = '';
            break;
        case 'weekly limit reached': $candidate['accepted_this_week'] = 4;
            break;
        case 'weekly limit lowered': $candidate['accepted_this_week'] = 1;
            $candidate['max_jobs_per_week'] = 1;
            break;
        case 'negative weekly count': $candidate['accepted_this_week'] = -1;
            break;
    }
    $policy = new AuditorDispatch(new AuditorStanding);
    $now = new DateTimeImmutable('2026-09-24T08:00:00Z');
    expect($policy->reasons($candidate, $now))->toContain($reason)->and($policy->select([$candidate], $now))->toBeNull();
})->with([
    ['suspended', 'ACCREDITATION_SUSPENDED'], ['empty id', 'AUDITOR_FACTS_INVALID'], ['negative count', 'AUDITOR_FACTS_INVALID'],
    ['negative reports', 'AUDITOR_FACTS_INVALID'], ['invalid assignment time', 'AUDITOR_FACTS_INVALID'], ['future assignment', 'AUDITOR_FACTS_INVALID'],
    ['paused', 'AUDITOR_UNAVAILABLE'], ['full', 'AUDITOR_CAPACITY_REACHED'], ['rotation', 'AUDITOR_ROTATION_REQUIRED'],
    ['unsigned engagement', 'AUDIT_ENGAGEMENT_ACCEPTANCE_REQUIRED'],
    ['financial interest', 'AUDITOR_CONFLICT'], ['current role tie', 'AUDITOR_CONFLICT'], ['family tie', 'AUDITOR_CONFLICT'],
    ['unresolved declaration', 'AUDITOR_CONFLICT'], ['role at cutoff', 'AUDITOR_CONFLICT'], ['invalid role date', 'AUDITOR_CONFLICT'],
    ['no districts', 'AUDITOR_OUTSIDE_COVERAGE'], ['other district', 'AUDITOR_OUTSIDE_COVERAGE'], ['no business district', 'AUDITOR_OUTSIDE_COVERAGE'],
    ['weekly limit reached', 'AUDITOR_WEEKLY_LIMIT_REACHED'], ['weekly limit lowered', 'AUDITOR_WEEKLY_LIMIT_REACHED'], ['negative weekly count', 'AUDITOR_FACTS_INVALID'],
]);

it('accepts a Business in one of the partner\'s districts below the weekly limit and an expired lookback with capacity remaining', function (): void {
    $candidate = AuditorFixture::candidate();
    $candidate['business_district'] = 'Kicukiro';
    $candidate['accepted_this_week'] = 3;
    $candidate['role_tie_ended_at'] = '2024-09-24T07:59:59Z';
    $candidate['active_count'] = $candidate['consecutive_reports'] = 2;
    expect((new AuditorDispatch(new AuditorStanding))->select([$candidate], new DateTimeImmutable('2026-09-24T08:00:00Z')))->toBe('partner-a');
});

it('uses a full calendar lookback at leap day instead of rolling February into March', function (): void {
    $candidate = AuditorFixture::candidate();
    $candidate['standing']['checked_at'] = '2028-02-29T08:00:00Z';
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
