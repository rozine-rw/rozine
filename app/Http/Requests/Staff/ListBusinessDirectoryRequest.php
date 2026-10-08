<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

class ListBusinessDirectoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*') || $this->user()->tokenCan('staff:businesses:read'));
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['chip' => ['sometimes', 'string', 'in:all,healthy,watch,distressed,frozen'], 'sort' => ['sometimes', 'string', 'in:raised,name'],
            'sector' => ['sometimes', 'nullable', 'string', 'max:120'], 'q' => ['sometimes', 'nullable', 'string', 'max:120'],
            'business' => ['sometimes', 'string', 'ulid']];
    }
}
