<?php

declare(strict_types=1);

namespace App\Http\Requests\Investor;

use Illuminate\Foundation\Http\FormRequest;

class ShowCommitmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*') || $this->user()->tokenCan('investor:read'));
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['identity_context_revision' => ['sometimes', 'integer', 'min:0']];
    }
}
