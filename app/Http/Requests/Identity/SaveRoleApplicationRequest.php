<?php

declare(strict_types=1);

namespace App\Http\Requests\Identity;

use Illuminate\Foundation\Http\FormRequest;

/** One step of a role-switch application; the answers themselves are checked by the application's own rules. */
class SaveRoleApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['request_id' => ['required', 'uuid'], 'identity_context_revision' => ['required', 'integer', 'min:0'],
            'expected_revision' => ['required', 'integer', 'min:0'], 'role' => ['required', 'string', 'in:business,auditor'],
            'step' => ['required', 'string', 'in:company,people,documents,licence,coverage'],
            'answers' => ['nullable', 'array', 'max:20']];
    }
}
