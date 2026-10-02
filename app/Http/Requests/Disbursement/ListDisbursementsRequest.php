<?php

declare(strict_types=1);

namespace App\Http\Requests\Disbursement;

use Illuminate\Foundation\Http\FormRequest;

class ListDisbursementsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*') || $this->user()->tokenCan('staff:disbursements:read'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['before' => ['sometimes', 'nullable', 'ulid'], 'limit' => ['sometimes', 'integer', 'min:1', 'max:50']];
    }
}
