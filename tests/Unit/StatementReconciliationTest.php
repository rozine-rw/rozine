<?php

declare(strict_types=1);

use App\Domain\Evidence\StatementReconciliation;
use App\Domain\Operations\CommandRejection;
use Tests\Support\StatementFixture;

it('reconciles all rails without counting financing or transfers as revenue or double deducting draws and debt', function (): void {
    $fixture = StatementFixture::reconciliation();
    $fixture['statements'][0]['transactions'][] = $fixture['statements'][0]['transactions'][0];
    $result = (new StatementReconciliation)->reconcile($fixture['rails'], $fixture['months'], $fixture['statements'], $fixture['sources']);
    expect($result)->toBe([[
        'month' => '2026-08', 'operating_inflow' => '1000', 'operating_outflow' => '300',
        'owner_draw' => '200', 'debt_service' => '150', 'financing_inflow' => '2000',
        'transfer_inflow' => '500', 'transfer_outflow' => '500', 'verified' => false,
        'source_ids' => ['original-a', 'original-b'], 'rail_ids' => ['bank-a', 'momo-b'],
        'classification_version' => StatementReconciliation::VERSION,
    ]]);
});

it('preserves exact balances larger than machine integers and genuine observed zero months', function (): void {
    $fixture = StatementFixture::reconciliation();
    $fixture['statements'][0]['opening_balance'] = '900719925474099312345678901234567890';
    $fixture['statements'][0]['closing_balance'] = '900719925474099312345678901234569840';
    $reconciler = new StatementReconciliation;
    expect($reconciler->reconcile($fixture['rails'], $fixture['months'], $fixture['statements'], $fixture['sources'])[0]['operating_inflow'])->toBe('1000');
    foreach ($fixture['statements'] as &$statement) {
        $statement['opening_balance'] = $statement['closing_balance'] = '-500';
        $statement['transactions'] = [];
    }
    unset($statement);
    $zero = $reconciler->reconcile($fixture['rails'], $fixture['months'], $fixture['statements'], $fixture['sources'])[0];
    expect($zero['operating_inflow'])->toBe('0')->and($zero['operating_outflow'])->toBe('0')
        ->and($zero['owner_draw'])->toBe('0')->and($zero['debt_service'])->toBe('0')->and($zero['verified'])->toBeFalse();
});

it('checks balance continuity after ordering complete months and respects a rail closure', function (): void {
    $fixture = StatementFixture::reconciliation();
    $fixture['months'] = ['2026-09', '2026-08'];
    $fixture['rails'][1]['active_until'] = '2026-08';
    $fixture['statements'][1]['closing_balance'] = '0';
    $fixture['statements'][1]['transactions'][] = StatementFixture::transaction('closing-cost', '-400', 'operating_outflow', source: 'original-b');
    $fixture['statements'][] = ['rail_id' => 'bank-a', 'month' => '2026-09', 'opening_balance' => '2950', 'closing_balance' => '2950',
        'source_ids' => ['original-a'], 'transactions' => []];
    $reconciler = new StatementReconciliation;
    $result = $reconciler->reconcile($fixture['rails'], $fixture['months'], $fixture['statements'], $fixture['sources']);
    expect(array_column($result, 'month'))->toBe(['2026-08', '2026-09'])
        ->and($result[1]['rail_ids'])->toBe(['bank-a'])->and($result[1]['operating_inflow'])->toBe('0');
    $fixture['statements'][2]['opening_balance'] = '2951';
    $fixture['statements'][2]['closing_balance'] = '2951';
    expect(fn () => $reconciler->reconcile($fixture['rails'], $fixture['months'], $fixture['statements'], $fixture['sources']))
        ->toThrow(CommandRejection::class, 'STATEMENT_BALANCE_CONTINUITY_REQUIRED');
});

it('offsets a partial same-month owner return on another declared rail without treating it as income', function (): void {
    $fixture = StatementFixture::reconciliation();
    array_splice($fixture['statements'][0]['transactions'], 5, 1);
    $fixture['statements'][0]['closing_balance'] = '2850';
    $fixture['statements'][1]['transactions'][] = StatementFixture::transaction('returned', '100', 'owner_return', source: 'original-b',
        returnOf: ['rail_id' => 'bank-a', 'month' => '2026-08', 'reference' => 'draw']);
    $fixture['statements'][1]['closing_balance'] = '500';
    $result = (new StatementReconciliation)->reconcile(array_reverse($fixture['rails']), $fixture['months'], $fixture['statements'], $fixture['sources']);
    expect($result[0]['owner_draw'])->toBe('200')->and($result[0]['operating_inflow'])->toBe('1000');
});

