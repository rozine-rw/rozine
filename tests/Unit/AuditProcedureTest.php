<?php

declare(strict_types=1);

use App\Domain\Auditor\AuditProcedure;
use App\Domain\Operations\CommandRejection;

/**
 * @return array{verification: array{id: string, revision: int, sha256: string}, check_in: array{id: string, revision: int, sha256: string}, photos: array{id: string, revision: int, sha256: string}, declaration: array{id: string, revision: int, sha256: string}, reported_stock: string, reported_cash: string, reported_units: string, financial_proofs: list<string>, inventory_proofs: list<string>, extra_photos: list<string>}
 */
function procedureSources(): array
{
    return ['verification' => procedureSource('verification'), 'check_in' => procedureSource('check_in'),
        'photos' => procedureSource('photos'), 'declaration' => procedureSource('declaration'),
        'reported_stock' => '38000000', 'reported_cash' => '1210000', 'reported_units' => '190',
        'financial_proofs' => ['bank', 'cash'], 'inventory_proofs' => ['photo'], 'extra_photos' => ['extra']];
}

/** @return array{id: string, revision: int, sha256: string} */
function procedureSource(string $key): array
{
    return ['id' => $key.'-source', 'revision' => 1, 'sha256' => hash('sha256', $key)];
}

/** @return array<string, mixed> */
function procedureFields(string $step): array
{
    return match ($step) {
        'ledger' => ['observed_stock' => '36400000', 'reconciled' => true],
        'count' => ['cash' => '1184000', 'stock_units' => '184', 'operational_status' => 'active', 'financial_proofs' => ['cash', 'bank'], 'inventory_proofs' => ['photo']],
        'history' => ['history_checked' => true],
        'premises' => ['premises_tenure' => 'rented', 'assets' => [['item' => 'Delivery motorbike', 'value' => '1800000']]],
        'people' => ['owners_confirmed' => true, 'management_confirmed' => true],
        'registration' => ['registration_confirmed' => true, 'tax_clearance_expires_on' => '2027-03-31'],
        'trading' => ['trading_since' => '2021-06'],
        'available' => ['cash_available' => '450000', 'financial_proofs' => ['cash', 'bank']],
        'reason' => ['late_reason' => 'customers_owe', 'late_reason_note' => ''],
        'cash_sales' => ['undeposited_cash_sales' => '15000'],
        'costs' => ['costs' => ['stock' => '400000', 'staff' => '250000', 'premises' => '80000', 'transport' => '30000', 'taxes' => '20000', 'other' => '0']],
        'drawings' => ['owner_drawings' => '150000'],
        'loans' => ['loans' => [['lender' => 'Bank of Kigali', 'outstanding' => '2400000', 'monthly_payment' => '200000']]],
        'differences' => ['explanations' => [['cause' => 'entry_error', 'amount' => '6000'], ['cause' => 'personal_spending', 'amount' => '20000']]],
        'staff' => ['staff_count' => '6', 'rssb_checked' => true],
        'expiries' => ['tax_clearance_expires_on' => '2027-03-31', 'rssb_clearance_expires_on' => '2027-06-30'],
        'loan_use' => ['loan_use' => 'as_planned', 'loan_use_note' => ''],
        'changes' => ['changes' => ['new_debt' => false, 'director_change' => false, 'ownership_change' => false, 'legal_claim' => false]],
        'photos' => ['titles' => ['extra' => 'Side store']],
        'seal' => ['note' => 'A physical stock difference was observed.'],
        default => [],
    };
}

/** @return array{step: string, draft: array{note: string, completed_steps: list<string>, fields: array<string, array<string, mixed>>, sources?: array<string, array<string, array{id: string, revision: int, sha256: string}>>}} */
function procedureBefore(string $kind, string $target): array
{
    $procedure = new AuditProcedure;
    $state = ['step' => $procedure->steps($kind)[0], 'draft' => ['note' => '', 'completed_steps' => [], 'fields' => []]];
    foreach ($procedure->steps($kind) as $step) {
        if ($step === $target) {
            break;
        }
        $state = $procedure->save($kind, $state['step'], $state['draft'], $step, procedureFields($step), procedureSources());
    }

    return $state;
}

it('records every procedure step with immutable source references and persists the note before a seal', function (string $kind): void {
    $procedure = new AuditProcedure;
    $state = procedureBefore($kind, 'seal');
    $saved = $procedure->save($kind, 'seal', $state['draft'], 'seal', procedureFields('seal'), procedureSources());
    expect($saved['step'])->toBe('seal')->and($saved['draft']['note'])->toBe(procedureFields('seal')['note'])
        ->and($saved['draft']['completed_steps'])->toBe(array_slice($procedure->steps($kind), 0, -1))
        ->and($saved['draft']['sources']['photos']['photos']['sha256'] ?? null)->toBe(hash('sha256', 'photos'))
        ->and(json_encode($saved))->not->toContain('rating', 'capacity', 'yield', 'credit_verdict');
    if ($kind !== 'monthly') {
        expect($saved['draft']['fields']['ledger'])->toBe(['observed_stock' => '36400000', 'reconciled' => true]);
    } else {
        expect($saved['draft']['fields']['count']['financial_proofs'])->toBe(['bank', 'cash']);
    }
})->with(['flash', 'baseline', 'monthly']);

it('invalidates later work and the persisted note when a prior step is edited', function (): void {
    $procedure = new AuditProcedure;
    $state = procedureBefore('flash', 'seal');
    $state['draft']['note'] = 'Previous finding';
    $saved = $procedure->save('flash', 'seal', $state['draft'], 'check_in', [], procedureSources());
    expect($saved['step'])->toBe('photos')->and($saved['draft']['completed_steps'])->toBe(['review', 'check_in'])
        ->and($saved['draft']['fields'])->toHaveKeys(['review', 'check_in'])->not->toHaveKeys(['photos', 'ledger'])
        ->and($saved['draft']['sources'] ?? [])->not->toHaveKeys(['photos', 'ledger'])->and($saved['draft']['note'])->toBe('');
});

