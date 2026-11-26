<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

/** A proposed new value for one changeable policy, with the reason the journal keeps. */
class ProposePolicyChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['request_id' => ['required', 'uuid'], 'key' => ['required', 'string', 'max:60'], 'value' => ['required', 'string', 'max:20'],
            'reason' => ['required', 'string', 'max:1000']];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['value.required' => 'Enter the new value.', 'reason.required' => 'Give the reason for this change.',
            'reason.max' => 'Keep the reason under 1,000 characters.'];
    }
}
