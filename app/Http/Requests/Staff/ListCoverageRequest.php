<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

/** The coverage search, on the web or with a `staff:coverage:read` API token. */
class ListCoverageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*') || $this->user()->tokenCan('staff:coverage:read'));
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return ['q' => ['sometimes', 'nullable', 'string', 'max:120']];
    }
}
