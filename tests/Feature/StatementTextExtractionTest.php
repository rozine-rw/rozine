<?php

declare(strict_types=1);

use App\Infrastructure\Evidence\PdfAndCsvTextExtractor;
use Tests\Support\StatementFixture;

it('extracts text from a real PDF without treating it as verified financial data', function (): void {
    $result = app(PdfAndCsvTextExtractor::class)->extract(StatementFixture::pdf(), 'application/pdf');
    expect($result['status'])->toBe('text_extracted')->and($result['text'])->toContain('Synthetic statement fixture only')
        ->and($result['parser_version'])->toBe('smalot-pdfparser-2.12.5')
        ->and($result['record_count'])->toBeNull()->and($result)->not->toHaveKey('verified');
});

it('preserves CSV text and recognizes quoted multiline records without financial coercion', function (): void {
    $text = "date,description,amount\n2026-08-01,\"First line\nsecond line\",9007199254740993\n\n";
    $result = app(PdfAndCsvTextExtractor::class)->extract($text, 'text/csv');
    expect($result['text'])->toBe($text)->and($result['status'])->toBe('text_extracted')
        ->and($result['record_count'])->toBe(2)->and($result['reason_codes'])->toBe([]);
});

it('preserves BOM and line-ending bytes while recognizing exported CSV records', function (string $ending, string $bom): void {
    $text = $bom.'date,amount'.$ending.'2026-08-01,100'.$ending;
    $result = app(PdfAndCsvTextExtractor::class)->extract($text, 'text/csv');
    expect($result['status'])->toBe('text_extracted')->and($result['text'])->toBe($text)->and($result['record_count'])->toBe(2);
})->with(["\n", "\r\n", "\r"])->with(['', "\xEF\xBB\xBF"]);

it('accepts exactly the extracted-text limit without truncating the source', function (): void {
    $prefix = "reference,amount\n";
    $text = $prefix.str_repeat('x', PdfAndCsvTextExtractor::MAX_TEXT_BYTES - strlen($prefix) - 2).',0';
    $result = app(PdfAndCsvTextExtractor::class)->extract($text, 'text/csv');
    expect(strlen($text))->toBe(PdfAndCsvTextExtractor::MAX_TEXT_BYTES)->and($result['status'])->toBe('text_extracted')
        ->and($result['text'])->toBe($text)->and($result['record_count'])->toBe(2);
});

it('returns actionable extraction feedback instead of inventing data', function (string $content, string $mediaType, string $reason): void {
    $result = app(PdfAndCsvTextExtractor::class)->extract($content, $mediaType);
    expect($result['status'])->toBe('needs_review')->and($result['reason_codes'])->toBe([$reason])
        ->and($result['text'])->toBeNull()->and($result['record_count'])->toBeNull();
})->with([
    'unknown format' => ['unknown', 'application/octet-stream', 'STATEMENT_TYPE_UNSUPPORTED'],
    'unreadable pdf' => ['%PDF-1.7 broken', 'application/pdf', 'PDF_TEXT_UNAVAILABLE'],
    'image-only equivalent' => [StatementFixture::pdf(''), 'application/pdf', 'STATEMENT_TEXT_NOT_FOUND'],
    'blank text' => [" \n", 'text/csv', 'STATEMENT_TEXT_NOT_FOUND'],
    'oversized extraction' => [str_repeat('x', 2097153), 'text/csv', 'STATEMENT_TEXT_LIMIT_OR_ENCODING'],
    'unusable encoding' => ["a,\xff", 'text/csv', 'STATEMENT_TEXT_LIMIT_OR_ENCODING'],
    'unrecognized structure' => ["Unmapped bank export\n100", 'text/csv', 'CSV_STRUCTURE_REVIEW_REQUIRED'],
    'inconsistent columns' => ["a,b\n1,2,3", 'text/csv', 'CSV_STRUCTURE_REVIEW_REQUIRED'],
    'too many columns' => [str_repeat('a,', 200).'b', 'text/csv', 'CSV_STRUCTURE_REVIEW_REQUIRED'],
    'too many records' => ["a,b\n".str_repeat("1,2\n", 50000), 'text/csv', 'CSV_STRUCTURE_REVIEW_REQUIRED'],
    'header only' => ["date,amount\n", 'text/csv', 'CSV_DATA_ROWS_REQUIRED'],
]);
