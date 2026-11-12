<?php

declare(strict_types=1);

use App\Domain\Business\ReadinessSignals;

/*
 * BRS v3.0 RDY-R04 and RDY-R06: Record Gap and Forecast Error read declared monthly figures
 * against published reports, average the newest three readings and hold them to the stage's target.
 */

/**
 * @return array{period: string, money_in: string, money_out: string, forecast_period: string, forecast: string}
 */
function declaredMonth(string $period, string $moneyIn, string $moneyOut, string $forecastPeriod, string $forecast): array
{
    return ['period' => $period, 'money_in' => $moneyIn, 'money_out' => $moneyOut, 'forecast_period' => $forecastPeriod, 'forecast' => $forecast];
}

it('reads each period once, at its newest report, with inflow and outflow', function (): void {
    expect(ReadinessSignals::verified([
        ['period' => '2026-09', 'inflow' => '300', 'outflow' => '30'],
        ['period' => '2026-08', 'inflow' => '200', 'outflow' => '20'],
        ['period' => '2026-09', 'inflow' => '100', 'outflow' => '10'],
    ]))->toBe([
        ['period' => '2026-09', 'revenue' => '300', 'costs' => '30'],
        ['period' => '2026-08', 'revenue' => '200', 'costs' => '20'],
    ]);
});

it('averages the newest three Record Gap readings and holds them to the stage target', function (): void {
    $verified = [
        ['period' => '2026-06', 'revenue' => '1000', 'costs' => '0'],
        ['period' => '2026-07', 'revenue' => '1000', 'costs' => '1000'],
        ['period' => '2026-08', 'revenue' => '1000', 'costs' => '1000'],
        ['period' => '2026-09', 'revenue' => '1000', 'costs' => '1000'],
    ];
    $declared = [
        declaredMonth('2026-06', '0', '0', '2026-07', '0'),
        declaredMonth('2026-07', '1100', '1000', '2026-08', '0'),
        declaredMonth('2026-08', '1000', '1060', '2026-09', '0'),
        declaredMonth('2026-09', '1000', '1000', '2026-10', '0'),
    ];

    [$gap] = (new ReadinessSignals)->guidance($verified, $declared, 2);

    expect($gap)->toBe(['signal' => 'record_gap', 'value' => '2.7', 'target' => '5', 'on_target' => true, 'months' => 3, 'bias' => null,
        'action' => 'record_gap.keep']);
    expect((new ReadinessSignals)->guidance($verified, $declared, 4)[0])
        ->toMatchArray(['target' => '2', 'on_target' => false, 'action' => 'record_gap.fix']);
});

it('measures each forecast against the verified money in of the month it forecast, with its bias', function (): void {
    $verified = [
        ['period' => '2026-08', 'revenue' => '1000', 'costs' => '0'],
        ['period' => '2026-09', 'revenue' => '1000', 'costs' => '0'],
        ['period' => '2026-10', 'revenue' => '0', 'costs' => '0'],
    ];
    $declared = [
        declaredMonth('2026-07', '0', '0', '2026-08', '1400'),
        declaredMonth('2026-08', '1000', '0', '2026-09', '1200'),
        declaredMonth('2026-09', '1000', '0', '2026-10', '900'),
    ];

    $lines = (new ReadinessSignals)->guidance($verified, $declared, null);

    expect($lines)->toHaveCount(2)
        ->and($lines[1])->toBe(['signal' => 'forecast_error', 'value' => '30.0', 'target' => '25', 'on_target' => false, 'months' => 2,
            'bias' => 'over', 'action' => 'forecast_error.fix']);
    expect((new ReadinessSignals)->guidance($verified, [declaredMonth('2026-07', '0', '0', '2026-08', '900')], 0)[0])
        ->toMatchArray(['signal' => 'forecast_error', 'value' => '10.0', 'target' => '25', 'on_target' => true, 'bias' => 'under',
            'action' => 'forecast_error.keep']);
    expect((new ReadinessSignals)->guidance($verified, [declaredMonth('2026-07', '0', '0', '2026-08', '1000')], 1)[0]['bias'])->toBeNull();
});

it('has no line before a month has been both declared and verified', function (): void {
    expect((new ReadinessSignals)->guidance([], [declaredMonth('2026-09', '1', '1', '2026-10', '1')], 1))->toBe([])
        ->and((new ReadinessSignals)->guidance([['period' => '2026-09', 'revenue' => '1', 'costs' => '1']], [], 1))->toBe([]);
});
