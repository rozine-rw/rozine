<?php

declare(strict_types=1);

use App\Domain\Evidence\StatementAuditReview;
use App\Domain\Evidence\StatementReconciliation;
use App\Domain\Operations\CommandRejection;
use Tests\Support\StatementFixture;

it('verifies reconciled source observations without adding a credit decision or report approval', function (): void {
    $input = StatementFixture::reconciliation();
    $hashes = ['original-a' => hash('sha256', 'bank'), 'original-b' => hash('sha256', 'momo')];
    $observations = (new StatementReconciliation)->reconcile($input['rails'], $input['months'], $input['statements'], $input['sources']);
    $review = StatementFixture::review($hashes);
    $review['obligations'] = [['id' => 'loan-1', 'principal' => '2000', 'service_by_month' => ['2026-09' => '150', '2026-10' => '0'], 'source_ids' => ['original-a']]];
    $verified = (new StatementAuditReview)->verify($review, $hashes, $observations, new DateTimeImmutable('2026-09-24T08:00:00Z'));
    expect($verified['observations'])->toBe([[...$observations[0], 'verified' => true]])
        ->and($verified['review'])->toEqual($review)
        ->and($observations[0]['verified'])->toBeFalse()->and($verified['observations'][0])->not->toHaveKeys(['rating', 'capacity', 'yield', 'credit_verdict']);
});

it('refuses incomplete unsupported or unbound factual reviews', function (string $fault, string $code): void {
    $input = StatementFixture::reconciliation();
    $hashes = ['original-a' => hash('sha256', 'bank'), 'original-b' => hash('sha256', 'momo')];
    $observations = (new StatementReconciliation)->reconcile($input['rails'], $input['months'], $input['statements'], $input['sources']);
    $review = StatementFixture::review($hashes);
    if ($fault === 'procedure') {
        $review['procedure_version'] = 'invented';
    } elseif ($fault === 'unchecked') {
        $review['checks']['originals_authentic'] = false;
    } elseif ($fault === 'missing check') {
        unset($review['checks']['complete_rail_inventory']);
    } elseif ($fault === 'credit verdict') {
        $review['credit_verdict'] = 'approved';
    } elseif ($fault === 'source hash') {
        $review['source_checks']['original-a'] = ['sha256' => hash('sha256', 'changed'), 'reference' => 'synthetic:checked'];
    } elseif ($fault === 'missing source') {
        unset($review['source_checks']['original-b']);
    } elseif ($fault === 'foreign source') {
        $review['source_checks']['foreign'] = $review['source_checks']['original-b'];
        unset($review['source_checks']['original-b']);
    } elseif ($fault === 'source authority') {
        $review['source_checks']['original-b'] = ['sha256' => $hashes['original-b'], 'reference' => 'synthetic:checked', 'verified' => true];
    } elseif ($fault === 'empty original inventory') {
        $hashes = [];
        $review['source_checks'] = [];
    } elseif ($fault === 'findings') {
        $review['findings'] = '';
    } elseif ($fault === 'reference controls') {
        $review['inventory_reference'] = "concealed\u{202e}reference";
    } elseif ($fault === 'reference encoding') {
        $review['owner_draw_reference'] = "\xff";
    } elseif ($fault === 'long reference') {
        $review['source_checks']['original-a'] = ['sha256' => $hashes['original-a'], 'reference' => str_repeat('x', 2001)];
    } elseif ($fault === 'amount') {
        $review['recurring_owner_draw'] = '01';
    } elseif ($fault === 'observations') {
        $observations = [];
    } else {
        $review['obligations'] = [['id' => 'loan-1', 'principal' => '2000', 'service_by_month' => ['2026-09' => '150'], 'source_ids' => ['original-a']]];
        if ($fault === 'duplicate debt') {
            $review['obligations'][] = $review['obligations'][0];
        } elseif ($fault === 'debt source') {
            $review['obligations'][0]['source_ids'] = ['unknown'];
        } elseif ($fault === 'missing debt source') {
            $review['obligations'][0]['source_ids'] = [];
        } elseif ($fault === 'missing schedule') {
            $review['obligations'][0]['service_by_month'] = [];
        } elseif ($fault === 'debt principal') {
            $review['obligations'][0]['principal'] = '-1';
        } elseif ($fault === 'debt service') {
            $review['obligations'][0]['service_by_month'] = ['2026-09' => '-1'];
        } elseif ($fault === 'debt month') {
            $review['obligations'][0]['service_by_month'] = ['2026-13' => '150'];
        } else {
            $review['obligations'][0]['capacity'] = '100000000';
        }
    }
    expect(fn () => (new StatementAuditReview)->verify($review, $hashes, $observations, new DateTimeImmutable('2026-09-24T08:00:00Z')))->toThrow(CommandRejection::class, $code);
})->with([
    ['procedure', 'STATEMENT_REVIEW_PROCEDURE_REQUIRED'], ['credit verdict', 'STATEMENT_REVIEW_PROCEDURE_REQUIRED'],
    ['unchecked', 'STATEMENT_REVIEW_INCOMPLETE'], ['missing check', 'STATEMENT_REVIEW_INCOMPLETE'],
    ['source hash', 'STATEMENT_SOURCE_REVIEW_REQUIRED'], ['missing source', 'STATEMENT_SOURCE_REVIEW_REQUIRED'],
    ['foreign source', 'STATEMENT_SOURCE_REVIEW_REQUIRED'], ['source authority', 'STATEMENT_SOURCE_REVIEW_REQUIRED'], ['empty original inventory', 'STATEMENT_SOURCE_REVIEW_REQUIRED'],
    ['findings', 'STATEMENT_REVIEW_REFERENCE_REQUIRED'], ['reference controls', 'STATEMENT_REVIEW_REFERENCE_REQUIRED'],
    ['reference encoding', 'STATEMENT_REVIEW_REFERENCE_REQUIRED'], ['long reference', 'STATEMENT_REVIEW_REFERENCE_REQUIRED'],
    ['amount', 'STATEMENT_REVIEW_AMOUNT_INVALID'], ['observations', 'STATEMENT_RECONCILIATION_REQUIRED'],
    ['duplicate debt', 'STATEMENT_DEBT_EVIDENCE_REQUIRED'], ['debt source', 'STATEMENT_DEBT_EVIDENCE_REQUIRED'],
    ['missing debt source', 'STATEMENT_DEBT_EVIDENCE_REQUIRED'], ['missing schedule', 'STATEMENT_DEBT_EVIDENCE_REQUIRED'],
    ['debt principal', 'STATEMENT_REVIEW_AMOUNT_INVALID'], ['debt service', 'STATEMENT_REVIEW_AMOUNT_INVALID'],
    ['debt month', 'STATEMENT_REVIEW_MONTH_INVALID'], ['debt authority', 'STATEMENT_DEBT_EVIDENCE_REQUIRED'],
]);

