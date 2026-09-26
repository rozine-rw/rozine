<?php

declare(strict_types=1);

namespace App\Domain\Auditor;

use App\Domain\Operations\CommandRejection;
use DateTimeImmutable;
use DateTimeZone;

final class MonthlyReportReview
{
    public const POLICY = 'monthly-review-2026-09-26';

    public const LEGACY_POLICY = 'audit-publication-legacy';

    public function dueAt(DateTimeImmutable $deliveredAt): string
    {
        return $deliveredAt->setTimezone(new DateTimeZone('UTC'))->modify('+24 hours')->format('Y-m-d\TH:i:s\Z');
    }

    public function isOpen(string $status, string $dueAt, DateTimeImmutable $now): bool
    {
        return $status === 'pending' && $now < new DateTimeImmutable($dueAt);
    }

    public function requireOpen(string $status, string $dueAt, DateTimeImmutable $now): void
    {
        if ($status !== 'pending') {
            throw new CommandRejection('REPORT_REVIEW_CLOSED');
        }
        if (! $this->isOpen($status, $dueAt, $now)) {
            throw new CommandRejection('REPORT_WINDOW_CLOSED');
        }
    }

    public function mayAutoApprove(string $status, string $dueAt, DateTimeImmutable $now): bool
    {
        return $status === 'pending' && $now >= new DateTimeImmutable($dueAt);
    }

    public function proofText(string $text, int $files): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", trim($text));
        if (! mb_check_encoding($text, 'UTF-8') || mb_strlen($text) > 1000 || preg_match('/[^\P{Cc}\n\t]|[\p{Cf}\x{2028}\x{2029}]/u', $text)) {
            throw new CommandRejection('REPORT_DISPUTE_PROOF_INVALID', 422, fieldErrors: ['supporting_text' => ['Use plain text of at most 1,000 characters.']]);
        }
        if ($files < 0 || $files > 5 || ($text === '' && $files === 0)) {
            throw new CommandRejection('REPORT_DISPUTE_PROOF_REQUIRED', 422, fieldErrors: ['proof_files' => ['Provide supporting text or up to five proof files.']]);
        }

        return $text;
    }

    public function decisionReason(string $text): string
    {
        return $this->proofText($text, 0);
    }
}
