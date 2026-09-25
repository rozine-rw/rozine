<?php

declare(strict_types=1);

use App\Application\Auditor\Contracts\AuditLedgerExtractionQueue;
use App\Application\Auditor\FindAuditReportOperation;
use App\Application\Auditor\GetAuditProcedure;
use App\Application\Auditor\IngestAuditLedger;
use App\Application\Evidence\Contracts\StatementTextExtractor;
use App\Application\Evidence\GetAuditStatementVerification;
use App\Models\AuditLedgerExtraction;
use App\Models\AuditLedgerOriginal;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\AuditLedgerFixture;
use Tests\Support\StatementFixture;

it('extracts committed report originals without changing a receipt or Business verification', function (): void {
    ['user' => $user, 'report' => $report] = AuditLedgerFixture::ready();
    $request = (string) Str::uuid();
    $content = "date,amount\n2026-08-01,38000000\n";
    $upload = fn (): array => app(IngestAuditLedger::class)->handle($user->id, 1, $report->id, 4, 'ledger.csv', $content, null, $request);
    $receipt = $upload();
    $verification = app(GetAuditStatementVerification::class)->handle($user->id, 1, $report->assignment_id);
    expect(Artisan::call('statements:extract', ['--limit' => '1']))->toBe(0)
        ->and(app(AuditLedgerExtractionQueue::class)->processPending(1))->toBe(0)->and($upload())->toBe($receipt)
        ->and(app(FindAuditReportOperation::class)->handle($user->id, 1, 'audit.save_step', $request))->toBe($receipt)
        ->and(app(GetAuditStatementVerification::class)->handle($user->id, 1, $report->assignment_id))->toBe($verification);
    $parsed = AuditLedgerExtraction::query()->where('revision', 2)->firstOrFail();
    expect($parsed->text)->toBe($content)->and($parsed->getRawOriginal('text'))->not->toContain('38000000')
        ->and($parsed->toArray())->not->toHaveKey('text')
        ->and(app(GetAuditProcedure::class)->handle($user->id, 1, $report->id)['sources']['ledger_documents'][0]['extraction']['status'])->toBe('text_extracted');
    $this->assertDatabaseCount('audit_ledger_extractions', 2);
});

it('retains a hostile PDF while bounding extraction outside report and journal locks', function (): void {
    ['user' => $user, 'report' => $report] = AuditLedgerFixture::ready();
    $content = StatementFixture::expandingPdf();
    app(IngestAuditLedger::class)->handle($user->id, 1, $report->id, 4, 'ledger.pdf', $content, null, (string) Str::uuid());
    expect(app(AuditLedgerExtractionQueue::class)->processPending(1))->toBe(1)
        ->and(AuditLedgerExtraction::query()->where('revision', 2)->firstOrFail()->status)->toBe('needs_review')
        ->and(AuditLedgerOriginal::query()->firstOrFail()->content)->toBe($content);
});

it('records parser failures and corrupt originals without exposing private exception details', function (string $failure): void {
    ['user' => $user, 'report' => $report] = AuditLedgerFixture::ready();
    $original = AuditLedgerOriginal::factory()->create(['audit_report_id' => $report->id, 'report_revision' => 5,
        ...match ($failure) {
            'hash' => ['sha256' => str_repeat('0', 64)], 'length' => ['size_bytes' => 1], default => [],
        }]);
    AuditLedgerExtraction::factory()->create(['audit_ledger_original_id' => $original->id, 'status' => 'pending']);
    $extractor = $this->createMock(StatementTextExtractor::class);
    if ($failure === 'parser') {
        $extractor->expects($this->once())->method('extract')->willReturnCallback(function (): never {
            expect(DB::transactionLevel())->toBe(0);
            throw new Error('Private parser detail');
        });
    } else {
        $extractor->expects($this->never())->method('extract');
    }
    $this->app->instance(StatementTextExtractor::class, $extractor);
    expect(app(AuditLedgerExtractionQueue::class)->processPending(1))->toBe(1)
        ->and(AuditLedgerExtraction::query()->where('revision', 2)->firstOrFail()->reason_codes)->toBe(['STATEMENT_EXTRACTION_FAILED_OR_LIMITED']);
})->with(['parser', 'hash', 'length']);

it('does not append a second extraction when another worker finishes during parsing', function (): void {
    ['user' => $user, 'report' => $report] = AuditLedgerFixture::ready();
    $receipt = app(IngestAuditLedger::class)->handle($user->id, 1, $report->id, 4, 'ledger.csv', "amount\n50\n", null, (string) Str::uuid());
    $extractor = $this->createMock(StatementTextExtractor::class);
    $extractor->method('extract')->willReturnCallback(function () use ($receipt): array {
        expect(DB::transactionLevel())->toBe(0);
        AuditLedgerExtraction::factory()->create(['audit_ledger_original_id' => $receipt['data']['document_id'], 'revision' => 2]);

        return ['parser_version' => 'test', 'status' => 'text_extracted', 'reason_codes' => [], 'text' => 'unused', 'record_count' => 1];
    });
    $this->app->instance(StatementTextExtractor::class, $extractor);
    expect(app(AuditLedgerExtractionQueue::class)->processPending(1))->toBe(0);
    $this->assertDatabaseCount('audit_ledger_extractions', 2);
});

it('refuses unbounded extraction batches and extraction inside caller transactions', function (): void {
    $queue = app(AuditLedgerExtractionQueue::class);
    expect(fn () => $queue->processPending(0))->toThrow(LogicException::class)
        ->and(fn () => $queue->processPending(21))->toThrow(LogicException::class)
        ->and(fn () => DB::transaction(fn (): int => $queue->processPending(1)))->toThrow(LogicException::class);
});
