<?php

declare(strict_types=1);

use App\Domain\Business\ReadinessRequirements;

/*
 * RDY-R03: which requirements a settled stage puts in force, the grace each starts with, and the
 * exact Kigali day Due or Lapsed turns Overdue.
 */

it('puts C1 and F1 in force from entry and the size requirements from their stage', function (): void {
    $rules = new ReadinessRequirements;

    expect($rules->triggered(0))->toBe(['C1', 'F1'])
        ->and($rules->triggered(1))->toBe(['C1', 'F1'])
        ->and($rules->triggered(2))->toBe(['C1', 'F1', 'F2'])
        ->and($rules->triggered(3))->toBe(['C1', 'F1', 'F2', 'F3', 'G1'])
        // P1 and P2 follow headcount, which has no verified source yet.
        ->and($rules->triggered(4))->toBe(['C1', 'F1', 'F2', 'F3', 'F4', 'G1', 'G2']);
});

it('starts each requirement Met at entry or Due with its grace', function (): void {
    $rules = new ReadinessRequirements;

    expect($rules->start('C1', '2026-11-10'))->toBe(['status' => 'met', 'due_on' => null])
        ->and($rules->start('F1', '2026-11-10'))->toBe(['status' => 'met', 'due_on' => null])
        ->and($rules->start('F2', '2026-11-10'))->toBe(['status' => 'due', 'due_on' => '2027-02-08'])
        ->and($rules->start('F3', '2026-11-10'))->toBe(['status' => 'due', 'due_on' => '2027-03-10'])
        ->and($rules->start('F4', '2026-11-10'))->toBe(['status' => 'due', 'due_on' => '2026-12-31'])
        ->and($rules->start('P1', '2026-11-10'))->toBe(['status' => 'due', 'due_on' => '2027-01-09'])
        ->and($rules->start('G2', '2026-11-10'))->toBe(['status' => 'due', 'due_on' => '2027-05-09']);
});

it('turns Due and Lapsed Overdue on the exact day the grace ends, and counts the days left', function (): void {
    $rules = new ReadinessRequirements;

    expect($rules->on('due', '2027-02-08', '2027-02-07'))->toBe('due')
        ->and($rules->on('due', '2027-02-08', '2027-02-08'))->toBe('overdue')
        ->and($rules->on('lapsed', '2027-02-08', '2027-02-09'))->toBe('overdue')
        ->and($rules->on('met', null, '2027-02-09'))->toBe('met')
        ->and($rules->on('overdue', '2027-02-08', '2027-02-09'))->toBe('overdue')
        ->and($rules->daysLeft('2027-02-08', '2027-01-25'))->toBe(14)
        ->and($rules->daysLeft('2027-02-08', '2027-02-10'))->toBe(-2)
        ->and($rules->lapsedDue('2026-11-10'))->toBe('2027-01-09');
});
