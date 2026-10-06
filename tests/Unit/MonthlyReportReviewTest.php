<?php

declare(strict_types=1);

use App\Domain\Auditor\MonthlyReportReview;
use App\Domain\Operations\CommandRejection;

it('opens a full 24 hour window from durable delivery even after the seventh', function (): void {
    $policy = new MonthlyReportReview;
    expect($policy->dueAt(new DateTimeImmutable('2026-09-26T23:30:00+02:00')))->toBe('2026-09-27T21:30:00Z');
    expect($policy->dueAt(new DateTimeImmutable('2026-12-31T23:30:00Z')))->toBe('2027-01-01T23:30:00Z');
});

it('gives human decisions and automatic approval disjoint boundaries', function (): void {
    $policy = new MonthlyReportReview;
    $due = '2026-09-27T10:00:00Z';
    $before = new DateTimeImmutable('2026-09-27T09:59:59Z');
    $at = new DateTimeImmutable($due);
    expect($policy->isOpen('pending', $due, $before))->toBeTrue()
        ->and($policy->mayAutoApprove('pending', $due, $before))->toBeFalse()
        ->and($policy->isOpen('pending', $due, $at))->toBeFalse()
        ->and($policy->mayAutoApprove('pending', $due, $at))->toBeTrue();
    $policy->requireOpen('pending', $due, $before);
    expect(fn () => $policy->requireOpen('pending', $due, $at))->toThrow(CommandRejection::class, 'REPORT_WINDOW_CLOSED');
});

it('never auto approves or reopens a frozen or terminal report', function (string $status): void {
    $policy = new MonthlyReportReview;
    $due = '2026-09-27T10:00:00Z';
    expect($policy->isOpen($status, $due, new DateTimeImmutable('2026-09-26T11:00:00Z')))->toBeFalse()
        ->and($policy->mayAutoApprove($status, $due, new DateTimeImmutable('2026-10-27T11:00:00Z')))->toBeFalse();
    expect(fn () => $policy->requireOpen($status, $due, new DateTimeImmutable($due)))->toThrow(CommandRejection::class, 'REPORT_REVIEW_CLOSED');
})->with(['disputed', 'escalated', 'published', 'amended']);

it('accepts text or files independently and normalizes proof text', function (): void {
    $policy = new MonthlyReportReview;
    expect($policy->proofText(" Revised\r\nstock record. ", 0))->toBe("Revised\nstock record.")
        ->and($policy->proofText('', 1))->toBe('')->and($policy->proofText(str_repeat('é', 1000), 5))->toBe(str_repeat('é', 1000))
        ->and($policy->decisionReason(' Reviewed proof. '))->toBe('Reviewed proof.');
});

it('refuses absent excessive or unsafe dispute proof', function (string $text, int $files, string $code): void {
    expect(fn () => (new MonthlyReportReview)->proofText($text, $files))->toThrow(CommandRejection::class, $code);
})->with([
    ['', 0, 'REPORT_DISPUTE_PROOF_REQUIRED'], [' ', 0, 'REPORT_DISPUTE_PROOF_REQUIRED'],
    ['', 6, 'REPORT_DISPUTE_PROOF_REQUIRED'], ['text', -1, 'REPORT_DISPUTE_PROOF_REQUIRED'],
    [str_repeat('a', 1001), 0, 'REPORT_DISPUTE_PROOF_INVALID'], ["\xFF", 1, 'REPORT_DISPUTE_PROOF_INVALID'],
    ["text\x00hidden", 1, 'REPORT_DISPUTE_PROOF_INVALID'], ["text\u{202E}hidden", 1, 'REPORT_DISPUTE_PROOF_INVALID'],
]);
