<?php

declare(strict_types=1);

namespace App\Http\Requests\Business;

class SaveApplicationRequest extends BusinessCommandRequest
{
    /** @return array<string, list<string>> */
    protected function commandRules(): array
    {
        return [
            'title' => ['present', 'nullable', 'string', 'max:1000'], 'target' => ['present', 'nullable', 'string', 'max:80'],
            'term_months' => ['present', 'nullable', 'integer'], 'use_of_funds' => ['present', 'array', 'list', 'max:30'],
            'use_of_funds.*' => ['string', 'max:100'], 'story' => ['present', 'nullable', 'string', 'max:20000'],
            'step' => ['sometimes', 'nullable', 'string', 'max:32'],
        ];
    }
}
