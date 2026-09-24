<?php

declare(strict_types=1);

namespace App\Http\Requests\Identity;

use Illuminate\Foundation\Http\FormRequest;

class SelectActiveRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*') || $this->user()->tokenCan('identity:select-role'));
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'role' => ['required', 'in:investor,business,auditor'],
            'expected_revision' => ['required', 'integer', 'min:0'],
            'request_id' => ['required', 'uuid'],
        ];
    }
}
