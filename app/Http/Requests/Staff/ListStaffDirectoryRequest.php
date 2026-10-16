<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

class ListStaffDirectoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*') || $this->user()->tokenCan('staff:staff:read'));
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['chip' => ['sometimes', 'string', 'in:all,active,frozen'], 'q' => ['sometimes', 'nullable', 'string', 'max:120'],
            'operator' => ['sometimes', 'integer', 'min:1']];
    }
}