it('allows read-only navigation to current and completed steps without allowing a future step', function (): void {
    $procedure = new AuditProcedure;
    expect($procedure->selectStep('flash', 'ledger', null))->toBe('ledger')
        ->and($procedure->selectStep('flash', 'ledger', 'check_in'))->toBe('check_in')
        ->and($procedure->selectStep('monthly', 'count', 'statements'))->toBe('statements');
    foreach ([['flash', 'review', 'seal'], ['flash', 'ledger', 'count'], ['monthly', 'unknown', null]] as [$kind, $current, $step]) {
        expect(fn () => $procedure->selectStep($kind, $current, $step))->toThrow(CommandRejection::class, 'AUDIT_STEP_NOT_AVAILABLE');
    }
});

it('refuses steps that bypass the ordered procedure', function (string $kind, string $current, string $requested, string $code): void {
    expect(fn () => (new AuditProcedure)->save($kind, $current, ['note' => '', 'completed_steps' => [], 'fields' => []], $requested, [], procedureSources()))
        ->toThrow(CommandRejection::class, $code);
})->with([
    ['unknown', 'review', 'review', 'AUDIT_PROCEDURE_INVALID'],
    ['flash', 'review', 'seal', 'AUDIT_STEP_NOT_AVAILABLE'],
    ['monthly', 'statements', 'ledger', 'AUDIT_STEP_NOT_AVAILABLE'],
    ['flash', 'unknown', 'review', 'AUDIT_STEP_NOT_AVAILABLE'],
    ['flash', 'seal', 'seal', 'AUDIT_PROCEDURE_INCOMPLETE'],
]);

it('requires the same protected sources for completed steps before continuing', function (string $change): void {
    $state = procedureBefore('monthly', 'count');
    $sources = procedureSources();
    $sources['verification'] = match ($change) {
        'revision' => [...$sources['verification'], 'revision' => 2],
        'id' => [...$sources['verification'], 'id' => 'changed'],
        default => [...$sources['verification'], 'sha256' => 'changed'],
    };
    expect(fn () => (new AuditProcedure)->save('monthly', 'count', $state['draft'], 'count', procedureFields('count'), $sources))
        ->toThrow(CommandRejection::class, 'AUDIT_PROCEDURE_SOURCE_CHANGED');
})->with(['id', 'revision', 'sha256']);

it('does not replace unavailable source facts with zeros or client assertions', function (string $kind, string $step, string $source, string $code): void {
    $state = procedureBefore($kind, $step);
    $sources = procedureSources();
    $sources = match ($source) {
        'verification' => [...$sources, 'verification' => null],
        'check_in' => [...$sources, 'check_in' => null],
        'photos' => [...$sources, 'photos' => null],
        'declaration' => [...$sources, 'declaration' => null],
        'reported_stock' => [...$sources, 'reported_stock' => null],
        'reported_cash' => [...$sources, 'reported_cash' => null],
        'reported_units' => [...$sources, 'reported_units' => null],
        default => throw new LogicException('Unknown fixture source'),
    };
    expect(fn () => (new AuditProcedure)->save($kind, $step, $state['draft'], $step, procedureFields($step), $sources))
        ->toThrow(CommandRejection::class, $code);
})->with([
    ['flash', 'check_in', 'check_in', 'AUDIT_CAPTURE_REQUIRED'],
    ['flash', 'photos', 'photos', 'AUDIT_CAPTURE_REQUIRED'],
    ['flash', 'ledger', 'verification', 'AUDIT_VERIFIED_STATEMENTS_REQUIRED'],
    ['flash', 'ledger', 'declaration', 'AUDIT_DECLARATION_REQUIRED'],
    ['flash', 'ledger', 'reported_stock', 'AUDIT_DECLARATION_REQUIRED'],
    ['monthly', 'statements', 'verification', 'AUDIT_VERIFIED_STATEMENTS_REQUIRED'],
    ['monthly', 'count', 'reported_cash', 'AUDIT_DECLARATION_REQUIRED'],
    ['monthly', 'count', 'reported_units', 'AUDIT_DECLARATION_REQUIRED'],
]);

it('rejects invalid or server-owned form fields', function (string $step, string $field, mixed $value, string $code): void {
    $kind = $step === 'count' ? 'monthly' : 'flash';
    $state = procedureBefore($kind, $step);
    $fields = procedureFields($step);
    $fields[$field] = $value;
    expect(fn () => (new AuditProcedure)->save($kind, $step, $state['draft'], $step, $fields, procedureSources()))
        ->toThrow(CommandRejection::class, $code);
})->with([
    ['review', 'rating', 'A', 'AUDIT_STEP_INPUT_INVALID'],
    ['ledger', 'variance', ['within' => true], 'AUDIT_STEP_INPUT_INVALID'],
    ['ledger', 'observed_stock', 12, 'AUDIT_STEP_INPUT_INVALID'],
    ['ledger', 'observed_stock', '1.5', 'AUDIT_STEP_INPUT_INVALID'],
    ['ledger', 'observed_stock', '-1', 'AUDIT_STEP_INPUT_INVALID'],
    ['ledger', 'reconciled', 'true', 'AUDIT_RECONCILIATION_REQUIRED'],
    ['count', 'cash', '', 'AUDIT_STEP_INPUT_INVALID'],
    ['count', 'stock_units', '1e9', 'AUDIT_STEP_INPUT_INVALID'],
    ['count', 'operational_status', 'approved', 'AUDIT_STEP_INPUT_INVALID'],
    ['count', 'financial_proofs', true, 'AUDIT_STEP_INPUT_INVALID'],
    ['count', 'financial_proofs', ['bank', 2], 'AUDIT_STEP_INPUT_INVALID'],
    ['count', 'financial_proofs', ['key' => 'bank'], 'AUDIT_STEP_INPUT_INVALID'],
    ['count', 'financial_proofs', ['bank'], 'AUDIT_PROOFS_REQUIRED'],
    ['count', 'financial_proofs', ['cash', 'bank', 'bank'], 'AUDIT_PROOFS_REQUIRED'],
    ['count', 'inventory_proofs', [], 'AUDIT_PROOFS_REQUIRED'],
    ['photos', 'titles', 'wrong', 'AUDIT_STEP_INPUT_INVALID'],
    ['photos', 'titles', ['unknown' => 'Not received'], 'AUDIT_STEP_INPUT_INVALID'],
    ['photos', 'titles', [], 'AUDIT_STEP_INPUT_INVALID'],
    ['seal', 'note', str_repeat('a', 101), 'AUDIT_STEP_INPUT_INVALID'],
    ['seal', 'note', "Bad\0text", 'AUDIT_STEP_INPUT_INVALID'],
    ['seal', 'note', "\xff", 'AUDIT_STEP_INPUT_INVALID'],
    ['seal', 'note', null, 'AUDIT_STEP_INPUT_INVALID'],
]);

