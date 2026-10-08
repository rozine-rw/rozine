<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

/** The trail's search, its preset or date range, and how many entries to show. */
class ListActivityTrailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*') || $this->user()->tokenCan('staff:events:read'));
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['q' => ['sometimes', 'nullable', 'string', 'max:120'], 'preset' => ['sometimes', 'nullable', 'string', 'in:today,7d,30d'],
            'from' => ['sometimes', 'nullable', 'date_format:Y-m-d'], 'to' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:500']];
    }
}
