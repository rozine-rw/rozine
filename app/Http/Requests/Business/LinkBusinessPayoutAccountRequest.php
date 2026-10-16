<?php

declare(strict_types=1);

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;

/** `business.wallet.link_method` input only. Whether the account is valid and the Business's own, and the actor permitted, is decided on the server. */
class LinkBusinessPayoutAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['request_id' => ['required', 'uuid'], 'identity_context_revision' => ['sometimes', 'integer', 'min:0'], 'type' => ['required', 'string', 'in:mobile,bank'],
            'network' => ['nullable', 'string', 'max:20'], 'bank' => ['nullable', 'string', 'max:20'], 'number' => ['required', 'string', 'max:30']];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['number.required' => 'Enter the account number.'];
    }
}
