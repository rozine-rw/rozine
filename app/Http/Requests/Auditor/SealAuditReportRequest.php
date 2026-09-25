<?php

declare(strict_types=1);

namespace App\Http\Requests\Auditor;

class SealAuditReportRequest extends AuditorCommandRequest
{
    /** @return array<string, list<string>> */
    protected function commandRules(): array
    {
        return ['audit_id' => ['required', 'ulid', 'in:'.$this->route('report')], 'digest' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/D'], 'procedure_version' => ['required', 'string', 'max:100'], 'findings_version' => ['required', 'string', 'max:100'], 'evidence_version' => ['required', 'string', 'max:100'], 'evidence_ids' => ['required', 'array'], 'evidence_ids.*' => ['required', 'string', 'distinct', 'max:100'], 'note' => ['present', 'nullable', 'string', 'max:100'], 'step_up' => ['required', 'array:proof'], 'step_up.proof' => ['required', 'string', 'size:64']];
    }
}