it('retains exact large integers and allows an empty optional note', function (): void {
    $procedure = new AuditProcedure;
    $state = procedureBefore('flash', 'ledger');
    $state = $procedure->save('flash', 'ledger', $state['draft'], 'ledger', ['observed_stock' => str_repeat('9', 64), 'reconciled' => true], procedureSources());
    expect($state['draft']['fields']['ledger']['observed_stock'])->toBe(str_repeat('9', 64));
    $state = $procedure->save('flash', 'seal', $state['draft'], 'seal', ['note' => ''], procedureSources());
    expect($state['draft']['note'])->toBe('');
});

it('projects source readiness without requiring unsaved form fields', function (string $kind): void {
    $procedure = new AuditProcedure;
    foreach ($procedure->steps($kind) as $step) {
        $state = procedureBefore($kind, $step);
        expect($procedure->unavailable($kind, $step, $state['draft'], $step, procedureSources()))->toBeNull();
    }
})->with(['flash', 'monthly']);

it('makes the read gate explain invalid, incomplete and changed source states', function (): void {
    $procedure = new AuditProcedure;
    $draft = ['note' => '', 'completed_steps' => [], 'fields' => []];
    $sources = procedureSources();
    expect($procedure->unavailable('unknown', 'review', $draft, 'review', $sources))->toBe('AUDIT_PROCEDURE_INVALID')
        ->and($procedure->unavailable('flash', 'review', $draft, 'unknown', $sources))->toBe('AUDIT_STEP_NOT_AVAILABLE')
        ->and($procedure->unavailable('flash', 'unknown', $draft, 'review', $sources))->toBe('AUDIT_STEP_NOT_AVAILABLE')
        ->and($procedure->unavailable('flash', 'review', $draft, 'seal', $sources))->toBe('AUDIT_STEP_NOT_AVAILABLE')
        ->and($procedure->unavailable('flash', 'seal', $draft, 'seal', $sources))->toBe('AUDIT_PROCEDURE_INCOMPLETE');
    $state = procedureBefore('monthly', 'count');
    expect($procedure->unavailable('monthly', 'count', $state['draft'], 'count', [...$sources, 'verification' => null]))->toBe('AUDIT_VERIFIED_STATEMENTS_REQUIRED')
        ->and($procedure->unavailable('monthly', 'count', $state['draft'], 'count', [...$sources, 'reported_cash' => null]))->toBe('AUDIT_DECLARATION_REQUIRED')
        ->and($procedure->unavailable('monthly', 'count', $state['draft'], 'count', [...$sources, 'reported_units' => null]))->toBe('AUDIT_DECLARATION_REQUIRED')
        ->and($procedure->unavailable('monthly', 'count', $state['draft'], 'count', [...$sources, 'verification' => [...$sources['verification'], 'revision' => 2]]))
        ->toBe('AUDIT_PROCEDURE_SOURCE_CHANGED');
    $state = procedureBefore('flash', 'ledger');
    expect($procedure->unavailable('flash', 'ledger', $state['draft'], 'ledger', [...$sources, 'reported_stock' => null]))->toBe('AUDIT_DECLARATION_REQUIRED');
});

it('withdraws count readiness when required proof lists are unavailable', function (string $key): void {
    $state = procedureBefore('monthly', 'count');
    $sources = procedureSources();
    if ($key === 'financial_proofs') {
        $sources['financial_proofs'] = [];
    } else {
        $sources['inventory_proofs'] = [];
    }
    expect((new AuditProcedure)->unavailable('monthly', 'count', $state['draft'], 'count', $sources))->toBe('AUDIT_PROOFS_REQUIRED');
})->with(['financial_proofs', 'inventory_proofs']);

it('pins the exact active ledger originals and requires review if that set changes', function (): void {
    $procedure = new AuditProcedure;
    $state = procedureBefore('flash', 'ledger');
    $sources = [...procedureSources(), 'ledger_sources' => ['ledger:one' => procedureSource('ledger')]];
    $state = $procedure->save('flash', 'ledger', $state['draft'], 'ledger', procedureFields('ledger'), $sources);
    expect($state['draft']['sources']['ledger']['ledger:one'] ?? null)->toBe(procedureSource('ledger'));
    $sources['ledger_sources']['ledger:one']['sha256'] = 'changed';
    expect($procedure->unavailable('flash', 'seal', $state['draft'], 'seal', $sources))->toBe('AUDIT_PROCEDURE_SOURCE_CHANGED');
});

