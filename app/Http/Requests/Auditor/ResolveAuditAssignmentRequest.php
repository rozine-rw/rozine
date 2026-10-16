<?php

declare(strict_types=1);

namespace App\Http\Requests\Auditor;

use Illuminate\Foundation\Http\FormRequest;

class ResolveAuditAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*') || ($this->user()->tokenCan('staff:audit:manage') && $this->user()->tokenCan('staff:audit:read')));
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['request_id' => ['required', 'uuid'], 'expected_revision' => ['required', 'integer', 'min:0'],
            'reason' => ['nullable', 'string', 'max:10000']];
    }
}
