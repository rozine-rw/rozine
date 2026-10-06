<?php

declare(strict_types=1);

use App\Domain\Auditor\AuditMonthlyFacts;

it('projects only the pinned month with exact totals and factual coverage', function (string $inflow, string $outflow, string $debt, string $net, ?string $cover): void {
    $month = ['month' => '2026-08', 'operating_inflow' => $inflow, 'operating_outflow' => $outflow, 'owner_draw' => '0',
        'debt_service' => $debt, 'financing_inflow' => '99999999', 'transfer_inflow' => '99999999', 'transfer_outflow' => '99999999',
        'verified' => true, 'source_ids' => ['original-a', 'original-b'], 'rail_ids' => ['mobile', 'bank'], 'classification_version' => 'statement-classification-2'];
    $statement = ['rail_id' => 'bank', 'month' => '2026-08', 'opening_balance' => '0', 'closing_balance' => '9007199254740993',
        'source_ids' => ['original-a'], 'transactions' => []];
    $facts = (new AuditMonthlyFacts)->project('2026-08', [[...$month, 'month' => '2026-07', 'operating_inflow' => '1'], $month], [
        $statement, [...$statement, 'rail_id' => 'mobile', 'closing_balance' => '7', 'source_ids' => ['original-b']],
        [...$statement, 'month' => '2026-07', 'closing_balance' => '99999999999'],
    ]);
    expect($facts)->toBe(['period' => '2026-08', 'inflow' => $inflow, 'outflow' => $outflow, 'net' => $net, 'cover' => $cover,
        'closing_balance' => '9007199254741000', 'source_ids' => ['original-a', 'original-b'], 'rail_ids' => ['bank', 'mobile']]);
})->with([
    ['100', '60', '20', '40', '1.25'],
    ['100', '200', '0', '-100', '0.50'],
    ['0', '0', '0', '0', null],
    ['1', '8', '0', '-7', '0.13'],
    ['9007199254740993', '1', '0', '9007199254740992', '9007199254740993.00'],
]);

it('refuses missing or ambiguous monthly sources rather than displaying invented totals', function (string $fault): void {
    $period = '2026-08';
    $month = ['month' => $period, 'operating_inflow' => '100', 'operating_outflow' => '50', 'owner_draw' => '0',
        'debt_service' => '0', 'financing_inflow' => '0', 'transfer_inflow' => '0', 'transfer_outflow' => '0',
        'verified' => true, 'source_ids' => ['original'], 'rail_ids' => ['bank'], 'classification_version' => 'statement-classification-2'];
    $statement = ['rail_id' => 'bank', 'month' => $period, 'opening_balance' => '0', 'closing_balance' => '50', 'source_ids' => ['original'], 'transactions' => []];
    $months = [$month];
    $statements = [$statement];
    if ($fault === 'period') {
        $period = null;
    } elseif ($fault === 'missing_month') {
        $months = [];
    } elseif ($fault === 'duplicate_month') {
        $months[] = $month;
    } elseif ($fault === 'originals') {
        $months[0]['source_ids'] = [];
    } elseif ($fault === 'missing_rails') {
        $statements = [];
    } elseif ($fault === 'different_rails') {
        $statements[0]['rail_id'] = 'unknown';
    } else {
        $statements[] = $statement;
        $months[0]['rail_ids'][] = 'bank';
    }
    expect((new AuditMonthlyFacts)->project($period, $months, $statements))->toBeNull();
})->with(['period', 'missing_month', 'duplicate_month', 'originals', 'missing_rails', 'different_rails', 'duplicate_rails']);
