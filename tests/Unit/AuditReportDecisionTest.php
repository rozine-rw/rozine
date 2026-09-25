<?php

declare(strict_types=1);

use App\Domain\Auditor\AuditReportDecision;
use App\Domain\Operations\CommandRejection;

it('retains every agreed factual reason without assigning a credit verdict', function (bool $reject, string $code): void {
    expect(AuditReportDecision::reason('monthly', 'draft', $reject, $code, '  Original balances cannot be reconciled.  '))
        ->toBe(['code' => $code, 'explanation' => 'Original balances cannot be reconciled.']);
})->with([
    ...array_map(fn (string $code): array => [false, $code], AuditReportDecision::CHANGES),
    ...array_map(fn (string $code): array => [true, $code], AuditReportDecision::REJECTION),
]);

it('refuses decisions outside an editable monthly report', function (string $kind, string $status): void {
    expect(fn () => AuditReportDecision::reason($kind, $status, false, 'other', 'Factual reason.'))
        ->toThrow(CommandRejection::class, 'AUDIT_REPORT_DECISION_NOT_ALLOWED');
})->with([['flash', 'draft'], ['monthly', 'sealed'], ['monthly', 'rejected'], ['monthly', 'changes_requested'], ['monthly', 'withdrawn']]);

it('requires a known reason and bounded plain factual explanation', function (mixed $code, mixed $reason): void {
    expect(fn () => AuditReportDecision::reason('monthly', 'draft', false, $code, $reason))
        ->toThrow(CommandRejection::class, 'AUDIT_REPORT_DECISION_INVALID');
})->with([[null, 'Missing documents.'], [['other'], 'Missing documents.'], ['credit_rejected', 'Missing documents.'],
    ['other', null], ['other', []], ['other', '   '], ['other', str_repeat('a', 2001)], ['other', "Bad\0text"], ['other', "\xFF"], ['other', "Hidden\u{200B}text"]]);

it('amends only a returned rejected or sealed version', function (string $status, bool $allowed): void {
    expect(AuditReportDecision::amendable($status))->toBe($allowed);
})->with([['changes_requested', true], ['rejected', true], ['sealed', true], ['draft', false], ['withdrawn', false]]);
