<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

/** Adding an operator: their account's email, the one role they get, the request key and the reason. */
class GrantStaffAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['request_id' => ['required', 'uuid'], 'email' => ['required', 'email', 'max:255'],
            'role' => ['required', 'in:analyst,approver,treasury,compliance,superadmin'], 'reason' => ['required', 'string', 'max:1000']];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['email.required' => "Enter the operator's email.", 'email.email' => 'Enter a valid email address.',
            'role.required' => 'Choose a role.', 'role.in' => 'Choose a role.',
            'reason.required' => 'Give the reason for this change.', 'reason.max' => 'Keep the reason under 1,000 characters.'];
    }
}
