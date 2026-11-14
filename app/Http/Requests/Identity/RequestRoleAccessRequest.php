<?php

declare(strict_types=1);

namespace App\Http\Requests\Identity;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * `identity.access.request` input only (#616). Whether the person may ask for the role is decided
 * on the server. A Business names the company and may give its RDB code; an Auditor gives their
 * ICPAR licence number and may name their firm.
 */
class RequestRoleAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        $auditor = $this->input('role') === 'auditor';

        return ['request_id' => ['required', 'uuid'], 'role' => ['required', Rule::in(['business', 'auditor'])],
            'organization' => [$auditor ? 'nullable' : 'required', 'string', 'max:160'],
            'reference' => [$auditor ? 'required' : 'nullable', 'string', 'max:60'],
            'phone' => ['nullable', 'string', 'max:32', 'regex:/^\+?[0-9 ()-]{7,32}$/'],
            'note' => ['nullable', 'string', 'max:1000']];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['organization.required' => 'Enter the business name.', 'reference.required' => 'Enter your ICPAR licence number.',
            'phone.regex' => 'Enter a phone number with digits only, for example +250 788 000 000.'];
    }
}
