<?php

declare(strict_types=1);

namespace App\Http\Requests\Investor;

use Illuminate\Foundation\Http\FormRequest;

/** `wallet.link_method` input only. Whether the account is valid and the Investor's own is decided on the server. */
class LinkPayoutAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['request_id' => ['required', 'uuid'], 'type' => ['required', 'string', 'in:mobile,bank'],
            'network' => ['nullable', 'string', 'max:20'], 'bank' => ['nullable', 'string', 'max:20'], 'number' => ['required', 'string', 'max:30']];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['number.required' => 'Enter the account number.'];
    }
}
