<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Operations\Contracts\CanonicalJson;
use App\Domain\Evidence\StatementAuditReview;
use App\Domain\Underwriting\ExactFinancialValue;

/**
 * @phpstan-import-type Report from \App\Application\Auditor\Contracts\AuditReportStore
 * @phpstan-import-type Projection from GetAuditProcedureSources
 */
final class BuildAuditReportPreview
{
    public const FINDINGS_VERSION = 'audit-factual-findings-1';

    public function __construct(private CanonicalJson $json) {}

    /**
     * Builds a preview of persisted observations only; query-string variance previews never enter it.
     *
     * @param  Report  $report
     * @param  Projection  $sources
     * @return array<string, mixed>
     */
    public function handle(array $report, array $sources): array
    {
        $draft = $report['draft'];
        $fields = $draft['fields'][$report['kind'] === 'flash' ? 'ledger' : 'count'] ?? [];
        $findings = [];
        $comparisons = $report['kind'] === 'flash'
            ? [['stock_value', $sources['reported_stock'], $fields['observed_stock'] ?? null, $sources['declaration']['id'] ?? null]]
            : [['cash', $sources['reported_cash'], $fields['cash'] ?? null, $sources['verification']['id'] ?? null],
                ['stock_units', $sources['reported_units'], $fields['stock_units'] ?? null, $sources['declaration']['id'] ?? null]];
        $requiresNote = false;
        foreach ($comparisons as [$measure, $reported, $observed, $sourceId]) {
            if ($reported === null || $observed === null || $sourceId === null) {
                continue;
            }
            $difference = (string) ExactFinancialValue::amount($observed)->minus(ExactFinancialValue::amount($reported));
            $requiresNote = $requiresNote || $difference !== '0';
            $findings[] = ['code' => strtoupper($measure).($difference === '0' ? '_MATCHED' : '_DIFFERENCE'),
                'measure' => $measure, 'reported' => $reported, 'observed' => $observed, 'difference' => $difference, 'evidence_ids' => [$sourceId]];
        }
        if ($report['kind'] === 'monthly' && isset($fields['operational_status'])) {
            $findings[] = ['code' => 'OPERATIONAL_STATUS_OBSERVED', 'measure' => 'operational_status',
                'observed' => $fields['operational_status'], 'evidence_ids' => $sources['photos'] === null ? [] : [$sources['photos']['id']]];
            $requiresNote = $requiresNote || $fields['operational_status'] !== 'active';
        }
        $sourcePins = array_intersect_key($sources, array_flip(['verification', 'check_in', 'photos', 'declaration']));
        $originals = array_map(fn (array $document): array => ['id' => $document['id'], 'sha256' => $document['sha256'], 'kind' => 'statement'], $sources['documents']);
        foreach ($sources['ledger_documents'] ?? [] as $document) {
            $originals[] = ['id' => $document['id'], 'sha256' => $document['sha256'], 'kind' => 'ledger'];
        }
        usort($originals, fn (array $left, array $right): int => strcmp($left['id'], $right['id']));
        $evidenceVersion = hash('sha256', $this->json->encode(['sources' => $sourcePins, 'originals' => $originals]));
        $payload = ['report_id' => $report['id'], 'report_revision' => $report['revision'], 'assignment_id' => $report['assignment_id'],
            'binding_sha256' => $report['binding_sha256'], 'authority' => $sources['authority'], 'period' => $report['period'], 'licence' => $sources['licence'], 'procedure_version' => StatementAuditReview::PROCEDURE,
            'findings_version' => self::FINDINGS_VERSION, 'evidence_version' => $evidenceVersion, 'findings' => $findings,
            'sources' => $sourcePins, 'source_provenance' => $sources['source_facts']['source'] ?? null, 'originals' => $originals, 'draft' => $draft];

        return ['payload' => $payload, 'digest' => hash('sha256', $this->json->encode($payload)), 'findings' => $findings,
            'findings_version' => self::FINDINGS_VERSION, 'evidence_version' => $evidenceVersion,
            'note_required' => $requiresNote, 'note_missing' => $requiresNote && trim($draft['note']) === ''];
    }
}
