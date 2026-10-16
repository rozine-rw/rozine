<?php

declare(strict_types=1);

use App\Domain\Auditor\AccreditationCertificate;
use App\Domain\Operations\CommandRejection;

it('hashes exact private certificate bytes without decoding the document', function (string $filename, string $content, string $media): void {
    expect((new AccreditationCertificate)->describe($filename, $content))->toBe([
        'filename' => $filename, 'media_type' => $media, 'size_bytes' => strlen($content), 'sha256' => hash('sha256', $content),
    ]);
})->with([
    ['licence.PDF', "%PDF-1.7\nprivate\x00\xff", 'application/pdf'],
    ['licence.png', "\x89PNG\r\n\x1a\nprivate", 'image/png'],
    ['licence.jpg', "\xff\xd8\xffprivate", 'image/jpeg'],
    ['licence.JPEG', "\xff\xd8\xffprivate", 'image/jpeg'],
]);

it('rejects unsafe names mismatched types and unbounded certificates', function (string $name, string $content, string $reason): void {
    expect(fn () => (new AccreditationCertificate)->describe($name, $content))->toThrow(CommandRejection::class, $reason);
})->with([
    ['', '%PDF-1.7', 'ACCREDITATION_FILENAME_INVALID'],
    [str_repeat('a', 177).'.pdf', '%PDF-1.7', 'ACCREDITATION_FILENAME_INVALID'],
    ["private\nfile.pdf", '%PDF-1.7', 'ACCREDITATION_FILENAME_INVALID'],
    ["private\u{202E}file.pdf", '%PDF-1.7', 'ACCREDITATION_FILENAME_INVALID'],
    ["private\u{0085}file.pdf", '%PDF-1.7', 'ACCREDITATION_FILENAME_INVALID'],
    ["private\xff.pdf", '%PDF-1.7', 'ACCREDITATION_FILENAME_INVALID'],
    ['../private.pdf', '%PDF-1.7', 'ACCREDITATION_FILENAME_INVALID'],
    ['private\\file.pdf', '%PDF-1.7', 'ACCREDITATION_FILENAME_INVALID'],
    ['private.pdf', '', 'ACCREDITATION_SIZE_INVALID'],
    ['private.pdf', '%PDF-1.7'.str_repeat('a', AccreditationCertificate::MAX_BYTES), 'ACCREDITATION_SIZE_INVALID'],
    ['private.pdf', "\x89PNG\r\n\x1a\n", 'ACCREDITATION_TYPE_UNSUPPORTED'],
    ['private.exe', '%PDF-1.7', 'ACCREDITATION_TYPE_UNSUPPORTED'],
    ['private.jpg', '<html>not a photo</html>', 'ACCREDITATION_TYPE_UNSUPPORTED'],
]);
