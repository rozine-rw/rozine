<?php

declare(strict_types=1);

namespace App\Domain\Auditor;

use App\Domain\Operations\CommandRejection;

/** @phpstan-type Certificate array{filename: string, media_type: string, size_bytes: int, sha256: string} */
final class AccreditationCertificate
{
    public const MAX_BYTES = 10 * 1024 * 1024;

    /**
     * Identifies a private, unverified upload without decoding untrusted documents or images.
     *
     * @return Certificate
     */
    public function describe(string $filename, string $content): array
    {
        if ($filename === '' || strlen($filename) > 180 || ! mb_check_encoding($filename, 'UTF-8')
            || preg_match('/[\p{Cc}\p{Cf}\\\\\/]/u', $filename) === 1) {
            throw new CommandRejection('ACCREDITATION_FILENAME_INVALID', 422, fieldErrors: ['certificate' => ['Use a plain PDF, PNG or JPEG filename.']]);
        }
        $length = strlen($content);
        if ($length === 0 || $length > self::MAX_BYTES) {
            throw new CommandRejection('ACCREDITATION_SIZE_INVALID', 422, fieldErrors: ['certificate' => ['The certificate must be nonempty and at most 10 MiB.']]);
        }
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $media = match (true) {
            $extension === 'pdf' && preg_match('/^%PDF-[12]\.[0-9]/', $content) === 1 => 'application/pdf',
            $extension === 'png' && str_starts_with($content, "\x89PNG\r\n\x1a\n") => 'image/png',
            in_array($extension, ['jpg', 'jpeg'], true) && str_starts_with($content, "\xff\xd8\xff") => 'image/jpeg',
            default => throw new CommandRejection('ACCREDITATION_TYPE_UNSUPPORTED', 422, fieldErrors: ['certificate' => ['Upload a PDF, PNG or JPEG certificate.']]),
        };

        return ['filename' => $filename, 'media_type' => $media, 'size_bytes' => $length, 'sha256' => hash('sha256', $content)];
    }
}
