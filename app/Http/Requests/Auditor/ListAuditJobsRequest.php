<?php

declare(strict_types=1);

namespace App\Http\Requests\Auditor;

use Illuminate\Foundation\Http\FormRequest;

class ListAuditJobsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*') || $this->user()->tokenCan('auditor:read'));
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['before' => ['nullable', 'string', 'regex:/^[0-9a-hjkmnp-tv-z]{26}$/D'], 'limit' => ['sometimes', 'integer', 'min:1', 'max:50']];
    }
}
