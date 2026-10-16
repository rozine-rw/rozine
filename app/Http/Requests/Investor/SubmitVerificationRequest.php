<?php

declare(strict_types=1);

namespace App\Http\Requests\Investor;

use Illuminate\Foundation\Http\FormRequest;

class SubmitVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*')
            || ($this->user()->tokenCan('investor:read') && $this->user()->tokenCan('investor:command')));
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['request_id' => ['required', 'uuid'], 'identity_context_revision' => ['required', 'integer', 'min:0'],
            'expected_revision' => ['required', 'integer', 'min:0']];
    }
}
