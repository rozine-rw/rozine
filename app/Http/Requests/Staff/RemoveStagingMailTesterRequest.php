<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

/** Removing a named staging mail tester, with the reason the journal keeps. */
class RemoveStagingMailTesterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['request_id' => ['required', 'uuid'], 'reason' => ['required', 'string', 'max:1000']];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['reason.required' => 'Give the reason for this change.', 'reason.max' => 'Keep the reason under 1,000 characters.'];
    }
}
