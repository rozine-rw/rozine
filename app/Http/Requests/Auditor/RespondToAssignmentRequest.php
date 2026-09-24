<?php

declare(strict_types=1);

namespace App\Http\Requests\Auditor;

/** Domain option/reason validation belongs inside the journal, including empty explanations. */
class RespondToAssignmentRequest extends AuditorCommandRequest
{
    /** @return array<string, list<string>> */
    protected function commandRules(): array
    {
        return ['kind' => ['nullable', 'string', 'max:100'], 'reason_code' => ['nullable', 'string', 'max:100'], 'reason' => ['nullable', 'string', 'max:10000']];
    }
}
