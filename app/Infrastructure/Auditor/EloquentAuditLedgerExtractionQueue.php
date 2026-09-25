<?php

declare(strict_types=1);

namespace App\Infrastructure\Auditor;

use App\Application\Auditor\Contracts\AuditLedgerExtractionQueue;
use App\Application\Evidence\Contracts\StatementTextExtractor;
use App\Models\AuditLedgerExtraction;
use App\Models\AuditLedgerOriginal;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use LogicException;
use RuntimeException;
use Throwable;

final class EloquentAuditLedgerExtractionQueue implements AuditLedgerExtractionQueue
{
    public function __construct(private StatementTextExtractor $extractor) {}

    public function processPending(int $limit): int
    {
        if (DB::transactionLevel() !== 0 || $limit < 1 || $limit > 20) {
            throw new LogicException('Audit ledger extraction requires committed originals and a batch of 1 to 20.');
        }
        $pending = AuditLedgerExtraction::query()->where('status', 'pending')
            ->whereNotExists(fn (Builder $query): Builder => $query->selectRaw('1')->from('audit_ledger_extractions as finished')
                ->whereColumn('finished.audit_ledger_original_id', 'audit_ledger_extractions.audit_ledger_original_id')
                ->whereColumn('finished.revision', '>', 'audit_ledger_extractions.revision'))
            ->orderBy('id')->limit($limit)->get(['audit_ledger_original_id']);
        $completed = 0;
        foreach ($pending as $item) {
            $original = AuditLedgerOriginal::query()->findOrFail($item->audit_ledger_original_id);
            try {
                $content = $original->content;
                if (! hash_equals($original->sha256, hash('sha256', $content)) || strlen($content) !== $original->size_bytes) {
                    throw new RuntimeException('Audit ledger source integrity check failed.');
                }
                $extraction = $this->extractor->extract($content, $original->media_type);
            } catch (Throwable) {
                $extraction = ['parser_version' => 'isolated-parser-1', 'status' => 'needs_review',
                    'reason_codes' => ['STATEMENT_EXTRACTION_FAILED_OR_LIMITED'], 'text' => null, 'record_count' => null];
            }
            unset($content);
            $completed += DB::transaction(function () use ($original, $extraction): int {
                AuditLedgerOriginal::query()->whereKey($original->id)->lockForUpdate()->firstOrFail(['id']);
                $latest = AuditLedgerExtraction::query()->where('audit_ledger_original_id', $original->id)->orderByDesc('revision')->firstOrFail();
                if ($latest->status !== 'pending') {
                    return 0;
                }
                (new AuditLedgerExtraction)->forceFill([...$extraction, 'audit_ledger_original_id' => $original->id, 'revision' => $latest->revision + 1])->save();

                return 1;
            });
        }

        return $completed;
    }
}
