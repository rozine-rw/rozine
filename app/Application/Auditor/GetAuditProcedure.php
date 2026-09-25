<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditReportStore;
use App\Domain\Auditor\AuditProcedure;
use App\Domain\Auditor\AuditReportDecision;
use App\Domain\Auditor\AuditVariance;

/**
 * @phpstan-import-type Procedure from AuditReportStore
 * @phpstan-import-type Draft from AuditProcedure
 */
final class GetAuditProcedure
{
    public function __construct(private AuditReportStore $store, private AuditProcedure $procedure, private AuditVariance $variance, private BuildAuditReportPreview $preview) {}

    /**
     * @param  array<string, mixed>  $preview
     * @return array<string, mixed>
     */
    public function handle(int $userId, int $contextRevision, string $reportId, array $preview = [], ?string $step = null): array
    {
        $page = $this->store->procedure($userId, $contextRevision, $reportId);
        $report = $page['report'];
        $storedStep = $report['step'];
        $report['step'] = $this->procedure->selectStep($report['kind'], $storedStep, $step);
        $steps = $this->procedure->steps($report['kind']);
        $index = array_search($report['step'], $steps, true);
        /** @var Draft $draft */
        $draft = $report['draft'];
        $unavailable = $this->procedure->unavailable($report['kind'], $storedStep, $draft, $report['step'], $page['sources']);
        $fields = $draft['fields'][$report['step']] ?? [];
        $observed = [...$fields, ...array_intersect_key($preview, array_flip(['observed_stock', 'cash', 'stock_units']))];
        $seal = $report['step'] === 'seal' && $report['status'] === 'draft' ? $this->preview->handle($report, $page['sources']) : null;
        if ($unavailable === null && ($seal['note_missing'] ?? false)) {
            $unavailable = 'AUDIT_NOTE_REQUIRED';
        }

        return [...$page, 'report' => $report,
            'can_seal' => $page['signing_available'] && $report['step'] === 'seal' && $unavailable === null,
            'can_upload_ledger' => $report['status'] === 'draft' && $report['kind'] === 'flash'
                && $storedStep === 'ledger' && $report['step'] === 'ledger',
            'can_amend' => AuditReportDecision::amendable($report['status']) && $report['amendment_id'] === null,
            'decision_options' => $report['kind'] === 'monthly' && $report['status'] === 'draft'
                ? ['request_changes' => AuditReportDecision::CHANGES, 'reject' => AuditReportDecision::REJECTION] : null, 'previous_step' => $index > 0 ? $steps[$index - 1] : null,
            'seal' => $seal, 'can_continue' => $report['status'] === 'draft' && $unavailable === null,
            'variance' => ['ledger' => $this->variance->compare($page['sources']['reported_stock'], $observed['observed_stock'] ?? null),
                'cash' => $this->variance->compare($page['sources']['reported_cash'], $observed['cash'] ?? null),
                'stock' => $this->variance->compare($page['sources']['reported_units'], $observed['stock_units'] ?? null)],
            'unavailable' => $unavailable, 'steps' => array_map(fn (string $step): array => ['key' => $step,
                'state' => $step === $report['step'] ? 'current' : (in_array($step, $draft['completed_steps'], true) ? 'done' : 'todo')], $steps)];
    }
}
