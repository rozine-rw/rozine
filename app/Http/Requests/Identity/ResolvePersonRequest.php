<?php

declare(strict_types=1);

namespace App\Http\Requests\Identity;

use Illuminate\Foundation\Http\FormRequest;

class ResolvePersonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*') || $this->user()->tokenCan('identity:manage'));
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'min:1'],
            'identity_reference' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9._-]+:[A-Za-z0-9._-]{1,128}$/D'],
            'evidence_reference' => ['required', 'string', 'max:255'],
            'reason' => ['required', 'string', 'max:1000'],
            'request_id' => ['required', 'uuid'],
        ];
    }
}
