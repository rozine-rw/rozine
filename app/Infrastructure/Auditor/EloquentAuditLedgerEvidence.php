<?php

declare(strict_types=1);

namespace App\Infrastructure\Auditor;

use App\Application\Auditor\Contracts\AuditLedgerEvidence;
use App\Domain\Evidence\StatementSource;
use App\Domain\Operations\CommandRejection;
use App\Models\AuditLedgerExtraction;
use App\Models\AuditLedgerOriginal;
use RuntimeException;

/**
 * Only called under the report store's accepted-assignment authority and report lock.
 *
 * @phpstan-import-type Document from \App\Application\Evidence\Contracts\StatementStore
 * @phpstan-import-type Original from \App\Application\Evidence\Contracts\StatementStore
 * @phpstan-import-type Reference from AuditLedgerEvidence
 */
final class EloquentAuditLedgerEvidence implements AuditLedgerEvidence
{
    public function __construct(private StatementSource $sources) {}

    /**
     * @param  list<Reference>  $references
     * @return Reference
     */
    public function retain(string $reportId, int $reportRevision, string $partyId, int $userId, array $references, string $filename, string $content, ?string $replaces): array
    {
        if ($replaces !== null && (! in_array($replaces, array_column($references, 'id'), true)
            || ! AuditLedgerOriginal::query()->where('audit_report_id', $reportId)->whereKey($replaces)->exists())) {
            throw new CommandRejection('STATEMENT_NOT_FOUND', 404);
        }
        $source = $this->sources->describe($filename, $content);
        $original = AuditLedgerOriginal::query()->where('audit_report_id', $reportId)->where('sha256', $source['sha256'])->first();
        if ($original === null) {
            $original = new AuditLedgerOriginal;
            $original->forceFill([...$source, 'audit_report_id' => $reportId, 'report_revision' => $reportRevision + 1,
                'content' => $content, 'actor_user_id' => $userId, 'actor_party_id' => $partyId])->save();
            (new AuditLedgerExtraction)->forceFill(['audit_ledger_original_id' => $original->id, 'revision' => 1,
                'parser_version' => 'pending-1', 'status' => 'pending', 'reason_codes' => [], 'text' => null, 'record_count' => null])->save();
        }

        return ['id' => $original->id, 'revision' => $original->report_revision, 'sha256' => $original->sha256,
            'replaces' => $original->id === $replaces ? null : $replaces];
    }

    /**
     * @param  list<Reference>  $references
     * @return list<Document>
     */
    public function documents(string $reportId, array $references): array
    {
        $documents = [];
        foreach ($references as $reference) {
            $original = AuditLedgerOriginal::query()->where('audit_report_id', $reportId)->whereKey($reference['id'])->first();
            if ($original === null || $original->sha256 !== $reference['sha256'] || $original->report_revision !== $reference['revision']) {
                throw new RuntimeException('AUDIT_LEDGER_INTEGRITY_FAILED');
            }
            $extraction = AuditLedgerExtraction::query()->where('audit_ledger_original_id', $original->id)->orderByDesc('revision')->firstOrFail();
            $documents[] = ['id' => $original->id, 'filename' => $this->name($original), 'sha256' => $original->sha256,
                'media_type' => $original->media_type, 'size_bytes' => $original->size_bytes, 'received_at' => $original->created_at->toAtomString(),
                'extraction' => ['id' => $extraction->id, 'revision' => $extraction->revision, 'parser_version' => $extraction->parser_version,
                    'status' => $extraction->status, 'reason_codes' => $extraction->reason_codes, 'record_count' => $extraction->record_count]];
        }

        return $documents;
    }

    /** @return Original */
    public function read(string $reportId, string $documentId): array
    {
        $original = AuditLedgerOriginal::query()->where('audit_report_id', $reportId)->find($documentId);
        if ($original === null) {
            throw new CommandRejection('STATEMENT_NOT_FOUND', 404);
        }
        $content = $original->content;
        if (! hash_equals($original->sha256, hash('sha256', $content)) || strlen($content) !== $original->size_bytes) {
            throw new RuntimeException('AUDIT_LEDGER_INTEGRITY_FAILED');
        }

        return ['filename' => $this->name($original), 'media_type' => $original->media_type, 'sha256' => $original->sha256, 'content' => $content];
    }

    private function name(AuditLedgerOriginal $original): string
    {
        return 'ledger-'.strtolower(substr($original->id, -8)).($original->media_type === 'application/pdf' ? '.pdf' : '.csv');
    }
}
