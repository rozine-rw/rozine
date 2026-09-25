<?php

declare(strict_types=1);

namespace App\Http\Requests\Business;

class ShowAuditCosignOperationRequest extends ShowAuditReportRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['identity_context_revision' => ['required', 'integer', 'min:0'], 'command' => ['required', 'in:report.cosign']];
    }
}
