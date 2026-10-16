<?php

declare(strict_types=1);

namespace App\Http\Requests\Investor;

use App\Application\Primary\GetInvestorPortfolio;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShowPortfolioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return ['identity_context_revision' => ['sometimes', 'integer', 'min:0'], 'tab' => ['sometimes', 'string', Rule::in(GetInvestorPortfolio::TABS)]];
    }
}
