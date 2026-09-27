<?php

declare(strict_types=1);

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;

class ListStaffApplicationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*') || $this->user()->tokenCan('staff:applications:read'));
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['tab' => ['sometimes', 'string', 'in:pending,approved'], 'search' => ['sometimes', 'nullable', 'string', 'max:120'],
            'before' => ['sometimes', 'string', 'ulid'], 'limit' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'application' => ['sometimes', 'string', 'ulid']];
    }
}
