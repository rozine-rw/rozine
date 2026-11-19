<?php

declare(strict_types=1);

use App\Infrastructure\Business\EloquentStaffApplicationQueue;

/**
 * @param  list<string>  $repaid
 * @return array<string, mixed>
 */
function staffApplicationCapacity(array $repaid, string $committed = '9000000'): array
{
    $quote = ['result' => ['cash_flow' => ['exposure_limit' => ['numerator' => '20000000', 'denominator' => '1'],
        'committed_exposure' => ['numerator' => $committed, 'denominator' => '1']]],
        'accepted_commitments' => [['id' => 'repaid', 'principal' => '9000000']]];

    /** @var array<string, mixed> */
    return (new ReflectionMethod(EloquentStaffApplicationQueue::class, 'capacity'))->invoke(null, $quote, '5000000', $repaid);
}

it('counts a note the quote counted as owed until that note is repaid in full (QA #672)', function (): void {
    $none = ['currency' => 'RWF', 'amount' => '0'];
    expect(staffApplicationCapacity([]))->toBe(['used_pct' => 70, 'active_notes' => 1, 'outstanding' => ['currency' => 'RWF', 'amount' => '9000000'], 'other_debts' => $none])
        ->and(staffApplicationCapacity(['repaid']))->toBe(['used_pct' => 25, 'active_notes' => 0, 'outstanding' => $none, 'other_debts' => $none]);
});

it('shows other loans on the verified evidence apart from the notes\' outstanding principal (QA #672)', function (): void {
    expect(staffApplicationCapacity(['repaid'], '12000000'))->toBe(['used_pct' => 40, 'active_notes' => 0,
        'outstanding' => ['currency' => 'RWF', 'amount' => '0'], 'other_debts' => ['currency' => 'RWF', 'amount' => '3000000']])
        ->and(staffApplicationCapacity([], '12000000'))->toMatchArray(['outstanding' => ['currency' => 'RWF', 'amount' => '9000000'],
            'other_debts' => ['currency' => 'RWF', 'amount' => '3000000']]);
});
