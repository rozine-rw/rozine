<?php

declare(strict_types=1);

namespace App\Infrastructure\Evidence;

use App\Application\Evidence\Contracts\StatementExtractionQueue;
use App\Application\Evidence\Contracts\StatementTextExtractor;
use App\Models\StatementExtraction;
use App\Models\StatementOriginal;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use LogicException;
use RuntimeException;
use Throwable;

final class EloquentStatementExtractionQueue implements StatementExtractionQueue
{
    public function __construct(private StatementTextExtractor $extractor) {}

    public function processPending(int $limit): int
    {
        if (DB::transactionLevel() !== 0 || $limit < 1 || $limit > 20) {
            throw new LogicException('Statement extraction requires committed originals and a batch of 1 to 20.');
        }
        $pending = StatementExtraction::query()->where('status', 'pending')
            ->whereNotExists(fn (Builder $query): Builder => $query->selectRaw('1')->from('statement_extractions as finished')
                ->whereColumn('finished.statement_original_id', 'statement_extractions.statement_original_id')
                ->whereColumn('finished.revision', '>', 'statement_extractions.revision'))
            ->orderBy('id')->limit($limit)->get(['statement_original_id']);
        $completed = 0;
        foreach ($pending as $item) {
            $original = StatementOriginal::query()->findOrFail($item->statement_original_id);
            try {
                $content = $original->content;
                if (! hash_equals($original->sha256, hash('sha256', $content)) || strlen($content) !== $original->size_bytes) {
                    throw new RuntimeException('Statement source integrity check failed.');
                }
                $extraction = $this->extractor->extract($content, $original->media_type);
            } catch (Throwable) {
                $extraction = ['parser_version' => 'isolated-parser-1', 'status' => 'needs_review',
                    'reason_codes' => ['STATEMENT_EXTRACTION_FAILED_OR_LIMITED'], 'text' => null, 'record_count' => null];
            }
            unset($content);
            $completed += DB::transaction(function () use ($original, $extraction): int {
                StatementOriginal::query()->whereKey($original->id)->lockForUpdate()->firstOrFail(['id']);
                $latest = StatementExtraction::query()->where('statement_original_id', $original->id)->orderByDesc('revision')->firstOrFail();
                if ($latest->status !== 'pending') {
                    return 0;
                }
                (new StatementExtraction)->forceFill([...$extraction, 'statement_original_id' => $original->id, 'revision' => $latest->revision + 1])->save();

                return 1;
            });
        }

        return $completed;
    }
}
