<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

/** Approve, ask for more or reject a role-switch application; asking for more names the items to change, rejecting needs a note. */
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
            'decision' => ['required', 'string', 'in:approve,more,reject'], 'note' => ['nullable', 'string', 'max:1000'],
            'items' => ['nullable', 'array', 'list', 'max:40'], 'items.*.key' => ['required', 'string', 'max:80'], 'items.*.note' => ['nullable', 'string', 'max:300']];
    }
}
