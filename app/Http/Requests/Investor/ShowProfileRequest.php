<?php

declare(strict_types=1);

namespace App\Http\Requests\Investor;

use App\Application\Primary\GetInvestorProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShowProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return ['identity_context_revision' => ['sometimes', 'integer', 'min:0'], 'section' => ['sometimes', 'string', Rule::in(GetInvestorProfile::SECTIONS)]];
    }
}