it('does not reduce this months draws with a return attributed to a prior month', function (): void {
    $fixture = StatementFixture::reconciliation();
    $fixture['statements'][0]['transactions'][5]['return_of'] = ['rail_id' => 'bank-a', 'month' => '2026-07', 'reference' => 'prior-draw'];
    $result = (new StatementReconciliation)->reconcile($fixture['rails'], $fixture['months'], $fixture['statements'], $fixture['sources']);
    expect($result[0]['owner_draw'])->toBe('300')->and($result[0]['operating_inflow'])->toBe('1000')->and($result[0]['verified'])->toBeFalse();
});

it('requires the approved one-off resolution for the exact draw and keeps its lineage', function (): void {
    $fixture = StatementFixture::reconciliation();
    $fixture['statements'][0]['transactions'][4]['exception_id'] = 'resolution-original';
    $fixture['sources'][] = 'resolution-original';
    $reconciler = new StatementReconciliation;
    foreach ([[], ['2026-08/bank-a/different-draw' => 'resolution-original'], ['2026-08/bank-a/draw' => 'other-resolution']] as $approval) {
        expect(fn () => $reconciler->reconcile($fixture['rails'], $fixture['months'], $fixture['statements'], $fixture['sources'], $approval))
            ->toThrow(CommandRejection::class, 'DRAW_EXCEPTION_REVIEW_REQUIRED');
    }
    $approval = ['2026-08/bank-a/draw' => 'resolution-original'];
    $result = $reconciler->reconcile($fixture['rails'], $fixture['months'], $fixture['statements'], $fixture['sources'], $approval);
    expect($result[0]['owner_draw'])->toBe('0')->and($result[0]['operating_outflow'])->toBe('300')
        ->and($result[0]['source_ids'])->toBe(['original-a', 'original-b', 'resolution-original']);
    expect(fn () => $reconciler->reconcile($fixture['rails'], $fixture['months'], $fixture['statements'], ['original-a', 'original-b'], $approval))
        ->toThrow(CommandRejection::class, 'STATEMENT_ORIGINAL_REQUIRED');
});

it('rejects missing or inconsistent rail inventories and source provenance', function (string $case, string $reason): void {
    $fixture = StatementFixture::reconciliation();
    switch ($case) {
        case 'no rails': $fixture['rails'] = [];
            break;
        case 'no months': $fixture['months'] = [];
            break;
        case 'too many months': $fixture['months'] = array_fill(0, 37, '2026-08');
            break;
        case 'duplicate month': $fixture['months'][] = '2026-08';
            break;
        case 'gap': $fixture['months'][] = '2026-10';
            break;
        case 'invalid month': $fixture['months'] = ['2026-13'];
            break;
        case 'unparseable month': $fixture['months'] = ['August'];
            break;
        case 'invalid rail id': $fixture['rails'][0]['id'] = 'bank/a';
            break;
        case 'invalid activation': $fixture['rails'][0]['active_from'] = '2026-00';
            break;
        case 'invalid closure': $fixture['rails'][0]['active_until'] = '2026-00';
            break;
        case 'duplicate rail': $fixture['rails'][] = $fixture['rails'][0];
            break;
        case 'closure before activation': $fixture['rails'][0]['active_until'] = '2026-07';
            break;
        case 'duplicate statement': $fixture['statements'][] = $fixture['statements'][0];
            break;
        case 'unknown rail': $fixture['statements'][0]['rail_id'] = 'not-declared';
            break;
        case 'outside window': $fixture['statements'][0]['month'] = '2026-07';
            break;
        case 'missing rail month': array_pop($fixture['statements']);
            break;
        case 'no active rail':
            $fixture['rails'] = [['id' => 'bank-a', 'active_from' => '2026-09', 'active_until' => null]];
            $fixture['statements'] = [];
            break;
        case 'no originals': $fixture['statements'][0]['source_ids'] = [];
            break;
        case 'unknown original': $fixture['statements'][0]['source_ids'] = ['unavailable-original'];
            break;
        case 'missing transaction original': $fixture['statements'][0]['transactions'][0]['source_ids'] = [];
            break;
        case 'transaction outside statement originals': $fixture['statements'][0]['transactions'][0]['source_ids'] = ['original-b'];
            break;
    }
    expect(fn () => (new StatementReconciliation)->reconcile($fixture['rails'], $fixture['months'], $fixture['statements'], $fixture['sources']))
        ->toThrow(CommandRejection::class, $reason);
})->with([
    ['no rails', 'STATEMENT_INVENTORY_REQUIRED'], ['no months', 'STATEMENT_INVENTORY_REQUIRED'],
    ['too many months', 'STATEMENT_INVENTORY_REQUIRED'], ['duplicate month', 'STATEMENT_INVENTORY_REQUIRED'],
    ['gap', 'STATEMENT_MONTH_GAP'], ['invalid month', 'STATEMENT_MONTH_INVALID'], ['unparseable month', 'STATEMENT_MONTH_INVALID'],
    ['invalid rail id', 'STATEMENT_REFERENCE_INVALID'], ['invalid activation', 'STATEMENT_MONTH_INVALID'], ['invalid closure', 'STATEMENT_MONTH_INVALID'],
    ['duplicate rail', 'STATEMENT_RAIL_CONFLICT'], ['closure before activation', 'STATEMENT_RAIL_CONFLICT'],
    ['duplicate statement', 'STATEMENT_COVERAGE_CONFLICT'], ['unknown rail', 'STATEMENT_COVERAGE_CONFLICT'], ['outside window', 'STATEMENT_COVERAGE_CONFLICT'],
    ['missing rail month', 'STATEMENT_RAIL_MONTH_REQUIRED'], ['no active rail', 'STATEMENT_RAIL_MONTH_REQUIRED'],
    ['no originals', 'STATEMENT_ORIGINAL_REQUIRED'], ['unknown original', 'STATEMENT_ORIGINAL_REQUIRED'],
    ['missing transaction original', 'STATEMENT_ORIGINAL_REQUIRED'], ['transaction outside statement originals', 'STATEMENT_ORIGINAL_REQUIRED'],
]);

