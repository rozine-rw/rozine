<?php

declare(strict_types=1);

namespace App\Http\Requests\Business;

class EvaluateApplicationRequest extends BusinessCommandRequest
{
    /** @return array<string, list<string>> */
    protected function commandRules(): array
    {
        return [
            'target' => ['required', 'string', 'max:80'], 'term_months' => ['required', 'integer'],
            'evidence_version' => ['required', 'string', 'max:80'], 'accepted_principal' => ['nullable', 'string', 'max:80'],
        ];
    }
}
