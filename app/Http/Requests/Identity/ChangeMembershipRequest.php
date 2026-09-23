<?php

declare(strict_types=1);

namespace App\Http\Requests\Identity;

use Illuminate\Foundation\Http\FormRequest;

class ChangeMembershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*') || $this->user()->tokenCan('identity:manage'));
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'party_id' => ['required', 'ulid'],
            'role' => ['required', 'in:investor,business,auditor'],
            'status' => ['required', 'in:pending,active,suspended,revoked'],
            'expected_revision' => ['required', 'integer', 'min:0'],
            'evidence_reference' => ['required', 'string', 'max:255'],
            'reason' => ['required', 'string', 'max:1000'],
            'request_id' => ['required', 'uuid'],
        ];
    }
}