it('refuses noncanonical amounts before arithmetic', function (string $amount, string $field): void {
    $fixture = StatementFixture::reconciliation();
    if ($field === 'transaction') {
        $fixture['statements'][0]['transactions'][0]['amount'] = $amount;
    } elseif ($field === 'opening_balance') {
        $fixture['statements'][0]['opening_balance'] = $amount;
    } else {
        $fixture['statements'][0]['closing_balance'] = $amount;
    }
    expect(fn () => (new StatementReconciliation)->reconcile($fixture['rails'], $fixture['months'], $fixture['statements'], $fixture['sources']))
        ->toThrow(CommandRejection::class, 'STATEMENT_AMOUNT_INVALID');
})->with(['', '+1', '-0', '01', '1.5', '1e3', '1,000', '10000000000000000000000000000000000000000000000000000000000000000'])
    ->with(['opening_balance', 'closing_balance', 'transaction']);

it('refuses unexplained differences and ambiguous or wrongly classified transactions', function (string $case, string $reason): void {
    $fixture = StatementFixture::reconciliation();
    switch ($case) {
        case 'one rwf difference': $fixture['statements'][0]['closing_balance'] = '2951';
            break;
        case 'invalid reference': $fixture['statements'][0]['transactions'][0]['reference'] = '';
            break;
        case 'invalid date': $fixture['statements'][0]['transactions'][0]['date'] = '2026-08-32';
            break;
        case 'unparseable date': $fixture['statements'][0]['transactions'][0]['date'] = 'today';
            break;
        case 'outside month': $fixture['statements'][0]['transactions'][0]['date'] = '2026-09-01';
            break;
        case 'unknown classification': $fixture['statements'][0]['transactions'][0]['classification'] = 'unclassified';
            break;
        case 'negative inflow': $fixture['statements'][0]['transactions'][0]['amount'] = '-1000';
            break;
        case 'positive outflow': $fixture['statements'][0]['transactions'][1]['amount'] = '200';
            break;
        case 'return reference on inflow': $fixture['statements'][0]['transactions'][0]['return_of'] = ['rail_id' => 'bank-a', 'month' => '2026-08', 'reference' => 'draw'];
            break;
        case 'exception on inflow': $fixture['statements'][0]['transactions'][0]['exception_id'] = 'resolution-original';
            break;
        case 'duplicate with changed amount':
        case 'duplicate with changed classification':
            $duplicate = $fixture['statements'][0]['transactions'][0];
            $duplicate[$case === 'duplicate with changed amount' ? 'amount' : 'classification'] = $case === 'duplicate with changed amount' ? '999' : 'financing';
            $fixture['statements'][0]['transactions'][] = $duplicate;
            break;
    }
    expect(fn () => (new StatementReconciliation)->reconcile($fixture['rails'], $fixture['months'], $fixture['statements'], $fixture['sources']))
        ->toThrow(CommandRejection::class, $reason);
})->with([
    ['one rwf difference', 'STATEMENT_RECONCILIATION_DIFFERENCE'], ['invalid reference', 'STATEMENT_REFERENCE_INVALID'],
    ['invalid date', 'STATEMENT_TRANSACTION_DATE_INVALID'], ['unparseable date', 'STATEMENT_TRANSACTION_DATE_INVALID'], ['outside month', 'STATEMENT_TRANSACTION_DATE_INVALID'],
    ['unknown classification', 'STATEMENT_CLASSIFICATION_REQUIRED'], ['negative inflow', 'STATEMENT_CLASSIFICATION_REQUIRED'], ['positive outflow', 'STATEMENT_CLASSIFICATION_REQUIRED'],
    ['return reference on inflow', 'STATEMENT_CLASSIFICATION_REQUIRED'], ['exception on inflow', 'STATEMENT_CLASSIFICATION_REQUIRED'],
    ['duplicate with changed amount', 'STATEMENT_DUPLICATE_CONFLICT'], ['duplicate with changed classification', 'STATEMENT_DUPLICATE_CONFLICT'],
]);

