<?php

namespace App\Http\Requests\Pulse;

use App\Concerns\PulseSignupValidationRules;
use App\Support\PulseUnderwriting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreInvestorPledgeRequest extends FormRequest
{
    use PulseSignupValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge($this->signupRules(), [
            'pledge_amount' => ['required', 'integer', 'min:5000', 'max:'.PulseUnderwriting::MAX_LOAN],
        ]);
    }

    /**
     * Get custom messages for the validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->signupMessages();
    }
}
