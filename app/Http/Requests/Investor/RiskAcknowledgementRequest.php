<?php

declare(strict_types=1);

namespace App\Http\Requests\Investor;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Reading the KYC-R05 risk statements, and acknowledging all five at once. The acknowledgement
 * repeats the version and digest the Investor was shown; the statements themselves are the server's.
 */
class RiskAcknowledgementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $command = $this->isMethod('POST');

        return $this->user() !== null && (! $this->routeIs('api.*')
            || ($this->user()->tokenCan('investor:read') && (! $command || $this->user()->tokenCan('investor:command'))));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        if (! $this->isMethod('POST')) {
            return ['identity_context_revision' => ['sometimes', 'integer', 'min:0']];
        }

        return ['request_id' => ['required', 'uuid'], 'identity_context_revision' => ['required', 'integer', 'min:0'],
            'version' => ['required', 'string', 'max:80'], 'statements_sha256' => ['required', 'string', 'regex:/^[0-9a-f]{64}$/D'],
            'acknowledged' => ['required', 'accepted']];
    }
}
