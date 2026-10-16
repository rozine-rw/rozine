<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

/** A named staging mail tester, with the reason the journal keeps. */
class AddStagingMailTesterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['request_id' => ['required', 'uuid'], 'email' => ['required', 'string', 'max:254', 'email:rfc'],
            'reason' => ['required', 'string', 'max:1000']];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['email.required' => 'Enter the tester\'s email address.', 'email.email' => 'Enter one email address.',
            'email.max' => 'Enter one email address.', 'reason.required' => 'Give the reason for this change.',
            'reason.max' => 'Keep the reason under 1,000 characters.'];
    }
}
