<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Evidence\GetAuditStatements;
use App\Application\Evidence\GetAuditStatementVerification;
use App\Domain\Auditor\AuditMonthlyFacts;

/**
 * @phpstan-import-type Source from \App\Domain\Auditor\AuditProcedure
 * @phpstan-import-type Document from \App\Application\Evidence\Contracts\StatementStore
 * @phpstan-import-type Monthly from AuditMonthlyFacts
 * @phpstan-import-type AcceptedAssignment from \App\Application\Auditor\Contracts\AuditAssignmentStore
 * @phpstan-import-type SourceFacts from \App\Application\Auditor\Contracts\AuditSourceFactsStore
 *
 * @phpstan-type Projection array{verification: Source|null, check_in: Source|null, photos: Source|null, declaration: Source|null, reported_stock: string|null, reported_cash: string|null, reported_units: string|null, financial_proofs: list<string>, inventory_proofs: list<string>, extra_photos: list<string>, documents: list<Document>, ledger_documents?: list<Document>, ledger_sources?: array<string, Source>, monthly: Monthly|null, licence: string, source_facts: SourceFacts|null}
 */
final class GetAuditProcedureSources
{
    public function __construct(private GetAuditStatementVerification $statements, private GetAuditStatements $files, private AuditMonthlyFacts $monthly, private GetAuditSourceFacts $facts) {}

    /**
     * Called while the report store holds this exact accepted assignment's authority.
     *
     * @param  AcceptedAssignment  $assignment
     * @return Projection
     */
    public function handle(int $userId, int $contextRevision, array $assignment, string $kind, ?string $period): array
    {
        $verification = $this->statements->handle($userId, $contextRevision, $assignment['id']);
        $file = $this->files->handle($userId, $contextRevision, $assignment['id']);
        $facts = $this->facts->forAccepted($assignment);
        $pin = $facts === null ? null : ['id' => $facts['source']['id'], 'revision' => $facts['source']['revision'], 'sha256' => $facts['source']['sha256']];
        $checkIn = $facts !== null && $facts['check_in']['at'] !== null && $facts['check_in']['position'] !== null
            && $facts['check_in']['review_required'] === false;
        $photos = $facts === null ? [] : [...$facts['photos']['required'] ?? [], ...$facts['photos']['extra'] ?? []];
        $photosReady = $facts !== null && $facts['photos']['required'] !== null && $facts['photos']['required'] !== []
            && $facts['photos']['extra'] !== null && count($photos) <= 5
            && array_all($photos, fn (array $photo): bool => $photo['captured_at'] !== null && $photo['position'] !== null);
        $declaration = $facts !== null && ($kind === 'monthly'
            ? $facts['declared_stock_units'] !== null && $facts['declared_unit_label'] !== null : $facts['declared_stock_rwf'] !== null);
        $current = $verification !== null && $verification['current'];
        $monthly = $current && $file['transcription'] !== null
            ? $this->monthly->project($period, $verification['payload']['observations'], $file['transcription']['payload']['statements']) : null;

        return ['verification' => $current && ($kind !== 'monthly' || $monthly !== null)
            ? ['id' => $verification['id'], 'revision' => $verification['revision'], 'sha256' => $verification['sha256']] : null,
            'check_in' => $checkIn ? $pin : null, 'photos' => $photosReady ? $pin : null, 'declaration' => $declaration ? $pin : null,
            'reported_stock' => $facts['declared_stock_rwf'] ?? null, 'reported_cash' => $monthly['closing_balance'] ?? null,
            'reported_units' => $facts['declared_stock_units'] ?? null,
            'financial_proofs' => $facts['proof_ids']['financial'] ?? [], 'inventory_proofs' => $facts['proof_ids']['inventory'] ?? [],
            'extra_photos' => array_column($facts['photos']['extra'] ?? [], 'id'),
            'documents' => $file['evidence']['documents'], 'monthly' => $monthly, 'licence' => $file['assignment']['accreditation']['licence'], 'source_facts' => $facts];
    }
}
