<?php

declare(strict_types=1);

namespace App\Infrastructure\Evidence;

use App\Application\Evidence\Contracts\StatementTextExtractor;
use Illuminate\Support\Facades\Process;
use Throwable;

/** @phpstan-import-type Extraction from StatementTextExtractor */
final class IsolatedStatementTextExtractor implements StatementTextExtractor
{
    /** @return Extraction */
    public function extract(string $content, string $mediaType): array
    {
        try {
            $result = Process::input($content)->path(base_path())->timeout(8)
                ->env(array_fill_keys(array_keys(getenv()), false))
                ->run([PHP_BINARY, '-d', 'memory_limit=128M', '-d', 'max_execution_time=5',
                    '-d', 'disable_functions=tmpfile', '-d', 'log_errors=0', '-d', 'display_errors=0',
                    '-r', <<<'PHP_CODE'
                        require 'vendor/autoload.php';
                        $content = stream_get_contents(STDIN, 10485761);
                        $result = (new App\Infrastructure\Evidence\PdfAndCsvTextExtractor)->extract($content, $argv[1]);
                        echo json_encode($result, JSON_THROW_ON_ERROR);
                        PHP_CODE, $mediaType]);
            if (! $result->successful()) {
                throw new \RuntimeException('Isolated statement parser did not finish.');
            }
            $extraction = json_decode($result->output(), true, flags: JSON_THROW_ON_ERROR);
            if (! is_array($extraction) || ! isset($extraction['parser_version'], $extraction['status'], $extraction['reason_codes'])
                || ! in_array($extraction['status'], ['text_extracted', 'needs_review'], true)
                || ! array_key_exists('text', $extraction) || ! array_key_exists('record_count', $extraction)) {
                throw new \RuntimeException('Isolated statement parser returned an invalid result.');
            }

            /** @var Extraction $extraction */
            return $extraction;
        } catch (Throwable) {
            return ['parser_version' => 'isolated-parser-1', 'status' => 'needs_review',
                'reason_codes' => ['STATEMENT_EXTRACTION_FAILED_OR_LIMITED'], 'text' => null, 'record_count' => null];
        }
    }
}
