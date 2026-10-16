<?php

declare(strict_types=1);

namespace App\Http\Requests\Auditor;

class StartAuditReportRequest extends AuditorCommandRequest
{
    /** @return array<string, list<string>> */
    protected function commandRules(): array
    {
        return ['assignment_id' => ['sometimes', 'required', 'ulid', 'in:'.$this->route('assignment')],
            'application_id' => ['required', 'ulid'], 'application_revision' => ['required', 'integer', 'min:1']];
    }
}
