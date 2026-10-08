<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

class ShowLedgerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*') || $this->user()->tokenCan('staff:ledger:read'));
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['search' => ['sometimes', 'nullable', 'string', 'max:120'], 'before' => ['sometimes', 'string', 'ulid'],
            'entry' => ['sometimes', 'string', 'ulid']];
    }
}
