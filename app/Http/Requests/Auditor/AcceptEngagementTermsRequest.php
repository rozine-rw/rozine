<?php

declare(strict_types=1);

namespace App\Http\Requests\Auditor;

class AcceptEngagementTermsRequest extends AuditorCommandRequest
{
    /** @return array<string, list<string>> */
    protected function commandRules(): array
    {
        return ['release_id' => ['required', 'ulid'], 'sha256' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/D'],
            'accepted' => ['required', 'boolean:strict']];
    }
}
