<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditorProcedureResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $page */
        $page = $this->resource;
        $report = $page['report'];
        $file = (new AuditorFileResource(['record' => $page['file'], 'jobs' => $page['jobs']]))->resolve($request);
        $job = $file['job'];
        $prefix = $request->routeIs('api.*') ? 'api.v1.auditor.' : 'auditor.';
        $placeholder = '00000000-0000-0000-0000-000000000000';
        $operation = AuditorJobsResource::link($prefix.'reports.operations.show', ['request_id' => $placeholder]);
        $operation['url'] = str_replace($placeholder, '{request_id}', $operation['url']);
        $allowed = $report['status'] === 'draft' ? ['audit.save_step', 'conflict.declare'] : [];
        if ($page['decision_options'] !== null) {
            $allowed = [...$allowed, 'audit.request_changes', 'audit.reject'];
        }
        if ($page['can_amend']) {
            $allowed[] = 'audit.amend';
        }
        if ($page['can_seal']) {
            $allowed[] = 'audit.seal';
        }
        if ($page['sealed']['can_dispute_uphold'] ?? false) {
            $allowed[] = 'audit.dispute.uphold';
        }
        if ($request->routeIs('api.*') && ! $request->user()?->tokenCan('auditor:command')) {
            $allowed = [];
        }

        if ($page['sealed'] !== null) {
            $page['sealed']['dispute'] = AuditDisputeResource::project($page['sealed']['dispute'] ?? null, $request, 'auditor', ['report' => $report['id']]);
            unset($page['sealed']['can_dispute_uphold']);
        }

        $reasonOptions = $page['decision_options'] === null ? null : $this->reasonOptions($page['decision_options']);
        $upload = $page['can_upload_ledger'] && in_array('audit.save_step', $allowed, true)
            ? ['url' => route($prefix.'reports.save', ['report' => $report['id']], false), 'method' => 'post'] : null;

        return [...AuditorJobsResource::envelope($page['jobs']['identity_context_revision']), 'allowed_actions' => $allowed,
            'engagement' => $file['engagement'],
            'audit' => ['id' => $report['id'], 'kind' => $report['kind'], 'business' => $job['business'], 'district' => $job['district'],
                'distance_km' => $job['distance_km'], 'month' => $report['period'], 'deadline' => $job['deadline'],
                'revision' => $report['revision'], 'reassigned_from' => null, 'amends' => $report['amends_id'] === null ? null
                    : ['report_id' => $report['amends_id'], 'link' => AuditorJobsResource::link($prefix.'reports.show', ['report' => $report['amends_id']])]],
            'assignment' => ['id' => $report['assignment_id'], 'revision' => $job['revision']],
            'steps' => $page['steps'], 'stage' => $page['sealed'] === null || $report['step'] !== 'seal'
                ? $this->stage($report, $page['sources'], $file['file'], $prefix, $page['variance'], $page['seal'], $reasonOptions, $upload, $page['mfa_confirmed'])
                : [...array_diff_key($page['sealed'], ['sources' => true]), 'step' => 'sealed', 'amended_by' => $report['amendment_id'] === null ? null
                    : ['report_id' => $report['amendment_id'], 'link' => AuditorJobsResource::link($prefix.'reports.show', ['report' => $report['amendment_id']])]],
            'can_continue' => $page['can_continue'], 'hint' => $report['status'] === 'draft' ? $this->hint($page['unavailable']) : null,
            'reason_options' => $reasonOptions,
            'links' => ['close' => AuditorJobsResource::link($prefix.'jobs.index'),
                'back' => $page['previous_step'] === null
                    ? AuditorJobsResource::link($prefix.'jobs.show', ['assignment' => $report['assignment_id']])
                    : AuditorJobsResource::link($prefix.'reports.show', ['report' => $report['id'], 'step' => $page['previous_step']]), 'operation' => $operation],
            'actions' => ['dispute_uphold' => in_array('audit.dispute.uphold', $allowed, true) ? ['url' => route($prefix.'reports.disputes.uphold', ['report' => $report['id']], false), 'method' => 'post'] : null, 'save' => ['url' => route($prefix.'reports.save', ['report' => $report['id']], false), 'method' => 'post'],
                'conflict' => in_array('conflict.declare', $allowed, true) ? $file['actions']['conflict'] : null,
                'step_up' => in_array('audit.seal', $allowed, true) ? ['url' => route($prefix.'reports.step-up', ['report' => $report['id']], false), 'method' => 'post'] : null,
                'seal' => in_array('audit.seal', $allowed, true) ? ['url' => route($prefix.'reports.seal', ['report' => $report['id']], false), 'method' => 'post'] : null,
                'request_changes' => in_array('audit.request_changes', $allowed, true) ? ['url' => route($prefix.'reports.request-changes', ['report' => $report['id']], false), 'method' => 'post'] : null,
                'reject' => in_array('audit.reject', $allowed, true) ? ['url' => route($prefix.'reports.reject', ['report' => $report['id']], false), 'method' => 'post'] : null,
                'amend' => in_array('audit.amend', $allowed, true) ? ['url' => route($prefix.'reports.amend', ['report' => $report['id']], false), 'method' => 'post'] : null],
            'outcome' => null, 'jobs' => $file['jobs']];
    }

    /**
     * @param  array<string, mixed>  $report
     * @param  array<string, mixed>  $sources
     * @param  array<string, mixed>  $file
     * @param  array<string, mixed>  $variance
     * @param  array<string, mixed>|null  $seal
     * @param  array<string, mixed>|null  $reasonOptions
     * @param  array{url: string, method: string}|null  $upload
     * @return array<string, mixed>
     */
    private function stage(array $report, array $sources, array $file, string $prefix, array $variance, ?array $seal, ?array $reasonOptions, ?array $upload, bool $mfaConfirmed): array
    {
        if (in_array($report['status'], ['changes_requested', 'rejected'], true)) {
            $decision = $report['draft']['decision'];

            return ['step' => 'returned', 'status' => $report['status'],
                'reason' => ['code' => $decision['code'], 'label' => $this->reasonLabel($decision['code']), 'explanation' => $decision['explanation']],
                'recorded_at' => $decision['recorded_at'], 'amended_by' => $report['amendment_id'] === null ? null
                    : ['report_id' => $report['amendment_id'], 'link' => AuditorJobsResource::link($prefix.'reports.show', ['report' => $report['amendment_id']])]];
        }
        $package = $this->capturePackage($report, $sources);
        $fields = $report['draft']['fields'][$report['step']] ?? [];

        return match ($report['step']) {
            'review' => ['step' => 'review', 'file' => $file],
            'check_in' => ['step' => 'check_in', 'package' => $package, 'check_in' => $this->checkIn($sources)],
            'photos' => ['step' => 'photos', 'package' => $package, 'required' => count($sources['source_facts']['photos']['required'] ?? []),
                'slots' => $this->photoSlots($sources, $fields), 'title_max' => 100],
            'ledger' => ['step' => 'ledger', 'upload' => $upload, 'reported_stock' => AuditorJobsResource::money($sources['reported_stock']),
                'observed_stock' => AuditorJobsResource::money($fields['observed_stock'] ?? null), 'tolerance' => 'RWF 0', 'variance' => $variance['ledger'],
                'documents' => $this->ledgerDocuments($sources['ledger_documents'] ?? [], $report, $prefix), 'ledger_ready' => $sources['verification'] !== null,
                'reconciled' => $fields['reconciled'] ?? false],
            'count' => ['step' => 'count', 'financial_proofs' => $this->proofs($sources['financial_proofs'], $fields['financial_proofs'] ?? []),
                'inventory_proofs' => $this->proofs($sources['inventory_proofs'], $fields['inventory_proofs'] ?? []),
                'cash' => ['observed' => AuditorJobsResource::money($fields['cash'] ?? null), 'statement' => AuditorJobsResource::money($sources['reported_cash']), 'variance' => $variance['cash']],
                'stock' => ['observed_units' => $fields['stock_units'] ?? null, 'reported_units' => $sources['reported_units'], 'variance' => $variance['stock']],
                'tolerance' => 'RWF 0', 'period' => $report['period'] === null ? null : [
                    'from' => $report['period'].'-01', 'to' => CarbonImmutable::parse($report['period'].'-01')->endOfMonth()->toDateString()],
                'account_ref' => $sources['source_facts']['declared_account_label'] ?? __('Unavailable'),
                'sector' => ['label' => $sources['source_facts']['declared_sector_label'] ?? __('Unavailable'),
                    'definition' => $sources['source_facts']['declared_unit_label'] ?? __('A declared inventory unit definition is required.')],
                'operational_status' => $fields['operational_status'] ?? null],
            'seal' => $this->seal($report, $sources, $seal ?? throw new \LogicException('A persisted report preview is required.'), $reasonOptions, $mfaConfirmed),
            default => ['step' => 'statements', 'statements' => $this->statements($report, $sources, $prefix)],
        };
    }

    /**
     * @param  array<string, mixed>  $report
     * @param  array<string, mixed>  $sources
     * @return array<string, mixed>
     */
    private function capturePackage(array $report, array $sources): array
    {
        $facts = $sources['source_facts'];
        $photos = [...$facts['photos']['required'] ?? [], ...$facts['photos']['extra'] ?? []];
        $includeCheckIn = $report['kind'] === 'flash';
        $complete = $sources['photos'] !== null && (! $includeCheckIn || $sources['check_in'] !== null);

        return ['status' => $facts === null ? 'not_started' : ($complete ? 'complete' : 'capturing'), 'attention' => null,
            ...($facts === null ? [] : ['source' => $facts['source']['kind']]),
            'received' => count(array_filter($photos, fn (array $photo): bool => $photo['captured_at'] !== null))
                + ($includeCheckIn && ($facts['check_in']['at'] ?? null) !== null ? 1 : 0),
            'expected' => $facts === null ? 0 : count($photos) + ($includeCheckIn ? 1 : 0), 'last_sync_at' => null, 'handoff' => null];
    }

    /**
     * @param  array<string, mixed>  $sources
     * @return array<string, mixed>
     */
    private function checkIn(array $sources): array
    {
        $checkIn = $sources['source_facts']['check_in'] ?? null;
        if ($checkIn === null || $checkIn['at'] === null) {
            return ['state' => 'pending'];
        }

        return ['state' => 'recorded', 'at' => $checkIn['at'], 'review' => $checkIn['review_required'] !== false,
            'evidence' => $this->sourceEvidence($sources, 'check_in', $checkIn['at'], $checkIn['position'])];
    }

    /**
     * @param  array<string, mixed>  $sources
     * @param  array<string, mixed>  $fields
     * @return list<array<string, mixed>>
     */
    private function photoSlots(array $sources, array $fields): array
    {
        $slots = [];
        foreach (['required', 'extra'] as $group) {
            foreach ($sources['source_facts']['photos'][$group] ?? [] as $photo) {
                $slots[] = ['key' => $photo['id'], 'label' => ucwords(str_replace(['-', '_'], ' ', $photo['id'])),
                    'required' => $group === 'required', 'captured_at' => $photo['captured_at'],
                    'position' => $this->position($photo['position']), 'thumbnail_url' => null, 'extra' => $group === 'extra',
                    'title' => $fields['titles'][$photo['id']] ?? $photo['title'] ?? ''];
            }
        }

        return $slots;
    }

    /**
     * @param  array<string, mixed>  $sources
     * @param  array<string, mixed>|null  $position
     * @return array<string, mixed>
     */
    private function sourceEvidence(array $sources, string $kind, ?string $at = null, ?array $position = null): array
    {
        $source = $sources['source_facts']['source'];

        return ['evidence_id' => $source['id'], 'kind' => $kind, 'sha256' => $source['sha256'], 'captured_at' => $at,
            'source' => $source['kind'], 'device_attestation' => 'unavailable', 'position' => $this->position($position),
            'accuracy_m' => $position['accuracy_m'] ?? null];
    }

    /** @param array<string, mixed>|null $position */
    private function position(?array $position): ?string
    {
        return $position === null ? null : $position['latitude'].', '.$position['longitude'];
    }

    /**
     * @param  array<string, mixed>  $report
     * @param  array<string, mixed>  $sources
     * @param  array<string, mixed>  $seal
     * @param  array<string, mixed>|null  $reasonOptions
     * @return array<string, mixed>
     */
    private function seal(array $report, array $sources, array $seal, ?array $reasonOptions, bool $mfaConfirmed): array
    {
        $findings = [];
        foreach ($seal['findings'] as $index => $finding) {
            $values = AuditorJobsResource::figures(array_intersect_key($finding, array_flip(['reported', 'observed', 'difference'])));
            $findings[] = ['code' => $finding['code'], 'no' => (string) ($index + 1),
                'title' => match ($finding['measure']) {
                    'stock_value' => __('Stock value'), 'cash' => __('Cash balance'), 'stock_units' => __('Inventory units'), default => __('Operating status'),
                }, 'body' => isset($finding['reported'])
                    ? __('Reported: :reported. Observed: :observed. Difference: :difference.', $values)
                    : __('Observed status: :observed.', $values), 'evidence_ids' => $finding['evidence_ids']];
        }

        return ['step' => 'seal', 'summary' => [], 'note' => ['required' => $seal['note_required'],
            'why' => $seal['note_required'] ? __('Explain the observed differences or operating status.') : __('Add any factual explanation for the report.'),
            'value' => $report['draft']['note'], 'min' => $seal['note_required'] ? 1 : 0, 'max' => 100],
            'findings' => $findings, 'evidence' => $this->evidence($sources), 'procedure_version' => $seal['payload']['procedure_version'],
            'findings_version' => $seal['findings_version'], 'evidence_version' => $seal['evidence_version'], 'digest' => $seal['digest'],
            'licence' => $sources['licence'], 'mfa' => ['confirmed' => $mfaConfirmed, 'settings' => AuditorJobsResource::link('security.edit')],
            'reason_options' => $reasonOptions];
    }

    /**
     * @param  array{request_changes: list<string>, reject: list<string>}  $options
     * @return array<string, list<array<string, mixed>>>
     */
    private function reasonOptions(array $options): array
    {
        $map = fn (array $codes): array => array_values(array_map(fn (string $code): array => ['code' => $code,
            'label' => $this->reasonLabel($code), 'requires_explanation' => true], $codes));

        return ['request_changes' => $map($options['request_changes']), 'reject' => $map($options['reject'])];
    }

    private function reasonLabel(string $code): string
    {
        return match ($code) {
            'missing_originals' => __('Original documents missing'),
            'reconciliation_difference' => __('Unexplained reconciliation difference'),
            'classification_unresolved' => __('Classification unresolved'),
            'debt_evidence_missing' => __('Debt or draw evidence missing'),
            'capture_unverified' => __('Capture could not be verified'),
            'evidence_unverifiable' => __('Evidence cannot be verified'),
            'procedure_incomplete' => __('Procedure cannot be completed'),
            default => __('Other'),
        };
    }

    /**
     * @param  array<string, mixed>  $sources
     * @return list<array<string, mixed>>
     */
    private function evidence(array $sources): array
    {
        $items = [];
        foreach (['verification' => 'statement'] as $key => $kind) {
            $pin = $sources[$key];
            if ($pin !== null) {
                $items[$pin['id']] ??= ['evidence_id' => $pin['id'], 'kind' => $kind, 'sha256' => $pin['sha256'],
                    'captured_at' => null, 'source' => 'web_upload', 'device_attestation' => 'unavailable', 'position' => null, 'accuracy_m' => null];
            }
        }
        if ($sources['source_facts'] !== null) {
            $item = $this->sourceEvidence($sources, 'photo');
            $items[$item['evidence_id']] = $item;
        }
        foreach ($this->ledgerDocuments($sources['ledger_documents'] ?? []) as $document) {
            $items[$document['id']] = $document['evidence'];
        }
        foreach ($sources['documents'] as $document) {
            $items[$document['id']] = ['evidence_id' => $document['id'], 'kind' => 'statement', 'sha256' => $document['sha256'],
                'captured_at' => null, 'source' => 'web_upload', 'device_attestation' => 'unavailable', 'position' => null, 'accuracy_m' => null];
        }

        return array_values($items);
    }

    /**
     * @param  list<string>  $required
     * @param  list<string>  $seen
     * @return list<array<string, mixed>>
     */
    private function proofs(array $required, array $seen): array
    {
        return array_map(fn (string $id): array => ['key' => $id, 'label' => $id, 'hint' => __('Inspect the original evidence.'),
            'seen' => in_array($id, $seen, true)], $required);
    }

    /**
     * @param  list<array<string, mixed>>  $documents
     * @param  array<string, mixed>|null  $report
     * @return list<array<string, mixed>>
     */
    private function ledgerDocuments(array $documents, ?array $report = null, string $prefix = 'auditor.'): array
    {
        return array_map(fn (array $document): array => ['id' => $document['id'], 'name' => $document['filename'],
            'link' => $report === null ? null : AuditorJobsResource::link($prefix.'reports.ledgers.show', ['report' => $report['id'], 'document' => $document['id']]),
            'detail' => $document['media_type'].' · '.$document['size_bytes'].' bytes',
            'state' => match ($document['extraction']['status']) {
                'pending' => 'scanning', 'text_extracted' => 'parsed', default => 'failed',
            }, 'fields' => [], 'failure' => $document['extraction']['status'] === 'needs_review' ? __('The document needs manual source review.') : null,
            'ingestion' => 'INGESTED_NOT_AUDIT_APPROVED', 'evidence' => ['evidence_id' => $document['id'], 'kind' => 'ledger',
                'sha256' => $document['sha256'], 'captured_at' => null, 'source' => 'web_upload', 'device_attestation' => 'unavailable',
                'position' => null, 'accuracy_m' => null]], $documents);
    }

    /**
     * @param  array<string, mixed>  $report
     * @param  array<string, mixed>  $sources
     * @return array<string, mixed>
     */
    private function statements(array $report, array $sources, string $prefix): array
    {
        $monthly = $sources['monthly'];
        if ($monthly === null) {
            return ['status' => 'unavailable', 'reason' => __('Current verified statements for the reporting period are required.')];
        }
        $documents = array_values(array_filter($sources['documents'], fn (array $document): bool => in_array($document['id'], $monthly['source_ids'], true)));

        return ['status' => 'available', 'inflow' => AuditorJobsResource::money($monthly['inflow']),
            'outflow' => AuditorJobsResource::money($monthly['outflow']), 'net' => AuditorJobsResource::money($monthly['net']),
            'cover' => $monthly['cover'] === null ? null : ['value' => $monthly['cover'], 'band' => null],
            'documents' => array_map(fn (array $document): array => ['name' => $document['filename'],
                'link' => AuditorJobsResource::link($prefix.'reports.statements.show', ['report' => $report['id'], 'document' => $document['id']])], $documents)];
    }

    private function hint(?string $code): ?string
    {
        return match ($code) {
            null => null,
            'AUDIT_CAPTURE_REQUIRED' => (string) __('A verified capture package is required before this step can continue.'),
            'AUDIT_DECLARATION_REQUIRED' => (string) __('The Business declaration needed for reconciliation is unavailable.'),
            'AUDIT_PROOFS_REQUIRED' => (string) __('The required financial or inventory proofs are unavailable.'),
            'AUDIT_VERIFIED_STATEMENTS_REQUIRED' => (string) __('Current verified statements are required before continuing.'),
            'AUDIT_PROCEDURE_SOURCE_CHANGED' => (string) __('A source changed. Review it again before continuing.'),
            'AUDIT_NOTE_REQUIRED' => (string) __('Save a factual explanation of the observed differences before continuing.'),
            default => (string) __('Complete the preceding procedure steps before continuing.'),
        };
    }
}
