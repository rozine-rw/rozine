<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

/** The live raises' search and how many to show, on the web or with a `staff:primary:read` API token. */
class ListPrimaryMarketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*') || $this->user()->tokenCan('staff:primary:read'));
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return ['q' => ['sometimes', 'nullable', 'string', 'max:120'], 'limit' => ['sometimes', 'integer', 'min:1', 'max:500']];
    }
}
