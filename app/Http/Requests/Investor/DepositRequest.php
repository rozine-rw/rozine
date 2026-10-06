<?php

declare(strict_types=1);

namespace App\Http\Requests\Investor;

use Illuminate\Foundation\Http\FormRequest;

/** `wallet.deposit` input only. Party, wallet, fee, policy and provider are resolved on the server. */
class DepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*')
            || ($this->user()->tokenCan('investor:read') && $this->user()->tokenCan('investor:command')));
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['request_id' => ['required', 'uuid'], 'identity_context_revision' => ['required', 'integer', 'min:0'],
            'amount' => ['required', 'array:currency,amount'], 'amount.currency' => ['required', 'string', 'in:RWF'],
            'amount.amount' => ['required', 'string', 'regex:/^[0-9]{1,15}$/D'], 'method_id' => ['required', 'ulid']];
    }
}
