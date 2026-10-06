<?php

declare(strict_types=1);

namespace App\Http\Requests\Business;

class CosignAuditReportRequest extends BusinessCommandRequest
{
    /** @return array<string, list<string>> */
    protected function commandRules(): array
    {
        return ['report_revision' => ['required', 'integer', 'min:1'], 'mandate_version' => ['required', 'integer', 'min:1'],
            'digest' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/D'], 'accepted' => ['required', 'boolean:strict'],
            'note' => ['present', 'nullable', 'string', 'max:1000']];
    }
}