it('requires attributable same-month returns and refuses total returns above the actual draw', function (string $case, string $reason): void {
    $fixture = StatementFixture::reconciliation();
    $reference = ['rail_id' => 'bank-a', 'month' => '2026-08', 'reference' => 'draw'];
    switch ($case) {
        case 'missing reference': $fixture['statements'][0]['transactions'][5]['return_of'] = null;
            break;
        case 'future reference': $fixture['statements'][0]['transactions'][5]['return_of'] = [...$reference, 'month' => '2026-09'];
            break;
        case 'unknown draw': $fixture['statements'][0]['transactions'][5]['return_of'] = [...$reference, 'reference' => 'missing'];
            break;
        case 'malformed rail': $fixture['statements'][0]['transactions'][5]['return_of'] = [...$reference, 'rail_id' => 'bank/a'];
            break;
        case 'malformed reference': $fixture['statements'][0]['transactions'][5]['return_of'] = [...$reference, 'reference' => ''];
            break;
        case 'negative return': $fixture['statements'][0]['transactions'][5]['amount'] = '-100';
            break;
        case 'excess across returns':
            $fixture['statements'][0]['transactions'][] = StatementFixture::transaction('second-return', '201', 'owner_return', returnOf: $reference);
            $fixture['statements'][0]['closing_balance'] = '3151';
            break;
    }
    expect(fn () => (new StatementReconciliation)->reconcile($fixture['rails'], $fixture['months'], $fixture['statements'], $fixture['sources']))
        ->toThrow(CommandRejection::class, $reason);
})->with([
    ['missing reference', 'OWNER_RETURN_REFERENCE_REQUIRED'], ['future reference', 'OWNER_RETURN_REFERENCE_REQUIRED'], ['unknown draw', 'OWNER_RETURN_REFERENCE_REQUIRED'],
    ['malformed rail', 'STATEMENT_REFERENCE_INVALID'], ['malformed reference', 'STATEMENT_REFERENCE_INVALID'], ['negative return', 'STATEMENT_CLASSIFICATION_REQUIRED'],
    ['excess across returns', 'OWNER_RETURN_EXCEEDS_DRAW'],
]);

