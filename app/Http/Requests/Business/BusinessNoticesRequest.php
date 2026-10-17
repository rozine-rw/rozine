<?php

declare(strict_types=1);

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;

/** Reading a Business's notices, and marking one or all of them read. */
class BusinessNoticesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $command = $this->isMethod('POST');

        return $this->user() !== null && (! $this->routeIs('api.*')
            || ($this->user()->tokenCan('business:read') && (! $command || $this->user()->tokenCan('business:command'))));
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['identity_context_revision' => ['sometimes', 'integer', 'min:0']];
    }
}
