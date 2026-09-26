<?php

declare(strict_types=1);

namespace App\Http\Requests\Auditor;

class ResolveAuditDisputeRequest extends ResolveAuditAssignmentRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [...parent::rules(), 'report_revision' => ['required', 'integer', 'min:1'],
            'digest' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/D'], 'reason' => ['present', 'string', 'max:10000'],
            'decision' => ['sometimes', 'string', 'max:100']];
    }
}