it('refuses cash-flow inflation through an unmatched transfer or a debt repayment disguised as financing', function (string $case, string $reason): void {
    $fixture = StatementFixture::reconciliation();
    switch ($case) {
        case 'expense hidden as transfer':
            $fixture['statements'][0]['transactions'][1]['classification'] = 'transfer';
            break;
        case 'debt hidden as financing':
            $fixture['statements'][0]['transactions'][6]['classification'] = 'financing';
            break;
        case 'transferred sales counted twice':
            $fixture['statements'][1]['transactions'][0]['classification'] = 'operating_inflow';
            $fixture['statements'][1]['transactions'][0]['transfer_of'] = null;
            break;
        case 'unmatched amount':
            $fixture['statements'][1]['transactions'][0]['amount'] = '501';
            $fixture['statements'][1]['closing_balance'] = '401';
            break;
        case 'counterpart points elsewhere':
            $fixture['statements'][1]['transactions'][0]['transfer_of'] = ['rail_id' => 'bank-a', 'month' => '2026-08', 'reference' => 'another-transfer'];
            break;
        case 'same rail':
            $fixture['statements'][0]['transactions'][3]['transfer_of'] = ['rail_id' => 'bank-a', 'month' => '2026-08', 'reference' => 'transfer-in'];
            break;
        case 'outside observed window':
            $fixture['statements'][0]['transactions'][3]['transfer_of'] = ['rail_id' => 'momo-b', 'month' => '2026-09', 'reference' => 'transfer-in'];
            break;
        case 'undeclared rail':
            $fixture['statements'][0]['transactions'][3]['transfer_of'] = ['rail_id' => 'undeclared', 'month' => '2026-08', 'reference' => 'transfer-in'];
            break;
        case 'reference on nontransfer':
            $fixture['statements'][0]['transactions'][0]['transfer_of'] = ['rail_id' => 'momo-b', 'month' => '2026-08', 'reference' => 'transfer-in'];
            break;
        case 'duplicate changes counterpart':
            $duplicate = $fixture['statements'][0]['transactions'][3];
            $duplicate['transfer_of'] = ['rail_id' => 'momo-b', 'month' => '2026-08', 'reference' => 'different'];
            $fixture['statements'][0]['transactions'][] = $duplicate;
            break;
    }
    expect(fn () => (new StatementReconciliation)->reconcile($fixture['rails'], $fixture['months'], $fixture['statements'], $fixture['sources']))
        ->toThrow(CommandRejection::class, $reason);
})->with([
    ['expense hidden as transfer', 'STATEMENT_TRANSFER_COUNTERPART_REQUIRED'], ['debt hidden as financing', 'STATEMENT_CLASSIFICATION_REQUIRED'],
    ['transferred sales counted twice', 'STATEMENT_TRANSFER_COUNTERPART_REQUIRED'], ['unmatched amount', 'STATEMENT_TRANSFER_COUNTERPART_REQUIRED'],
    ['counterpart points elsewhere', 'STATEMENT_TRANSFER_COUNTERPART_REQUIRED'], ['same rail', 'STATEMENT_TRANSFER_COUNTERPART_REQUIRED'],
    ['outside observed window', 'STATEMENT_TRANSFER_COUNTERPART_REQUIRED'], ['undeclared rail', 'STATEMENT_TRANSFER_COUNTERPART_REQUIRED'],
    ['reference on nontransfer', 'STATEMENT_CLASSIFICATION_REQUIRED'], ['duplicate changes counterpart', 'STATEMENT_DUPLICATE_CONFLICT'],
]);

it('matches a transfer across the year boundary and opens the receiving rail at zero', function (): void {
    $rails = [
        ['id' => 'bank', 'active_from' => '2026-12', 'active_until' => null],
        ['id' => 'momo', 'active_from' => '2027-01', 'active_until' => null],
    ];
    $statements = [
        ['rail_id' => 'bank', 'month' => '2026-12', 'opening_balance' => '1000', 'closing_balance' => '500', 'source_ids' => ['original-a'],
            'transactions' => [StatementFixture::transaction('sent', '-500', 'transfer', '2026-12-31', transferOf: ['rail_id' => 'momo', 'month' => '2027-01', 'reference' => 'received'])]],
        ['rail_id' => 'bank', 'month' => '2027-01', 'opening_balance' => '500', 'closing_balance' => '500', 'source_ids' => ['original-a'], 'transactions' => []],
        ['rail_id' => 'momo', 'month' => '2027-01', 'opening_balance' => '0', 'closing_balance' => '500', 'source_ids' => ['original-b'],
            'transactions' => [StatementFixture::transaction('received', '500', 'transfer', '2027-01-01', 'original-b', transferOf: ['rail_id' => 'bank', 'month' => '2026-12', 'reference' => 'sent'])]],
    ];
    $reconciler = new StatementReconciliation;
    $result = $reconciler->reconcile($rails, ['2027-01', '2026-12'], $statements, ['original-a', 'original-b']);
    expect(array_column($result, 'operating_inflow'))->toBe(['0', '0'])
        ->and(array_column($result, 'transfer_inflow'))->toBe(['0', '500'])
        ->and(array_column($result, 'transfer_outflow'))->toBe(['500', '0']);
    $statements[2]['opening_balance'] = '1';
    $statements[2]['closing_balance'] = '501';
    expect(fn () => $reconciler->reconcile($rails, ['2026-12', '2027-01'], $statements, ['original-a', 'original-b']))
        ->toThrow(CommandRejection::class, 'STATEMENT_RAIL_BOUNDARY_BALANCE_REQUIRED');
});