it('explains the whole monthly cash difference by cause code, to the franc, in the listed cause order', function (): void {
    $procedure = new AuditProcedure;
    $state = procedureBefore('monthly', 'differences');
    expect($state['step'])->toBe('differences')->and($procedure->cashDifference($state['draft'], procedureSources()))->toBe('26000');

    $saved = $procedure->save('monthly', 'differences', $state['draft'], 'differences', ['explanations' => [
        ['amount' => '20000', 'cause' => 'personal_spending'], ['cause' => 'unrecorded_sales', 'amount' => '6000']]], procedureSources());
    expect($saved['step'])->toBe('staff')
        ->and($saved['draft']['fields']['differences']['explanations'])->toBe([['cause' => 'unrecorded_sales', 'amount' => '6000'], ['cause' => 'personal_spending', 'amount' => '20000']])
        ->and($saved['draft']['sources']['differences'] ?? null)->toBe(['verification' => procedureSource('verification'), 'declaration' => procedureSource('declaration')]);
});

it('records no explanation for a month whose count matches the statements', function (): void {
    $procedure = new AuditProcedure;
    $state = procedureBefore('monthly', 'differences');
    $sources = [...procedureSources(), 'reported_cash' => '1184000'];
    expect($procedure->cashDifference($state['draft'], $sources))->toBe('0')
        ->and($procedure->save('monthly', 'differences', $state['draft'], 'differences', ['explanations' => []], $sources)['draft']['fields']['differences'])
        ->toBe(['explanations' => []])
        ->and(fn () => $procedure->save('monthly', 'differences', $state['draft'], 'differences', ['explanations' => [['cause' => 'entry_error', 'amount' => '1']]], $sources))
        ->toThrow(CommandRejection::class, 'AUDIT_DIFFERENCE_UNEXPLAINED');
});

it('refuses explanations that leave a franc unexplained, repeat a cause or are not plain amounts', function (mixed $explanations, string $code): void {
    $procedure = new AuditProcedure;
    $state = procedureBefore('monthly', 'differences');
    try {
        $procedure->save('monthly', 'differences', $state['draft'], 'differences', ['explanations' => $explanations], procedureSources());
        $this->fail('The explanations were accepted.');
    } catch (CommandRejection $rejection) {
        expect($rejection->reason)->toBe($code)->and($rejection->fieldErrors)->toHaveKey('explanations');
    }
})->with([
    'one franc short' => [[['cause' => 'entry_error', 'amount' => '25999']], 'AUDIT_DIFFERENCE_UNEXPLAINED'],
    'one franc over' => [[['cause' => 'entry_error', 'amount' => '26001']], 'AUDIT_DIFFERENCE_UNEXPLAINED'],
    'nothing explained' => [[], 'AUDIT_DIFFERENCE_UNEXPLAINED'],
    'a repeated cause' => [[['cause' => 'entry_error', 'amount' => '13000'], ['cause' => 'entry_error', 'amount' => '13000']], 'AUDIT_STEP_INPUT_INVALID'],
    'an unknown cause' => [[['cause' => 'theft', 'amount' => '26000']], 'AUDIT_STEP_INPUT_INVALID'],
    'a zero amount' => [[['cause' => 'entry_error', 'amount' => '26000'], ['cause' => 'wrong_month', 'amount' => '0']], 'AUDIT_STEP_INPUT_INVALID'],
    'a fractional amount' => [[['cause' => 'entry_error', 'amount' => '26000.0']], 'AUDIT_STEP_INPUT_INVALID'],
    'a numeric amount' => [[['cause' => 'entry_error', 'amount' => 26000]], 'AUDIT_STEP_INPUT_INVALID'],
    'an extra key' => [[['cause' => 'entry_error', 'amount' => '26000', 'note' => 'x']], 'AUDIT_STEP_INPUT_INVALID'],
    'a bare value' => [['entry_error'], 'AUDIT_STEP_INPUT_INVALID'],
    'a keyed map' => [['entry_error' => ['cause' => 'entry_error', 'amount' => '26000']], 'AUDIT_STEP_INPUT_INVALID'],
    'more rows than causes' => [array_fill(0, 7, ['cause' => 'entry_error', 'amount' => '1']), 'AUDIT_STEP_INPUT_INVALID'],
    'not a list' => ['26000', 'AUDIT_STEP_INPUT_INVALID'],
]);

it('withholds the difference step until the statements and declaration it explains are on file', function (): void {
    $procedure = new AuditProcedure;
    $state = procedureBefore('monthly', 'differences');
    $sources = [...procedureSources(), 'reported_cash' => null];
    expect($procedure->unavailable('monthly', 'differences', $state['draft'], 'differences', $sources))->toBe('AUDIT_DECLARATION_REQUIRED')
        ->and($procedure->cashDifference($state['draft'], $sources))->toBeNull()
        ->and($procedure->cashDifference(['note' => '', 'completed_steps' => [], 'fields' => []], procedureSources()))->toBeNull()
        ->and(fn () => $procedure->save('monthly', 'differences', $state['draft'], 'differences', ['explanations' => []], $sources))
        ->toThrow(CommandRejection::class, 'AUDIT_DECLARATION_REQUIRED');
});

