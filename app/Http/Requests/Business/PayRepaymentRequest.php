<?php

declare(strict_types=1);

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;

/** `repayment.pay` input only. The amount, instalments, wallet and servicing state are resolved on the server. */
class PayRepaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*')
            || ($this->user()->tokenCan('business:read') && $this->user()->tokenCan('business:command')));
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['request_id' => ['required', 'uuid'], 'identity_context_revision' => ['required', 'integer', 'min:0'], 'note_id' => ['required', 'ulid'],
            'option' => ['required', 'string', 'in:due_now,next_instalment'], 'expected_servicing_revision' => ['required', 'integer', 'min:1'],
            'quoted_total' => ['required', 'array:currency,amount'], 'quoted_total.currency' => ['required', 'string', 'in:RWF'],
            'quoted_total.amount' => ['required', 'string', 'regex:/^[0-9]{1,15}$/D']];
    }
}
