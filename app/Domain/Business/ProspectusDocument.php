<?php

declare(strict_types=1);

namespace App\Domain\Business;

use App\Domain\Operations\CommandRejection;

/**
 * Supporting evidence for a prospectus (PRO-R03 P-G05, P-G06): the supplier quote or invoice behind
 * a large use-of-funds line, or the signed contract behind a forecast month outside the verified
 * range. Identified by its signature without decoding it; only PDF, PNG and JPEG are kept.
 *
 * Acceptance limit: the gates establish that a cited document exists, belongs to this application
 * and has the kind named, nothing more. Nobody reads its content: the kind is declared by the
 * Business, only the file type's magic prefix is checked, and the quote amount is entered
 * separately. Whether the file really is a quote for that amount, or a signed contract, is not
 * verified here.
 *
 * @phpstan-type Document array{filename: string, media_type: string, size_bytes: int, sha256: string}
 */
final class ProspectusDocument
{
    public const array KINDS = ['quote', 'contract'];

    public const int MAX_BYTES = 10 * 1024 * 1024;

    /** @return Document */
    public function describe(string $kind, string $filename, string $content): array
    {
        if (! in_array($kind, self::KINDS, true)) {
            throw new CommandRejection('VALIDATION_FAILED', 422, fieldErrors: ['kind' => ['Choose a quote or invoice, or a signed contract.']]);
        }
        if ($filename === '' || strlen($filename) > 180 || ! mb_check_encoding($filename, 'UTF-8')
            || preg_match('/[\p{Cc}\p{Cf}\\\\\/]/u', $filename) === 1) {
            throw new CommandRejection('VALIDATION_FAILED', 422, fieldErrors: ['file' => ['Use a plain PDF, PNG or JPEG filename.']]);
        }
        $length = strlen($content);
        if ($length === 0 || $length > self::MAX_BYTES) {
            throw new CommandRejection('VALIDATION_FAILED', 422, fieldErrors: ['file' => ['The file must be nonempty and at most 10 MiB.']]);
        }
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $media = match (true) {
            $extension === 'pdf' && preg_match('/^%PDF-[12]\.[0-9]/', $content) === 1 => 'application/pdf',
            $extension === 'png' && str_starts_with($content, "\x89PNG\r\n\x1a\n") => 'image/png',
            in_array($extension, ['jpg', 'jpeg'], true) && str_starts_with($content, "\xff\xd8\xff") => 'image/jpeg',
            default => throw new CommandRejection('VALIDATION_FAILED', 422, fieldErrors: ['file' => ['Upload a PDF, PNG or JPEG.']]),
        };

        return ['filename' => $filename, 'media_type' => $media, 'size_bytes' => $length, 'sha256' => hash('sha256', $content)];
    }
}