it('records owner drawings and other loans after the count, before the differences (BRS v3 VIS-R01 steps 7 and 8)', function (): void {
    $procedure = new AuditProcedure;
    expect(array_slice($procedure->steps('monthly'), 0, 7))->toBe(['statements', 'count', 'cash_sales', 'costs', 'drawings', 'loans', 'differences']);

    $state = procedureBefore('monthly', 'drawings');
    $drawn = $procedure->save('monthly', 'drawings', $state['draft'], 'drawings', ['owner_drawings' => '0'], procedureSources());
    expect($drawn['step'])->toBe('loans')->and($drawn['draft']['fields']['drawings'])->toBe(['owner_drawings' => '0'])
        ->and($drawn['draft']['sources']['drawings'] ?? null)->toBe(['verification' => procedureSource('verification'), 'declaration' => procedureSource('declaration')]);

    $none = $procedure->save('monthly', 'loans', $drawn['draft'], 'loans', ['loans' => []], procedureSources());
    $listed = $procedure->save('monthly', 'loans', $drawn['draft'], 'loans', ['loans' => [
        ['monthly_payment' => '0', 'outstanding' => '500000', 'lender' => '  Umurenge SACCO '],
        ['lender' => 'Family', 'outstanding' => '90000', 'monthly_payment' => '15000'],
    ]], procedureSources());
    expect($none['step'])->toBe('differences')->and($none['draft']['fields']['loans'])->toBe(['loans' => []])
        ->and($listed['draft']['fields']['loans']['loans'])->toBe([
            ['lender' => 'Umurenge SACCO', 'outstanding' => '500000', 'monthly_payment' => '0'],
            ['lender' => 'Family', 'outstanding' => '90000', 'monthly_payment' => '15000'],
        ]);
});

it('refuses owner drawings and loans it cannot record exactly', function (string $step, array $input, string $field, string $message): void {
    $procedure = new AuditProcedure;
    $state = procedureBefore('monthly', $step);

    try {
        $procedure->save('monthly', $step, $state['draft'], $step, $input, procedureSources());
        $this->fail('The step was accepted.');
    } catch (CommandRejection $rejection) {
        expect($rejection->reason)->toBe('AUDIT_STEP_INPUT_INVALID')->and($rejection->fieldErrors)->toBe([$field => [$message]]);
    }
})->with([
    'drawings missing' => ['drawings', [], 'step', 'Send only the fields for this procedure step.'],
    'drawings negative' => ['drawings', ['owner_drawings' => '-1'], 'owner_drawings', 'Use a non-negative whole-number amount.'],
    'drawings as a number' => ['drawings', ['owner_drawings' => 1000], 'owner_drawings', 'Use a non-negative whole-number amount.'],
    'loans not a list' => ['loans', ['loans' => ['a' => []]], 'loans', 'List at most 10 other loans.'],
    'loans too many' => ['loans', ['loans' => array_fill(0, 11, ['lender' => 'Bank', 'outstanding' => '1', 'monthly_payment' => '1'])], 'loans', 'List at most 10 other loans.'],
    'loan not an object' => ['loans', ['loans' => ['Bank']], 'loans', 'Give each loan its lender, the amount still owed and the monthly payment.'],
    'loan with an extra key' => ['loans', ['loans' => [['lender' => 'Bank', 'outstanding' => '1', 'monthly_payment' => '1', 'rate' => '5']]], 'loans', 'Give each loan its lender, the amount still owed and the monthly payment.'],
    'loan repaid' => ['loans', ['loans' => [['lender' => 'Bank', 'outstanding' => '0', 'monthly_payment' => '0']]], 'loans', 'Leave out a loan that is fully repaid.'],
    'loan blank lender' => ['loans', ['loans' => [['lender' => '  ', 'outstanding' => '1', 'monthly_payment' => '0']]], 'loans', 'Use plain text of at most 100 characters.'],
    'loan bad payment' => ['loans', ['loans' => [['lender' => 'Bank', 'outstanding' => '1', 'monthly_payment' => '1.5']]], 'loans', 'Use a non-negative whole-number amount.'],
]);

it('records the staff count, expiry dates, use of the loan money and changes after the differences (BRS v3 VIS-R01 steps 10, 12, 13 and 14)', function (): void {
    $procedure = new AuditProcedure;
    expect($procedure->steps('monthly'))->toBe(['statements', 'count', 'cash_sales', 'costs', 'drawings', 'loans', 'differences', 'staff', 'expiries', 'loan_use', 'changes', 'photos', 'seal']);

    $state = procedureBefore('monthly', 'staff');
    $staff = $procedure->save('monthly', 'staff', $state['draft'], 'staff', ['rssb_checked' => true, 'staff_count' => '0'], procedureSources());
    expect($staff['step'])->toBe('expiries')->and($staff['draft']['fields']['staff'])->toBe(['staff_count' => '0', 'rssb_checked' => true])
        ->and($staff['draft']['sources']['staff'] ?? null)->toBe(['verification' => procedureSource('verification'), 'declaration' => procedureSource('declaration')]);

    // A certificate already expired is recorded as the CPA saw it.
    $expiries = $procedure->save('monthly', 'expiries', $staff['draft'], 'expiries', ['tax_clearance_expires_on' => '2024-02-29', 'rssb_clearance_expires_on' => '2027-12-31'], procedureSources());
    expect($expiries['step'])->toBe('loan_use')->and($expiries['draft']['fields']['expiries'])->toBe(['tax_clearance_expires_on' => '2024-02-29', 'rssb_clearance_expires_on' => '2027-12-31']);

    $planned = $procedure->save('monthly', 'loan_use', $expiries['draft'], 'loan_use', ['loan_use' => 'as_planned', 'loan_use_note' => ''], procedureSources());
    $partly = $procedure->save('monthly', 'loan_use', $expiries['draft'], 'loan_use', ['loan_use' => 'partly', 'loan_use_note' => '  Part paid the rent. '], procedureSources());
    expect($planned['step'])->toBe('changes')->and($planned['draft']['fields']['loan_use'])->toBe(['loan_use' => 'as_planned', 'loan_use_note' => ''])
        ->and($partly['draft']['fields']['loan_use'])->toBe(['loan_use' => 'partly', 'loan_use_note' => 'Part paid the rent.']);

    $changes = $procedure->save('monthly', 'changes', $planned['draft'], 'changes', ['changes' => ['legal_claim' => false, 'ownership_change' => false, 'director_change' => true, 'new_debt' => false]], procedureSources());
    expect($changes['step'])->toBe('photos')
        ->and($changes['draft']['fields']['changes'])->toBe(['changes' => ['new_debt' => false, 'director_change' => true, 'ownership_change' => false, 'legal_claim' => false]]);
});

