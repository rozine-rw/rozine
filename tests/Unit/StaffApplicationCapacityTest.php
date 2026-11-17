<?php

declare(strict_types=1);

use App\Infrastructure\Business\EloquentStaffApplicationQueue;

/**
 * @param  list<string>  $repaid
 * @return array<string, mixed>
 */
function staffApplicationCapacity(array $repaid): array
{
    $quote = ['result' => ['cash_flow' => ['exposure_limit' => ['numerator' => '20000000', 'denominator' => '1'],
        'committed_exposure' => ['numerator' => '9000000', 'denominator' => '1']]],
        'accepted_commitments' => [['id' => 'repaid', 'principal' => '9000000']]];

    /** @var array<string, mixed> */
    return (new ReflectionMethod(EloquentStaffApplicationQueue::class, 'capacity'))->invoke(null, $quote, '5000000', $repaid);
}

it('counts a note the quote counted as owed until that note is repaid in full (QA #672)', function (): void {
    expect(staffApplicationCapacity([]))->toBe(['used_pct' => 70, 'active_notes' => 1, 'outstanding' => ['currency' => 'RWF', 'amount' => '9000000']])
        ->and(staffApplicationCapacity(['repaid']))->toBe(['used_pct' => 25, 'active_notes' => 0, 'outstanding' => ['currency' => 'RWF', 'amount' => '0']]);
});
