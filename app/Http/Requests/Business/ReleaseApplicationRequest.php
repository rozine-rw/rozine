<?php

declare(strict_types=1);

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReleaseApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*')
            || ($this->user()->tokenCan('staff:applications:read') && $this->user()->tokenCan('staff:applications:review')));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['request_id' => ['required', 'uuid'], 'application_id' => ['required', 'ulid', Rule::in([(string) $this->route('application')])],
            'expected_revision' => ['required', 'integer', 'min:0'], 'reason' => ['present', 'nullable', 'string', 'max:10000']];
    }
}
