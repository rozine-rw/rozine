<?php

declare(strict_types=1);

namespace App\Domain\Evidence;

use App\Domain\Operations\CommandRejection;

/** @phpstan-type Source array{filename: string, media_type: string, size_bytes: int, sha256: string} */
final class StatementSource
{
    public const MAX_BYTES = 10 * 1024 * 1024;

    /** @return Source */
    public function describe(string $filename, string $content): array
    {
        if ($filename === '' || strlen($filename) > 180 || ! mb_check_encoding($filename, 'UTF-8')
            || preg_match('/[\\p{Cc}\\p{Cf}\\\\\\/]/u', $filename) === 1) {
            throw new CommandRejection('STATEMENT_FILENAME_INVALID', 422, fieldErrors: ['file' => ['Use a plain PDF or CSV filename.']]);
        }
        $length = strlen($content);
        if ($length === 0 || $length > self::MAX_BYTES) {
            throw new CommandRejection('STATEMENT_SIZE_INVALID', 422, fieldErrors: ['file' => ['The statement must be nonempty and at most 10 MiB.']]);
        }
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $pdf = preg_match('/^%PDF-[12]\\.[0-9]/', $content) === 1;
        $csv = mb_check_encoding($content, 'UTF-8') && preg_match('/[\\x00-\\x08\\x0b\\x0c\\x0e-\\x1f\\x7f]/', $content) !== 1;
        if (! (($extension === 'pdf' && $pdf) || ($extension === 'csv' && ! $pdf && $csv))) {
            throw new CommandRejection('STATEMENT_TYPE_UNSUPPORTED', 422, fieldErrors: ['file' => ['Upload an original PDF or a UTF-8 CSV export.']]);
        }

        return ['filename' => $filename, 'media_type' => $pdf ? 'application/pdf' : 'text/csv',
            'size_bytes' => $length, 'sha256' => hash('sha256', $content)];
    }
}
