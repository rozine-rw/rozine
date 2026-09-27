<?php

declare(strict_types=1);

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublishApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*')
            || ($this->user()->tokenCan('business:read') && $this->user()->tokenCan('business:command')));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['request_id' => ['required', 'uuid'], 'identity_context_revision' => ['required', 'integer', 'min:0'],
            'application_id' => ['required', 'ulid', Rule::in([(string) $this->route('application')])],
            'expected_application_revision' => ['required', 'integer', 'min:0'], 'fee_disclosure_version' => ['required', 'string', 'max:100']];
    }
}
