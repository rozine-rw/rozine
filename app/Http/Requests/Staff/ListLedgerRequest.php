<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

/** The ledger's search, how many entries to show and the entry opened, on the web or with a `staff:ledger:read` API token. */
class ListLedgerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*') || $this->user()->tokenCan('staff:ledger:read'));
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['q' => ['sometimes', 'nullable', 'string', 'max:120'], 'limit' => ['sometimes', 'integer', 'min:1', 'max:500'],
            'entry' => ['sometimes', 'nullable', 'ulid']];
    }
}
