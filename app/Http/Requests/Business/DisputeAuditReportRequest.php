<?php

declare(strict_types=1);

namespace App\Http\Requests\Business;

class DisputeAuditReportRequest extends BusinessCommandRequest
{
    /** @return array<string, list<string>> */
    protected function commandRules(): array
    {
        return ['report_revision' => ['required', 'integer', 'min:1'], 'mandate_version' => ['required', 'integer', 'min:1'],
            'digest' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/D'], 'supporting_text' => ['nullable', 'string', 'max:10000'],
            'proof_files' => ['sometimes', 'array', 'max:5'], 'proof_files.*' => ['required', 'file', 'max:10240', 'mimetypes:application/pdf,image/jpeg,image/png']];
    }
}
