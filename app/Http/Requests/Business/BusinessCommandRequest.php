<?php

declare(strict_types=1);

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;

/** Structural input is validated here; domain refusals are recorded under the supplied UUID. */
abstract class BusinessCommandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*')
            || ($this->user()->tokenCan('business:read') && $this->user()->tokenCan('business:command')));
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [...$this->commandRules(), 'identity_context_revision' => ['required', 'integer', 'min:0'],
            'expected_revision' => ['required', 'integer', 'min:0'], 'request_id' => ['required', 'uuid']];
    }

    /** @return array<string, list<string>> */
    abstract protected function commandRules(): array;
}
