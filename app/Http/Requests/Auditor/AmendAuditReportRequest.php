<?php

declare(strict_types=1);

namespace App\Http\Requests\Auditor;

class AmendAuditReportRequest extends AuditorCommandRequest
{
    /** @return array<string, list<string>> */
    protected function commandRules(): array
    {
        return ['audit_id' => ['required', 'ulid', 'in:'.$this->route('report')]];
    }
}
