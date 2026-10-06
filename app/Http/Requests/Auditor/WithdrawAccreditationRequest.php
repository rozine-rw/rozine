<?php

declare(strict_types=1);

namespace App\Http\Requests\Auditor;

class WithdrawAccreditationRequest extends AuditorCommandRequest
{
    /** @return array<string, list<string>> */
    protected function commandRules(): array
    {
        return [
            'submission_id' => ['required', 'string', 'max:64'],
        ];
    }
}
