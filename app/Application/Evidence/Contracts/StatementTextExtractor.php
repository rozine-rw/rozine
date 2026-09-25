<?php

declare(strict_types=1);

namespace App\Application\Evidence\Contracts;

/**
 * Extraction is not transaction classification, reconciliation or audit approval.
 *
 * @phpstan-type Extraction array{parser_version: string, status: 'text_extracted'|'needs_review', reason_codes: list<string>, text: string|null, record_count: int|null}
 */
interface StatementTextExtractor
{
    /** @return Extraction */
    public function extract(string $content, string $mediaType): array;
}