it('refuses a staff count, expiry date, loan use or change it cannot record exactly', function (string $step, array $input, string $field, string $message): void {
    $procedure = new AuditProcedure;
    $state = procedureBefore('monthly', $step);

    try {
        $procedure->save('monthly', $step, $state['draft'], $step, $input, procedureSources());
        $this->fail('The step was accepted.');
    } catch (CommandRejection $rejection) {
        expect($rejection->reason)->toBe('AUDIT_STEP_INPUT_INVALID')->and($rejection->fieldErrors)->toBe([$field => [$message]]);
    }
})->with([
    'staff missing the RSSB check' => ['staff', ['staff_count' => '6'], 'step', 'Send only the fields for this procedure step.'],
    'staff count not whole' => ['staff', ['staff_count' => '6.5', 'rssb_checked' => true], 'staff_count', 'Use a non-negative whole-number amount.'],
    'staff RSSB not checked' => ['staff', ['staff_count' => '6', 'rssb_checked' => false], 'rssb_checked', 'Check the staff count against the RSSB total page before continuing.'],
    'tax date not a date' => ['expiries', ['tax_clearance_expires_on' => '31/03/2027', 'rssb_clearance_expires_on' => '2027-06-30'], 'tax_clearance_expires_on', 'Use the date on the certificate, as YYYY-MM-DD.'],
    'RSSB date impossible' => ['expiries', ['tax_clearance_expires_on' => '2027-03-31', 'rssb_clearance_expires_on' => '2027-02-30'], 'rssb_clearance_expires_on', 'Use the date on the certificate, as YYYY-MM-DD.'],
    'RSSB date missing' => ['expiries', ['tax_clearance_expires_on' => '2027-03-31', 'rssb_clearance_expires_on' => null], 'rssb_clearance_expires_on', 'Use the date on the certificate, as YYYY-MM-DD.'],
    'loan use unknown' => ['loan_use', ['loan_use' => 'unknown', 'loan_use_note' => ''], 'loan_use', 'Say how the loan money was used.'],
    'loan use unexplained' => ['loan_use', ['loan_use' => 'not_as_planned', 'loan_use_note' => '  '], 'loan_use_note', 'Use plain text of at most 200 characters.'],
    'loan use note too long' => ['loan_use', ['loan_use' => 'partly', 'loan_use_note' => str_repeat('a', 201)], 'loan_use_note', 'Use plain text of at most 200 characters.'],
    'changes not a map' => ['changes', ['changes' => 'no'], 'changes', 'Answer yes or no for each change.'],
    'changes missing one' => ['changes', ['changes' => ['new_debt' => false, 'director_change' => false, 'ownership_change' => false]], 'changes', 'Answer yes or no for each change.'],
    'changes with another' => ['changes', ['changes' => ['new_debt' => false, 'director_change' => false, 'ownership_change' => false, 'other' => false]], 'changes', 'Answer yes or no for each change.'],
    'change not yes or no' => ['changes', ['changes' => ['new_debt' => 'no', 'director_change' => false, 'ownership_change' => false, 'legal_claim' => false]], 'changes', 'Answer yes or no for each change.'],
]);

it('records the cash sales not deposited and the costs in six categories after the count (BRS v3 VIS-R01 steps 4 and 5)', function (): void {
    $procedure = new AuditProcedure;
    expect(array_slice($procedure->steps('monthly'), 1, 4))->toBe(['count', 'cash_sales', 'costs', 'drawings']);

    $state = procedureBefore('monthly', 'cash_sales');
    $sales = $procedure->save('monthly', 'cash_sales', $state['draft'], 'cash_sales', ['undeposited_cash_sales' => '0'], procedureSources());
    expect($sales['step'])->toBe('costs')->and($sales['draft']['fields']['cash_sales'])->toBe(['undeposited_cash_sales' => '0'])
        ->and($sales['draft']['sources']['cash_sales'] ?? null)->toBe(['verification' => procedureSource('verification'), 'declaration' => procedureSource('declaration')]);

    // Sent in any order, the six categories are stored in their listed order.
    $costs = $procedure->save('monthly', 'costs', $sales['draft'], 'costs', ['costs' => ['other' => '0', 'taxes' => '20000', 'transport' => '0',
        'premises' => '80000', 'staff' => '250000', 'stock' => '400000']], procedureSources());
    expect($costs['step'])->toBe('drawings')
        ->and($costs['draft']['fields']['costs'])->toBe(['costs' => ['stock' => '400000', 'staff' => '250000', 'premises' => '80000', 'transport' => '0', 'taxes' => '20000', 'other' => '0']])
        ->and(AuditProcedure::COST_CATEGORIES)->toBe(['stock', 'staff', 'premises', 'transport', 'taxes', 'other']);
});