it('refuses to close a rail with an unexplained remaining balance', function (): void {
    $fixture = StatementFixture::reconciliation();
    $fixture['rails'][1]['active_until'] = '2026-08';
    expect(fn () => (new StatementReconciliation)->reconcile($fixture['rails'], $fixture['months'], $fixture['statements'], $fixture['sources']))
        ->toThrow(CommandRejection::class, 'STATEMENT_RAIL_BOUNDARY_BALANCE_REQUIRED');
});

it('retains both original references for an economic duplicate without doubling revenue', function (): void {
    $fixture = StatementFixture::reconciliation();
    $fixture['statements'][0]['source_ids'][] = 'original-b';
    $duplicate = $fixture['statements'][0]['transactions'][0];
    $duplicate['source_ids'] = ['original-b'];
    $fixture['statements'][0]['transactions'][] = $duplicate;
    $result = (new StatementReconciliation)->reconcile($fixture['rails'], $fixture['months'], $fixture['statements'], $fixture['sources']);
    expect($result[0]['operating_inflow'])->toBe('1000')->and($result[0]['source_ids'])->toBe(['original-a', 'original-b']);
});

it('accepts zero entries in each classification without inventing income or deductions', function (): void {
    $fixture = StatementFixture::reconciliation();
    foreach ($fixture['statements'] as &$statement) {
        $statement['opening_balance'] = $statement['closing_balance'] = '0';
        foreach ($statement['transactions'] as &$transaction) {
            $transaction['amount'] = '0';
        }
        unset($transaction);
    }
    unset($statement);
    $result = (new StatementReconciliation)->reconcile($fixture['rails'], $fixture['months'], $fixture['statements'], $fixture['sources'])[0];
    foreach (['operating_inflow', 'operating_outflow', 'owner_draw', 'debt_service', 'financing_inflow', 'transfer_inflow', 'transfer_outflow'] as $field) {
        expect($result[$field])->toBe('0');
    }
});

it('reconciles exactly thirty-six consecutive months and preserves an overdraft across year boundaries', function (): void {
    $months = $statements = [];
    $start = new DateTimeImmutable('2024-01-01');
    for ($index = 0; $index < 36; $index++) {
        $month = $start->modify('+'.$index.' months')->format('Y-m');
        $months[] = $month;
        $statements[] = ['rail_id' => 'bank', 'month' => $month, 'opening_balance' => '-500', 'closing_balance' => '-500',
            'source_ids' => ['original-a'], 'transactions' => []];
    }
    $result = (new StatementReconciliation)->reconcile([['id' => 'bank', 'active_from' => '2023-12', 'active_until' => null]], $months, $statements, ['original-a']);
    expect($result)->toHaveCount(36)->and($result[35]['month'])->toBe('2026-12')->and(array_unique(array_column($result, 'operating_inflow')))->toBe(['0']);
});

it('validates actual leap days instead of normalizing invalid dates', function (string $month, bool $valid): void {
    $statement = ['rail_id' => 'bank', 'month' => $month, 'opening_balance' => '0', 'closing_balance' => '10', 'source_ids' => ['original-a'],
        'transactions' => [StatementFixture::transaction('sale', '10', 'operating_inflow', $month.'-29')]];
    $reconcile = fn (): array => (new StatementReconciliation)->reconcile([['id' => 'bank', 'active_from' => '2027-01', 'active_until' => null]], [$month], [$statement], ['original-a']);
    if ($valid) {
        expect($reconcile()[0]['operating_inflow'])->toBe('10');
    } else {
        expect($reconcile)->toThrow(CommandRejection::class, 'STATEMENT_TRANSACTION_DATE_INVALID');
    }
})->with([['2028-02', true], ['2027-02', false]]);
