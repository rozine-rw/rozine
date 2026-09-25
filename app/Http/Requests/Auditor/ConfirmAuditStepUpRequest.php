<?php

declare(strict_types=1);

namespace App\Http\Requests\Auditor;

class ConfirmAuditStepUpRequest extends AuditorCommandRequest
{
    /** @return array<string, list<string>> */
    protected function commandRules(): array
    {
        return ['audit_id' => ['required', 'ulid', 'in:'.$this->route('report')], 'digest' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/D'], 'code' => ['required', 'string', 'regex:/^[0-9]{6}$/D']];
    }
}
