<?php

declare(strict_types=1);

use App\Domain\Auditor\AuditAssignmentClock;
use App\Domain\Operations\CommandRejection;

it('keeps Flash completion anchored to original dispatch across reassignment and caps late acceptance', function (): void {
    $clock = new AuditAssignmentClock;
    $original = new DateTimeImmutable('2026-09-24T08:00:00Z');
    $first = $clock->offer('flash', $original, $original, 1);
    $thirdAt = new DateTimeImmutable('2026-09-25T07:30:00Z');
    $third = $clock->offer('flash', $original, $thirdAt, 3);
    expect($first['accept_by']?->format(DATE_ATOM))->toBe('2026-09-24T09:00:00+00:00')
        ->and($third['accept_by']?->format(DATE_ATOM))->toBe('2026-09-25T08:00:00+00:00')
        ->and($third['complete_by'])->toEqual($first['complete_by'])->and($third['operations_required'])->toBeFalse();
    $accepted = $clock->accept('flash', $original, $thirdAt, 3, new DateTimeImmutable('2026-09-25T07:59:59Z'));
    expect($accepted['visit_by'])->toBeNull()->and($accepted['complete_by'])->toEqual($first['complete_by']);
});

it('gives Routine four hours to accept and forty-eight elapsed hours from acceptance to visit', function (): void {
    $clock = new AuditAssignmentClock;
    $original = new DateTimeImmutable('2026-10-31T10:00:00-04:00');
    $acceptedAt = new DateTimeImmutable('2026-10-31T13:59:59-04:00');
    $offer = $clock->offer('routine', $original, $original, 1);
    $accepted = $clock->accept('routine', $original, $original, 1, $acceptedAt);
    expect($offer['accept_by']?->getTimestamp() - $original->getTimestamp())->toBe(4 * 3600)
        ->and($offer['complete_by'])->toBeNull()->and($offer['operations_required'])->toBeFalse()
        ->and($accepted['visit_by']?->getTimestamp() - $acceptedAt->getTimestamp())->toBe(48 * 3600)
        ->and($accepted['complete_by'])->toBeNull();
});

it('routes exhausted dispatch attempts and elapsed Flash clocks to operations without extending deadlines', function (string $kind, int $attempt, string $offered): void {
    $clock = new AuditAssignmentClock;
    $original = new DateTimeImmutable('2026-09-24T08:00:00Z');
    $offeredAt = new DateTimeImmutable($offered);
    $offer = $clock->offer($kind, $original, $offeredAt, $attempt);
    expect($offer['operations_required'])->toBeTrue()->and($offer['accept_by'])->toBeNull()
        ->and(fn () => $clock->accept($kind, $original, $offeredAt, $attempt, $offeredAt))
        ->toThrow(CommandRejection::class, 'ASSIGNMENT_ACCEPTANCE_EXPIRED');
})->with([['flash', 4, '2026-09-24T10:00:00Z'], ['routine', 4, '2026-09-24T10:00:00Z'], ['flash', 2, '2026-09-25T08:00:00Z']]);

it('refuses acceptance at the cutoff or before the actual offer', function (string $kind, string $accepted): void {
    $time = new DateTimeImmutable('2026-09-24T08:00:00Z');
    expect(fn () => (new AuditAssignmentClock)->accept($kind, $time, $time, 1, new DateTimeImmutable($accepted)))
        ->toThrow(CommandRejection::class, 'ASSIGNMENT_ACCEPTANCE_EXPIRED');
})->with([['flash', '2026-09-24T09:00:00Z'], ['routine', '2026-09-24T12:00:00Z'], ['flash', '2026-09-24T07:59:59Z']]);

it('rejects invalid dispatch clock inputs', function (string $kind, int $attempt, string $offered): void {
    expect(fn () => (new AuditAssignmentClock)->offer($kind, new DateTimeImmutable('2026-09-24T08:00:00Z'), new DateTimeImmutable($offered), $attempt))
        ->toThrow(CommandRejection::class, 'AUDIT_CLOCK_INVALID');
})->with([['unknown', 1, '2026-09-24T08:00:00Z'], ['flash', 0, '2026-09-24T08:00:00Z'], ['flash', 1, '2026-09-24T07:59:59Z']]);
