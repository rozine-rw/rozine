<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

/** The complaints search and the complaint open in the drawer, on the web or with a `staff:complaints:read` API token. */
class ListComplaintsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*') || $this->user()->tokenCan('staff:complaints:read'));
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return ['q' => ['sometimes', 'nullable', 'string', 'max:120'], 'complaint' => ['sometimes', 'nullable', 'ulid']];
    }
}
