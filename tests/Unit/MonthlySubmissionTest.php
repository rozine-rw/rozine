<?php

declare(strict_types=1);

use App\Domain\Business\MonthlySubmission;
use App\Domain\Operations\CommandRejection;

/*
 * BRS v3.0 VIS-R04: the figures a Business declares for its audited month, and the forecast that
 * needs a signed order when it moves more than 25% from the verified 3-month average.
 */

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function monthlyInput(array $overrides = []): array
{
    return [...['money_in' => '12500000', 'costs' => ['stock' => '4000000', 'staff' => '2000000', 'premises' => '800000', 'transport' => '300000',
        'taxes' => '150000', 'other' => '50000'], 'paid_to_owners' => '1000000', 'forecast' => '12000000', 'order' => null,
        'changes' => ['owners' => false, 'premises' => false, 'borrowing' => true, 'customers' => false]], ...$overrides];
}

/**
 * @param  array<string, mixed>  $input
 * @return array<string, list<string>>
 */
function monthlyRefusal(array $input, ?string $basis): array
{
    try {
        MonthlySubmission::figures($input, $basis);
    } catch (CommandRejection $rejection) {
        expect($rejection->reason)->toBe('VALIDATION_FAILED')->and($rejection->status)->toBe(422);

        return $rejection->fieldErrors;
    }
    throw new RuntimeException('The figures were accepted.');
}

it('averages the newest three published months, rounding down, and has no basis with none', function (): void {
    expect(MonthlySubmission::forecastBasis([]))->toBeNull()
        ->and(MonthlySubmission::forecastBasis([['period' => '2026-05', 'revenue' => '10']]))->toBe('10')
        ->and(MonthlySubmission::forecastBasis([['period' => '2026-04', 'revenue' => '999'], ['period' => '2026-07', 'revenue' => '10'],
            ['period' => '2026-05', 'revenue' => '11'], ['period' => '2026-06', 'revenue' => '11']]))->toBe('10');
});

it('needs a signed order only past 25% either way from a basis above zero', function (): void {
    expect(MonthlySubmission::needsOrder('125', '100'))->toBeFalse()
        ->and(MonthlySubmission::needsOrder('126', '100'))->toBeTrue()
        ->and(MonthlySubmission::needsOrder('75', '100'))->toBeFalse()
        ->and(MonthlySubmission::needsOrder('74', '100'))->toBeTrue()
        ->and(MonthlySubmission::needsOrder('5000', null))->toBeFalse()
        ->and(MonthlySubmission::needsOrder('5000', '0'))->toBeFalse();
});

it('keeps the declared figures with the cost total, the basis and the four answers', function (): void {
    expect(MonthlySubmission::figures(monthlyInput(['money_in' => ' 12500000 ']), '11000000'))->toBe(['money_in' => '12500000',
        'costs' => ['stock' => '4000000', 'staff' => '2000000', 'premises' => '800000', 'transport' => '300000', 'taxes' => '150000', 'other' => '50000'],
        'costs_total' => '7300000', 'paid_to_owners' => '1000000', 'forecast' => '12000000', 'forecast_basis' => '11000000', 'order' => null,
        'changes' => ['owners' => false, 'premises' => false, 'borrowing' => true, 'customers' => false]])
        ->and(MonthlySubmission::figures(monthlyInput(['forecast' => '20000000', 'order' => '  Order 114 from Simba Supermarket  ']), '11000000')['order'])
        ->toBe('Order 114 from Simba Supermarket')
        ->and(MonthlySubmission::figures(monthlyInput(['forecast' => 0, 'order' => 'ignored within 25%']), null)['order'])->toBeNull();
});

it('refuses each field that is not a whole amount or a yes or no, and a big change with no order', function (): void {
    $amount = ['Enter a whole amount in Rwandan francs, 0 or more.'];
    expect(monthlyRefusal(monthlyInput(['money_in' => '-5', 'costs' => ['stock' => '1.5', 'staff' => '07'], 'paid_to_owners' => 'ten',
        'forecast' => '1234567890123456', 'changes' => ['owners' => 'yes', 'premises' => false]]), '100'))->toBe(['money_in' => $amount,
            'costs.stock' => $amount, 'costs.staff' => $amount, 'costs.premises' => $amount, 'costs.transport' => $amount, 'costs.taxes' => $amount,
            'costs.other' => $amount, 'paid_to_owners' => $amount, 'forecast' => $amount, 'changes.owners' => ['Answer yes or no.'],
            'changes.borrowing' => ['Answer yes or no.'], 'changes.customers' => ['Answer yes or no.']])
        ->and(monthlyRefusal(monthlyInput(['costs' => null, 'changes' => null, 'forecast' => '20000000']), '11000000'))->toHaveKeys(['costs.stock', 'changes.owners',
            'order'])
        ->and(monthlyRefusal(monthlyInput(['forecast' => '20000000']), '11000000')['order'])
        ->toBe(['This forecast is more than 25% away from your verified average. Name the signed order behind it.'])
        ->and(monthlyRefusal(monthlyInput(['forecast' => '20000000', 'order' => str_repeat('a', 201)]), '11000000')['order'])
        ->toBe(['Keep the order to 200 characters.']);
});
