<?php

declare(strict_types=1);

namespace App\Http\Requests\Investor;

use App\Application\Primary\GetInvestorDeals;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShowDealsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*') || $this->user()->tokenCan('investor:read'));
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return ['identity_context_revision' => ['sometimes', 'integer', 'min:0'], 'sort' => ['sometimes', 'string', Rule::in(GetInvestorDeals::SORTS)],
            'industry' => ['sometimes', 'string', 'max:120'], 'deal' => ['sometimes', 'string', 'ulid']];
    }
}
