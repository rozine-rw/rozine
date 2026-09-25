<?php

declare(strict_types=1);

namespace App\Infrastructure\Evidence;

use App\Application\Evidence\Contracts\StatementTextExtractor;
use Smalot\PdfParser\Config;
use Smalot\PdfParser\Parser;
use SplTempFileObject;
use Throwable;

/** @phpstan-import-type Extraction from StatementTextExtractor */
final class PdfAndCsvTextExtractor implements StatementTextExtractor
{
    public const MAX_TEXT_BYTES = 2 * 1024 * 1024;

    /** @return Extraction */
    public function extract(string $content, string $mediaType): array
    {
        if (! in_array($mediaType, ['application/pdf', 'text/csv'], true)) {
            return $this->review('unsupported-1', 'STATEMENT_TYPE_UNSUPPORTED');
        }
        $version = $mediaType === 'application/pdf' ? 'smalot-pdfparser-2.12.5' : 'utf8-csv-2';
        $text = $content;
        $records = null;
        if ($mediaType === 'application/pdf') {
            $config = new Config;
            $config->setRetainImageContent(false);
            $config->setDecodeMemoryLimit(8 * 1024 * 1024);
            try {
                $document = (new Parser([], $config))->parseContent($content);
                $text = $document->getText();
            } catch (Throwable) {
                return $this->review($version, 'PDF_TEXT_UNAVAILABLE');
            }
        }
        if (strlen($text) > self::MAX_TEXT_BYTES || ! mb_check_encoding($text, 'UTF-8')) {
            return $this->review($version, 'STATEMENT_TEXT_LIMIT_OR_ENCODING');
        }
        if (trim($text) === '') {
            return $this->review($version, 'STATEMENT_TEXT_NOT_FOUND');
        }
        if ($mediaType === 'text/csv') {
            $stream = new SplTempFileObject;
            $stream->fwrite(str_replace(["\r\n", "\r"], "\n", $text));
            $stream->rewind();
            $columns = null;
            $records = 0;
            while (! $stream->eof()) {
                $row = $stream->fgetcsv(',', '"', '');
                if ($row === [null] || $row === false) {
                    continue;
                }
                $columns ??= count($row);
                if ($columns < 2 || $columns > 200 || count($row) !== $columns || $records >= 50000) {
                    return $this->review($version, 'CSV_STRUCTURE_REVIEW_REQUIRED');
                }
                $records++;
            }
            if ($records < 2) {
                return $this->review($version, 'CSV_DATA_ROWS_REQUIRED');
            }
        }

        return ['parser_version' => $version, 'status' => 'text_extracted', 'reason_codes' => [], 'text' => $text, 'record_count' => $records];
    }

    /** @return Extraction */
    private function review(string $version, string $reason): array
    {
        return ['parser_version' => $version, 'status' => 'needs_review', 'reason_codes' => [$reason], 'text' => null, 'record_count' => null];
    }
}
