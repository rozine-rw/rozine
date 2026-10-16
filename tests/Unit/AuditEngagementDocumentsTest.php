<?php

declare(strict_types=1);

use App\Domain\Auditor\AuditEngagementDocuments;
use App\Domain\Operations\CommandRejection;
use Tests\Support\AuditEngagementFixture;

it('retains the exact original engagement text and hashes its bytes', function (): void {
    $documents = AuditEngagementFixture::documents();
    $documents['master_services'] = ['title' => '  Master services  ', 'body' => " Original é\r\n\tterms. \n"];
    $result = (new AuditEngagementDocuments)->normalize($documents);
    expect($result['master_services'])->toBe(['title' => 'Master services', 'body' => $documents['master_services']['body'],
        'sha256' => hash('sha256', $documents['master_services']['body'])]);
});

it('rejects incomplete documents and untrusted extra engagement fields', function (string $case): void {
    $documents = AuditEngagementFixture::documents();
    match ($case) {
        'missing kind' => $documents = ['master_services' => $documents['master_services']],
        'extra kind' => $documents['other'] = [],
        'not document' => $documents['master_services'] = 'text',
        'missing title' => $documents['master_services'] = ['body' => 'Text'],
        'missing body' => $documents['master_services'] = ['title' => 'Title'],
        'client hash' => $documents['master_services']['sha256'] = str_repeat('a', 64),
        default => throw new InvalidArgumentException('Unknown malformed document case.'),
    };
    expect(fn () => (new AuditEngagementDocuments)->normalize($documents))->toThrow(CommandRejection::class, 'AUDIT_ENGAGEMENT_INPUT_INVALID');
})->with(['missing kind', 'extra kind', 'not document', 'missing title', 'missing body', 'client hash']);

it('rejects malformed or unbounded engagement text', function (mixed $value, bool $multiline): void {
    expect(fn () => (new AuditEngagementDocuments)->text($value, 'terms', 200, $multiline))
        ->toThrow(CommandRejection::class, 'AUDIT_ENGAGEMENT_INPUT_INVALID');
})->with([
    [null, false], [12, false], ['', false], ['  ', true], [str_repeat('é', 201), true], ["\xFF", true],
    ["hello\nworld", false], ["hello\x00world", true], ["hello\u{0085}world", true], ["hello\u{202E}world", true],
]);

it('only accepts bounded distinct engagement version identifiers', function (mixed $version, bool $valid): void {
    $documents = new AuditEngagementDocuments;
    if ($valid) {
        expect($documents->version($version))->toBe($version);
    } else {
        expect(fn () => $documents->version($version))->toThrow(CommandRejection::class, 'AUDIT_ENGAGEMENT_INPUT_INVALID');
    }
})->with([['MVP-AUP-1.2026_1', true], [str_repeat('a', 80), true], [str_repeat('a', 81), false], [null, false], [12, false], ['', false], ["v1\n", false], ['../v1', false]]);
