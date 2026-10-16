<?php

declare(strict_types=1);

use App\Application\Evidence\Contracts\StatementExtractionQueue;
use App\Application\Evidence\Contracts\StatementTextExtractor;
use App\Application\Evidence\FindStatementOperation;
use App\Application\Evidence\GetStatementEvidence;
use App\Application\Evidence\IngestStatement;
use App\Domain\Evidence\StatementSource;
use App\Models\StatementExtraction;
use App\Models\StatementOriginal;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\BusinessApplicationFixture;
use Tests\Support\StatementFixture;

it('processes a committed original once and keeps the recorded ingestion receipt immutable', function (): void {
    $fixture = BusinessApplicationFixture::make();
    $request = (string) Str::uuid();
    $receipt = StatementFixture::ingest($fixture, requestId: $request);
    expect(Artisan::call('statements:extract', ['--limit' => '1']))->toBe(0);
    $original = StatementOriginal::query()->firstOrFail();
    $extraction = StatementExtraction::query()->where('revision', 2)->firstOrFail();
    expect($extraction->text)->toBe(StatementFixture::csv())
        ->and($extraction->getRawOriginal('text'))->not->toContain('SYNTHETIC-ONLY')
        ->and($original->content)->toBe(StatementFixture::csv())
        ->and(app(StatementExtractionQueue::class)->processPending(5))->toBe(0)
        ->and(StatementFixture::ingest($fixture, requestId: $request))->toBe($receipt)
        ->and(app(FindStatementOperation::class)->handle($fixture['authority']['users'][0]->id, 1, $request))->toBe($receipt)
        ->and(app(GetStatementEvidence::class)->handle($fixture['authority']['users'][0]->id, 1, $fixture['business']->id)['documents'][0]['extraction']['status'])->toBe('text_extracted');
    $this->assertDatabaseCount('statement_extractions', 2);
});

it('finishes a hostile PDF with a stable review result while its original and UUID remain available', function (): void {
    $fixture = BusinessApplicationFixture::make();
    $content = StatementFixture::expandingPdf();
    $request = (string) Str::uuid();
    $ingest = fn (): array => app(IngestStatement::class)->handle($fixture['authority']['users'][0]->id, 1, $fixture['business']->id, 0, 'original.pdf', $content, $request);
    $receipt = $ingest();
    expect(app(StatementExtractionQueue::class)->processPending(1))->toBe(1)
        ->and(StatementExtraction::query()->where('revision', 2)->firstOrFail()->status)->toBe('needs_review')
        ->and(StatementOriginal::query()->firstOrFail()->content)->toBe($content)
        ->and($ingest())->toBe($receipt)->and(app(StatementExtractionQueue::class)->processPending(1))->toBe(0);
});

it('ingests and extracts a maximum-size benign PDF with bounded parent memory', function (): void {
    $fixture = BusinessApplicationFixture::make();
    memory_reset_peak_usage();
    $baseline = memory_get_usage(true);
    $content = StatementFixture::pdf();
    $content .= str_repeat("\n", StatementSource::MAX_BYTES - strlen($content));
    $receipt = app(IngestStatement::class)->handle($fixture['authority']['users'][0]->id, 1, $fixture['business']->id, 0,
        'maximum.pdf', $content, (string) Str::uuid());
    expect($receipt['code'])->toBe('INGESTED_NOT_AUDIT_APPROVED')
        ->and(app(StatementExtractionQueue::class)->processPending(1))->toBe(1)
        ->and(StatementExtraction::query()->where('revision', 2)->firstOrFail()->status)->toBe('text_extracted')
        ->and(memory_get_peak_usage(true) - $baseline)->toBeLessThan(160 * 1024 * 1024);
});

it('converts an unexpected parser Error into a terminal review outside every database transaction', function (): void {
    $fixture = BusinessApplicationFixture::make();
    StatementFixture::ingest($fixture);
    $extractor = $this->createMock(StatementTextExtractor::class);
    $extractor->expects($this->once())->method('extract')->willReturnCallback(function (): never {
        expect(DB::transactionLevel())->toBe(0);
        throw new Error('Synthetic parser crash with private details');
    });
    $this->app->instance(StatementTextExtractor::class, $extractor);
    expect(app(StatementExtractionQueue::class)->processPending(1))->toBe(1)
        ->and(app(StatementExtractionQueue::class)->processPending(1))->toBe(0)
        ->and(StatementExtraction::query()->where('revision', 2)->firstOrFail()->reason_codes)->toBe(['STATEMENT_EXTRACTION_FAILED_OR_LIMITED']);
});

it('refuses source corruption before sending any plaintext to a parser', function (): void {
    $original = StatementOriginal::factory()->create(['sha256' => str_repeat('0', 64)]);
    StatementExtraction::factory()->create(['statement_original_id' => $original->id, 'status' => 'pending', 'text' => null]);
    $extractor = $this->createMock(StatementTextExtractor::class);
    $extractor->expects($this->never())->method('extract');
    $this->app->instance(StatementTextExtractor::class, $extractor);
    expect(app(StatementExtractionQueue::class)->processPending(1))->toBe(1)
        ->and(StatementExtraction::query()->where('revision', 2)->firstOrFail()->status)->toBe('needs_review');
});

it('does not append twice if another worker finishes during parsing', function (): void {
    $fixture = BusinessApplicationFixture::make();
    $receipt = StatementFixture::ingest($fixture);
    $extractor = $this->createMock(StatementTextExtractor::class);
    $extractor->method('extract')->willReturnCallback(function () use ($receipt): array {
        StatementExtraction::factory()->create(['statement_original_id' => $receipt['data']['document_id'], 'revision' => 2]);

        return ['parser_version' => 'test-1', 'status' => 'text_extracted', 'reason_codes' => [], 'text' => 'unused', 'record_count' => 2];
    });
    $this->app->instance(StatementTextExtractor::class, $extractor);
    expect(app(StatementExtractionQueue::class)->processPending(1))->toBe(0);
    $this->assertDatabaseCount('statement_extractions', 2);
});

it('refuses unbounded batches and extraction from inside a caller transaction', function (): void {
    $queue = app(StatementExtractionQueue::class);
    expect(fn () => $queue->processPending(0))->toThrow(LogicException::class)
        ->and(fn () => $queue->processPending(21))->toThrow(LogicException::class)
        ->and(fn () => DB::transaction(fn (): int => $queue->processPending(1)))->toThrow(LogicException::class);
    expect(Artisan::call('statements:extract', ['--limit' => 'invalid']))->toBe(1);
});
