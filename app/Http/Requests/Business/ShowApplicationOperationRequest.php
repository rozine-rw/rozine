<?php

declare(strict_types=1);

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;

class ShowApplicationOperationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*') || $this->user()->tokenCan('business:read'));
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['identity_context_revision' => ['required', 'integer', 'min:0'], 'command' => ['required', 'string', 'max:100']];
    }
}
