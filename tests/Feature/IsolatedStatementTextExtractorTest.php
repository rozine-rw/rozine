<?php

declare(strict_types=1);

use App\Application\Evidence\Contracts\StatementTextExtractor;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Tests\Support\StatementFixture;

it('extracts a benign PDF in a bounded child with no application environment or temporary plaintext fallback', function (): void {
    $result = app(StatementTextExtractor::class)->extract(StatementFixture::pdf(), 'application/pdf');
    expect($result['status'])->toBe('text_extracted')->and($result['text'])->toContain('Synthetic statement fixture only');
});

it('contains stream expansion and cyclic page trees in the parser process', function (string $attack): void {
    $content = $attack === 'streams' ? StatementFixture::expandingPdf() : StatementFixture::pdfObjects([
        '<< /Type /Catalog /Pages 2 0 R >>', '<< /Type /Pages /Kids [2 0 R] /Count 1 >>',
    ]);
    $start = microtime(true);
    $result = app(StatementTextExtractor::class)->extract($content, 'application/pdf');
    expect(strlen($content))->toBeLessThan(600000)->and($result['status'])->toBe('needs_review')
        ->and($result['text'])->toBeNull()->and(microtime(true) - $start)->toBeLessThan(12.0);
})->with(['streams', 'cycle']);

it('keeps encrypted PDFs and parser Errors reviewable without creating a plaintext fallback', function (string $fault): void {
    $objects = ['<< /Type /Catalog /Pages 2 0 R >>', '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
        '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 300 100] /Contents 4 0 R >>',
        "<< /Filter /FlateDecode /Length 8 >>\nstream\nNOTFLATE\nendstream"];
    $content = StatementFixture::pdfObjects($objects, $fault === 'encrypted' ? '/Encrypt 4 0 R' : '');
    $result = app(StatementTextExtractor::class)->extract($content, 'application/pdf');
    expect($result['status'])->toBe('needs_review')->and($result['text'])->toBeNull()
        ->and($result['reason_codes'])->toBe(['PDF_TEXT_UNAVAILABLE']);
})->with(['encrypted', 'parser Error']);

it('records a stable safe failure for child termination or a malformed response', function (string $fault): void {
    Process::fake(fn () => $fault === 'timeout' ? throw new RuntimeException('Synthetic timeout with private diagnostics') : Process::result(
        output: match ($fault) {
            'invalid json' => 'private malformed output',
            'missing fields' => '{}',
            'unknown status' => '{"parser_version":"test","status":"pending","reason_codes":[],"text":null,"record_count":null}',
            default => '',
        }, errorOutput: 'private parser diagnostics', exitCode: $fault === 'terminated' ? 137 : 0));
    $content = StatementFixture::pdf();
    $result = app(StatementTextExtractor::class)->extract($content, 'application/pdf');
    expect($result)->toBe(['parser_version' => 'isolated-parser-1', 'status' => 'needs_review',
        'reason_codes' => ['STATEMENT_EXTRACTION_FAILED_OR_LIMITED'], 'text' => null, 'record_count' => null]);
    if ($fault !== 'timeout') {
        Process::assertRan(fn (PendingProcess $process): bool => $process->timeout === 8 && $process->input === $content
            && is_array($process->command) && in_array('memory_limit=128M', $process->command, true)
            && in_array('disable_functions=tmpfile', $process->command, true)
            && array_filter($process->environment, fn (mixed $value): bool => $value !== false) === []);
    }
})->with(['terminated', 'invalid json', 'missing fields', 'unknown status', 'timeout']);
