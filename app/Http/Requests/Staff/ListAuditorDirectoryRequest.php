<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

class ListAuditorDirectoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*') || $this->user()->tokenCan('staff:auditors:read'));
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['chip' => ['sometimes', 'string', 'in:all,active,pending,licence_expired'], 'q' => ['sometimes', 'nullable', 'string', 'max:120'],
            'auditor' => ['sometimes', 'string', 'ulid']];
    }
}
