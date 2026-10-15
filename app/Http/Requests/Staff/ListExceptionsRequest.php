<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** The exceptions' search, kind filter and how many to show, on the web or with a `staff:exceptions:read` API token. */
class ListExceptionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*') || $this->user()->tokenCan('staff:exceptions:read'));
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return ['q' => ['sometimes', 'nullable', 'string', 'max:120'], 'kind' => ['sometimes', 'nullable', Rule::in(['arrears', 'halt', 'variance'])],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:500']];
    }
}
