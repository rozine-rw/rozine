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
            'districts' => ['sometimes', 'array', 'max:30'],
            'districts.*' => ['string', 'max:40'],
            'max_jobs_per_week' => ['sometimes', 'integer', 'between:1,20'],
        ];
    }
}
