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
    if ($kind === 'flash') {
        expect($saved['draft']['fields']['ledger'])->toBe(['observed_stock' => '36400000', 'reconciled' => true]);
    } else {
        expect($saved['draft']['fields']['count']['financial_proofs'])->toBe(['bank', 'cash']);
    }
})->with(['flash', 'monthly']);

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
