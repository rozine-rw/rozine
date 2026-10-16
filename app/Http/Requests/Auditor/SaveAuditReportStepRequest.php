<?php

declare(strict_types=1);

namespace App\Http\Requests\Auditor;

class SaveAuditReportStepRequest extends AuditorCommandRequest
{
    /** @return array<string, list<string>> */
    protected function commandRules(): array
    {
        $fields = $this->hasFile('document') ? ['prohibited'] : ['sometimes'];

        return ['audit_id' => ['required', 'ulid', 'in:'.$this->route('report')],
            'step' => ['required', 'string', 'max:30', ...($this->hasFile('document') ? ['in:ledger'] : [])],
            'observed_stock' => $fields, 'reconciled' => $fields, 'cash' => $fields, 'stock_units' => $fields,
            'operational_status' => $fields, 'financial_proofs' => $fields, 'inventory_proofs' => $fields, 'titles' => $fields,
            'note' => $fields, 'document' => ['sometimes', 'file', 'max:10240'],
            'replaces' => ['sometimes', 'nullable', 'ulid', ...($this->hasFile('document') ? [] : ['prohibited'])]];
    }
}