it('refuses cash sales or costs it cannot record exactly', function (string $step, array $input, string $field, string $message): void {
    $procedure = new AuditProcedure;
    $state = procedureBefore('monthly', $step);

    try {
        $procedure->save('monthly', $step, $state['draft'], $step, $input, procedureSources());
        $this->fail('The step was accepted.');
    } catch (CommandRejection $rejection) {
        expect($rejection->reason)->toBe('AUDIT_STEP_INPUT_INVALID')->and($rejection->fieldErrors)->toBe([$field => [$message]]);
    }
})->with([
    'cash sales not whole' => ['cash_sales', ['undeposited_cash_sales' => '1500.50'], 'undeposited_cash_sales', 'Use a non-negative whole-number amount.'],
    'cash sales negative' => ['cash_sales', ['undeposited_cash_sales' => '-1'], 'undeposited_cash_sales', 'Use a non-negative whole-number amount.'],
    'cash sales missing' => ['cash_sales', [], 'step', 'Send only the fields for this procedure step.'],
    'costs not a map' => ['costs', ['costs' => '700000'], 'costs', 'Enter an amount for each of the six cost categories.'],
    'costs missing one' => ['costs', ['costs' => ['stock' => '1', 'staff' => '1', 'premises' => '1', 'transport' => '1', 'taxes' => '1']], 'costs', 'Enter an amount for each of the six cost categories.'],
    'costs with another' => ['costs', ['costs' => ['stock' => '1', 'staff' => '1', 'premises' => '1', 'transport' => '1', 'taxes' => '1', 'rent' => '1']], 'costs', 'Enter an amount for each of the six cost categories.'],
    'cost not whole' => ['costs', ['costs' => ['stock' => '1', 'staff' => '1', 'premises' => '1', 'transport' => '1', 'taxes' => '1', 'other' => '2.5']], 'costs', 'Use a non-negative whole-number amount.'],
    'cost blank' => ['costs', ['costs' => ['stock' => '1', 'staff' => '1', 'premises' => '1', 'transport' => '1', 'taxes' => '1', 'other' => null]], 'costs', 'Use a non-negative whole-number amount.'],
]);

it('adds the history, premises, people, registration and trading steps to a baseline Flash visit (BRS v3 VIS-R02)', function (): void {
    $procedure = new AuditProcedure;
    expect($procedure->steps('baseline'))->toBe(['review', 'check_in', 'history', 'premises', 'people', 'registration', 'trading', 'photos', 'ledger', 'seal'])
        ->and($procedure->steps('flash'))->toBe(['review', 'check_in', 'photos', 'ledger', 'seal']);

    $state = procedureBefore('baseline', 'history');
    $history = $procedure->save('baseline', 'history', $state['draft'], 'history', ['history_checked' => true], procedureSources());
    expect($history['step'])->toBe('premises')->and($history['draft']['fields']['history'])->toBe(['history_checked' => true])
        ->and($history['draft']['sources']['history'] ?? null)->toBe(['verification' => procedureSource('verification')]);

    $premises = $procedure->save('baseline', 'premises', $history['draft'], 'premises', ['premises_tenure' => 'owned',
        'assets' => [['value' => '1800000', 'item' => '  Delivery motorbike '], ['item' => 'Cold room', 'value' => '0']]], procedureSources());
    $none = $procedure->save('baseline', 'premises', $history['draft'], 'premises', ['premises_tenure' => 'shared', 'assets' => []], procedureSources());
    expect($premises['step'])->toBe('people')
        ->and($premises['draft']['fields']['premises'])->toBe(['premises_tenure' => 'owned', 'assets' => [['item' => 'Delivery motorbike', 'value' => '1800000'], ['item' => 'Cold room', 'value' => '0']]])
        ->and($premises['draft']['sources']['premises'] ?? null)->toBe(['check_in' => procedureSource('check_in')])
        ->and($none['draft']['fields']['premises'])->toBe(['premises_tenure' => 'shared', 'assets' => []]);

    $people = $procedure->save('baseline', 'people', $premises['draft'], 'people', ['management_confirmed' => true, 'owners_confirmed' => true], procedureSources());
    expect($people['step'])->toBe('registration')->and($people['draft']['fields']['people'])->toBe(['owners_confirmed' => true, 'management_confirmed' => true]);

    $registration = $procedure->save('baseline', 'registration', $people['draft'], 'registration', ['registration_confirmed' => true, 'tax_clearance_expires_on' => '2024-02-29'], procedureSources());
    expect($registration['step'])->toBe('trading')->and($registration['draft']['fields']['registration'])->toBe(['registration_confirmed' => true, 'tax_clearance_expires_on' => '2024-02-29']);

    $trading = $procedure->save('baseline', 'trading', $registration['draft'], 'trading', ['trading_since' => '2019-12'], procedureSources());
    expect($trading['step'])->toBe('photos')->and($trading['draft']['fields']['trading'])->toBe(['trading_since' => '2019-12']);

    // Editing an earlier baseline step drops the later work, as for any step.
    $again = $procedure->save('baseline', 'photos', $trading['draft'], 'premises', ['premises_tenure' => 'rented', 'assets' => []], procedureSources());
    expect($again['step'])->toBe('people')->and($again['draft']['completed_steps'])->toBe(['review', 'check_in', 'history', 'premises'])
        ->and($again['draft']['fields'])->not->toHaveKeys(['people', 'registration', 'trading']);
});

it('reports a baseline step unavailable until its source is', function (): void {
    $procedure = new AuditProcedure;
    $state = procedureBefore('baseline', 'history');
    expect($procedure->unavailable('baseline', 'history', $state['draft'], 'history', [...procedureSources(), 'verification' => null]))->toBe('AUDIT_VERIFIED_STATEMENTS_REQUIRED')
        ->and($procedure->unavailable('baseline', 'history', $state['draft'], 'history', procedureSources()))->toBeNull();
});

