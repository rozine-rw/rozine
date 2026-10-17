<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

/** Messaging History's search and its older-page cursor, on the web or with a `staff:messaging:read` API token. */
class ListMessageHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*') || $this->user()->tokenCan('staff:messaging:read'));
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['q' => ['sometimes', 'nullable', 'string', 'max:120'], 'before' => ['sometimes', 'nullable', 'ulid']];
    }
}
