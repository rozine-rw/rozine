<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

/** Approve, ask for more or reject a role-switch application; the note is required except to approve. */
class DecideRoleApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['request_id' => ['required', 'uuid'], 'expected_revision' => ['required', 'integer', 'min:1'],
            'decision' => ['required', 'string', 'in:approve,more,reject'], 'note' => ['nullable', 'string', 'max:1000']];
    }
}
