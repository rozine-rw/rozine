<?php

declare(strict_types=1);

use App\Domain\Identity\ConsentDocuments;

/**
 * @return array{version: string, body: string, summary: list<array{heading: string, body: string}>}
 */
function legalDocument(string $version): array
{
    $body = (string) file_get_contents(dirname(__DIR__, 2)."/docs/legal/{$version}.md");
    preg_match_all('/^## (.+)$/m', $body, $headings);

    return [
        'version' => $version,
        'body' => $body,
        'summary' => array_map(fn (string $heading): array => ['heading' => $heading, 'body' => $heading], $headings[1]),
    ];
}

it('names its own version inside every issued legal document', function (string $version): void {
    expect(legalDocument($version)['body'])->toContain("**Document identifier:** `{$version}`");
})->with(['investor-terms-2026-10-13', 'business-terms-2026-10-13', 'privacy-note-2026-10-13']);

it('accepts each Terms version with the Privacy Note as one consent release', function (string $terms): void {
    $documents = (new ConsentDocuments)->documents([
        ['kind' => 'terms', ...legalDocument($terms)],
        ['kind' => 'privacy', ...legalDocument('privacy-note-2026-10-13')],
    ]);

    expect(array_column($documents, 'version'))->toBe(['privacy-note-2026-10-13', $terms]);
})->with(['investor-terms-2026-10-13', 'business-terms-2026-10-13']);

it('keeps the decided exclusions out of the issued text', function (): void {
    $privacy = legalDocument('privacy-note-2026-10-13')['body'];
    $business = legalDocument('business-terms-2026-10-13')['body'];

    expect($privacy)->not->toContain('biometric')->not->toContain('privacy@rozine.rw')->toContain('info@rozine.rw')
        ->and($business)->not->toContain('Credit Reference')->not->toContain('First-Loss')->toContain('day 31');
});