it('requires the entire observation month to have ended in Kigali', function (): void {
    $input = StatementFixture::reconciliation();
    $hashes = ['original-a' => hash('sha256', 'bank'), 'original-b' => hash('sha256', 'momo')];
    $observations = (new StatementReconciliation)->reconcile($input['rails'], $input['months'], $input['statements'], $input['sources']);
    $review = StatementFixture::review($hashes);
    $verifier = new StatementAuditReview;
    expect(fn () => $verifier->verify($review, $hashes, $observations, new DateTimeImmutable('2026-08-31T21:59:59Z')))->toThrow(CommandRejection::class, 'STATEMENT_COMPLETE_MONTH_REQUIRED')
        ->and($verifier->verify($review, $hashes, $observations, new DateTimeImmutable('2026-08-31T22:00:00Z'))['observations'][0]['verified'])->toBeTrue();
});

it('rejects malformed nested review values with stable domain validation codes', function (string $path, mixed $value, string $code): void {
    $input = StatementFixture::reconciliation();
    $hashes = ['original-a' => hash('sha256', 'bank'), 'original-b' => hash('sha256', 'momo')];
    $observations = (new StatementReconciliation)->reconcile($input['rails'], $input['months'], $input['statements'], $input['sources']);
    $review = StatementFixture::review($hashes);
    $review['obligations'] = [['id' => 'loan-1', 'principal' => '2000', 'service_by_month' => ['2026-09' => '150'], 'source_ids' => ['original-a']]];
    data_set($review, $path, $value);
    try {
        (new StatementAuditReview)->verify($review, $hashes, $observations, new DateTimeImmutable('2026-09-24T08:00:00Z'));
        $this->fail('Malformed reviews must be rejected before granting source verification.');
    } catch (CommandRejection $exception) {
        expect($exception->reason)->toBe($code)->and($exception->status)->toBe(422);
    }
})->with([
    ['procedure_version', null, 'STATEMENT_REVIEW_PROCEDURE_REQUIRED'],
    ['checks', 'all', 'STATEMENT_REVIEW_INCOMPLETE'],
    ['checks.originals_authentic', 1, 'STATEMENT_REVIEW_INCOMPLETE'],
    ['source_checks', null, 'STATEMENT_SOURCE_REVIEW_REQUIRED'],
    ['source_checks.original-a', false, 'STATEMENT_SOURCE_REVIEW_REQUIRED'],
    ['source_checks.original-a', ['reference' => 'missing hash'], 'STATEMENT_SOURCE_REVIEW_REQUIRED'],
    ['source_checks.original-a', ['sha256' => 'missing reference'], 'STATEMENT_SOURCE_REVIEW_REQUIRED'],
    ['source_checks.original-a.sha256', ['hash'], 'STATEMENT_SOURCE_REVIEW_REQUIRED'],
    ['source_checks.original-a.reference', null, 'STATEMENT_REVIEW_REFERENCE_REQUIRED'],
    ['inventory_reference', [], 'STATEMENT_REVIEW_REFERENCE_REQUIRED'],
    ['owner_draw_reference', 123, 'STATEMENT_REVIEW_REFERENCE_REQUIRED'],
    ['findings', true, 'STATEMENT_REVIEW_REFERENCE_REQUIRED'],
    ['recurring_owner_draw', 100, 'STATEMENT_REVIEW_AMOUNT_INVALID'],
    ['obligations', 'none', 'STATEMENT_DEBT_EVIDENCE_REQUIRED'],
    ['obligations', ['named' => []], 'STATEMENT_DEBT_EVIDENCE_REQUIRED'],
    ['obligations.0', null, 'STATEMENT_DEBT_EVIDENCE_REQUIRED'],
    ['obligations.0', ['id' => 'loan-1'], 'STATEMENT_DEBT_EVIDENCE_REQUIRED'],
    ['obligations.0.id', [], 'STATEMENT_REVIEW_REFERENCE_REQUIRED'],
    ['obligations.0.principal', 2000, 'STATEMENT_REVIEW_AMOUNT_INVALID'],
    ['obligations.0.source_ids', 'original-a', 'STATEMENT_DEBT_EVIDENCE_REQUIRED'],
    ['obligations.0.source_ids', ['named' => 'original-a'], 'STATEMENT_DEBT_EVIDENCE_REQUIRED'],
    ['obligations.0.source_ids', [['original-a']], 'STATEMENT_DEBT_EVIDENCE_REQUIRED'],
    ['obligations.0.source_ids', ['original-a', 'original-a'], 'STATEMENT_DEBT_EVIDENCE_REQUIRED'],
    ['obligations.0.service_by_month', '150', 'STATEMENT_DEBT_EVIDENCE_REQUIRED'],
    ['obligations.0.service_by_month', ['150'], 'STATEMENT_REVIEW_MONTH_INVALID'],
    ['obligations.0.service_by_month', ['2026-09' => []], 'STATEMENT_REVIEW_AMOUNT_INVALID'],
]);

it('rejects each missing top-level factual review field before reading it', function (string $field): void {
    $review = StatementFixture::review(['original-a' => hash('sha256', 'bank')]);
    unset($review[$field]);
    expect(fn () => (new StatementAuditReview)->verify($review, [], [], new DateTimeImmutable('2026-09-24T08:00:00Z')))
        ->toThrow(CommandRejection::class, 'STATEMENT_REVIEW_PROCEDURE_REQUIRED');
})->with(['procedure_version', 'checks', 'source_checks', 'inventory_reference', 'obligations', 'recurring_owner_draw', 'owner_draw_reference', 'findings']);
