<?php

declare(strict_types=1);

use App\Domain\Business\ReadinessMilestones;

/*
 * BRS v3.0 RDY-R08: the bond track and the RSE Investment Clinic referral, each a list of its
 * conditions; a condition Rozine cannot measure yet stays pending, so neither milestone is reached.
 */

/**
 * @param  array{stage?: int|null, company?: bool, established_year?: int|null, notes?: int, audited?: bool}  $overrides
 * @return array{stage: int|null, company: bool, established_year: int|null, notes: int, audited: bool}
 */
function milestoneFacts(array $overrides = []): array
{
    return [
        'stage' => array_key_exists('stage', $overrides) ? $overrides['stage'] : 3,
        'company' => $overrides['company'] ?? true,
        'established_year' => array_key_exists('established_year', $overrides) ? $overrides['established_year'] : 2020,
        'notes' => $overrides['notes'] ?? 3,
        'audited' => $overrides['audited'] ?? true,
    ];
}

/**
 * @param  array{reached: bool, conditions: list<array{key: string, state: string}>}  $track
 * @return array<string, string>
 */
function milestoneStates(array $track): array
{
    return array_column($track['conditions'], 'state', 'key');
}

it('meets every condition it can read for a stage 3 company and leaves the rest pending', function (): void {
    $milestones = ReadinessMilestones::assess(milestoneFacts(), new DateTimeImmutable('2026-11-10'));

    expect(milestoneStates($milestones['bond']))->toBe(['stage' => 'met', 'ready_6_months' => 'pending', 'notes_on_time' => 'pending', 'audited_statements' => 'met'])
        ->and(milestoneStates($milestones['clinic']))->toBe(['limited_company' => 'met', 'trading_3_years' => 'met', 'no_proceedings' => 'pending',
            'stage' => 'met', 'ready_12_months' => 'pending'])
        ->and($milestones['bond']['reached'])->toBeFalse()
        ->and($milestones['clinic']['reached'])->toBeFalse();
});

it('marks a lower stage, fewer than 3 notes, no audit and a sole trader as not met', function (): void {
    $milestones = ReadinessMilestones::assess(milestoneFacts(['stage' => null, 'company' => false, 'notes' => 2, 'audited' => false, 'established_year' => null]),
        new DateTimeImmutable('2026-11-10'));

    expect(milestoneStates($milestones['bond']))->toMatchArray(['stage' => 'not_met', 'notes_on_time' => 'not_met', 'audited_statements' => 'not_met'])
        ->and(milestoneStates($milestones['clinic']))->toMatchArray(['limited_company' => 'not_met', 'trading_3_years' => 'pending', 'stage' => 'not_met'])
        ->and(milestoneStates(ReadinessMilestones::assess(milestoneFacts(['stage' => 2]), new DateTimeImmutable('2026-11-10'))['bond'])['stage'])->toBe('not_met');
});

it('counts 3 years of trading from the end of the year the business was established', function (): void {
    $established = milestoneFacts(['established_year' => 2023]);

    expect(milestoneStates(ReadinessMilestones::assess($established, new DateTimeImmutable('2026-12-30'))['clinic'])['trading_3_years'])->toBe('not_met')
        ->and(milestoneStates(ReadinessMilestones::assess($established, new DateTimeImmutable('2026-12-31'))['clinic'])['trading_3_years'])->toBe('met');
});
