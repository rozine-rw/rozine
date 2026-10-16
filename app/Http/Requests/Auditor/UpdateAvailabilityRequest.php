<?php

declare(strict_types=1);

namespace App\Http\Requests\Auditor;

class UpdateAvailabilityRequest extends AuditorCommandRequest
{
    /** @return array<string, list<string>> */
    protected function commandRules(): array
    {
        return [
            'accepting' => ['required', 'boolean'],
        ];
    }
}