it('refuses a baseline step it cannot record exactly', function (string $step, array $input, string $field, string $message): void {
    $procedure = new AuditProcedure;
    $state = procedureBefore('baseline', $step);

    try {
        $procedure->save('baseline', $step, $state['draft'], $step, $input, procedureSources());
        $this->fail('The step was accepted.');
    } catch (CommandRejection $rejection) {
        expect($rejection->reason)->toBe('AUDIT_STEP_INPUT_INVALID')->and($rejection->fieldErrors)->toBe([$field => [$message]]);
    }
})->with([
    'history not compared' => ['history', ['history_checked' => false], 'history_checked', 'Compare every month of the history with the statements before continuing.'],
    'history as text' => ['history', ['history_checked' => 'yes'], 'history_checked', 'Compare every month of the history with the statements before continuing.'],
    'premises unknown tenure' => ['premises', ['premises_tenure' => 'leased', 'assets' => []], 'premises_tenure', 'Say who holds the premises.'],
    'assets not a list' => ['premises', ['premises_tenure' => 'owned', 'assets' => ['car' => '1']], 'assets', 'List at most 10 assets.'],
    'assets too many' => ['premises', ['premises_tenure' => 'owned', 'assets' => array_fill(0, 11, ['item' => 'Chair', 'value' => '1'])], 'assets', 'List at most 10 assets.'],
    'asset missing value' => ['premises', ['premises_tenure' => 'owned', 'assets' => [['item' => 'Chair']]], 'assets', 'Give each asset what it is and its estimated value.'],
    'asset blank item' => ['premises', ['premises_tenure' => 'owned', 'assets' => [['item' => ' ', 'value' => '1']]], 'assets', 'Use plain text of at most 100 characters.'],
    'asset bad value' => ['premises', ['premises_tenure' => 'owned', 'assets' => [['item' => 'Chair', 'value' => '1.5']]], 'assets', 'Use a non-negative whole-number amount.'],
    'owners not checked' => ['people', ['owners_confirmed' => false, 'management_confirmed' => true], 'owners_confirmed', 'Check the identity of every director and owner before continuing.'],
    'management not confirmed' => ['people', ['owners_confirmed' => true, 'management_confirmed' => null], 'management_confirmed', 'Confirm the management profiles before continuing.'],
    'registration not checked' => ['registration', ['registration_confirmed' => false, 'tax_clearance_expires_on' => '2027-03-31'], 'registration_confirmed', 'Check the registration certificate before continuing.'],
    'tax date impossible' => ['registration', ['registration_confirmed' => true, 'tax_clearance_expires_on' => '2027-02-30'], 'tax_clearance_expires_on', 'Use the date on the certificate, as YYYY-MM-DD.'],
    'trading as a date' => ['trading', ['trading_since' => '2021-06-01'], 'trading_since', 'Use the month trading started, as YYYY-MM.'],
    'trading month 13' => ['trading', ['trading_since' => '2021-13'], 'trading_since', 'Use the month trading started, as YYYY-MM.'],
    'trading missing' => ['trading', ['trading_since' => null], 'trading_since', 'Use the month trading started, as YYYY-MM.'],
]);

it('follows check-in with the money available now and the reason on a late visit, then seals (BRS v3 VIS-R02)', function (): void {
    $procedure = new AuditProcedure;
    expect($procedure->steps('late'))->toBe(['review', 'check_in', 'available', 'reason', 'seal']);

    $state = procedureBefore('late', 'available');
    $available = $procedure->save('late', 'available', $state['draft'], 'available', ['financial_proofs' => ['cash', 'bank'], 'cash_available' => '0'], procedureSources());
    expect($available['step'])->toBe('reason')
        ->and($available['draft']['fields']['available'])->toBe(['cash_available' => '0', 'financial_proofs' => ['bank', 'cash']])
        ->and($available['draft']['sources']['available'] ?? null)->toBe(['check_in' => procedureSource('check_in')]);

    $reason = $procedure->save('late', 'reason', $available['draft'], 'reason', ['late_reason' => 'other', 'late_reason_note' => ' Flooded shop. '], procedureSources());
    expect($reason['step'])->toBe('seal')->and($reason['draft']['fields']['reason'])->toBe(['late_reason' => 'other', 'late_reason_note' => 'Flooded shop.'])
        ->and($procedure->unavailable('late', 'seal', $reason['draft'], 'seal', procedureSources()))->toBeNull();

    $sealed = $procedure->save('late', 'seal', $reason['draft'], 'seal', ['note' => ''], procedureSources());
    expect($sealed['draft']['completed_steps'])->toBe(['review', 'check_in', 'available', 'reason']);
});

it('withholds the money-available step until a financial proof is on file', function (): void {
    $procedure = new AuditProcedure;
    $state = procedureBefore('late', 'available');

    expect($procedure->unavailable('late', 'available', $state['draft'], 'available', [...procedureSources(), 'financial_proofs' => []]))->toBe('AUDIT_PROOFS_REQUIRED')
        ->and($procedure->unavailable('late', 'available', $state['draft'], 'available', [...procedureSources(), 'check_in' => null]))->toBe('AUDIT_CAPTURE_REQUIRED')
        ->and($procedure->unavailable('late', 'available', $state['draft'], 'available', procedureSources()))->toBeNull();
});

it('refuses a late visit step it cannot record exactly', function (string $step, array $input, string $field, string $message): void {
    $procedure = new AuditProcedure;
    $state = procedureBefore('late', $step);

    try {
        $procedure->save('late', $step, $state['draft'], $step, $input, procedureSources());
        $this->fail('The step was accepted.');
    } catch (CommandRejection $rejection) {
        expect($rejection->reason)->toBe(str_ends_with($message, 'proof before continuing.') ? 'AUDIT_PROOFS_REQUIRED' : 'AUDIT_STEP_INPUT_INVALID')
            ->and($rejection->fieldErrors)->toBe([$field => [$message]]);
    }
})->with([
    'amount with decimals' => ['available', ['cash_available' => '10.5', 'financial_proofs' => ['bank', 'cash']], 'cash_available', 'Use a non-negative whole-number amount.'],
    'proof missed' => ['available', ['cash_available' => '10', 'financial_proofs' => ['bank']], 'financial_proofs', 'Inspect every required proof before continuing.'],
    'reason off the list' => ['reason', ['late_reason' => 'payment_plan', 'late_reason_note' => ''], 'late_reason', 'Choose why the repayment is late.'],
    'other without a note' => ['reason', ['late_reason' => 'other', 'late_reason_note' => ' '], 'late_reason_note', 'Use plain text of at most 200 characters.'],
    'note too long' => ['reason', ['late_reason' => 'sales_fell', 'late_reason_note' => str_repeat('a', 201)], 'late_reason_note', 'Use plain text of at most 200 characters.'],
]);
