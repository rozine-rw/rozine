<?php

namespace App\Http\Requests\Site;

use App\Concerns\PulseSignupValidationRules;
use App\Domain\Pulse\PulseUnderwriting;
use App\Enums\PulseContactMethod;
use App\Models\PulseSignup;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSiteInvestorRequest extends FormRequest
{
    use PulseSignupValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'contact_method' => ['required', Rule::enum(PulseContactMethod::class)],
            'contact' => [
                'required',
                'string',
                'max:255',
                $this->input('contact_method') === PulseContactMethod::Email->value
                    ? 'email:rfc'
                    : 'regex:/^\+?[0-9]{9,}$/',
                Rule::unique(PulseSignup::class, 'contact'),
            ],
            'country' => ['required', 'string', 'min:2', 'max:255'],
            'pledge_amount' => [
                'required',
                'integer',
                'min:'.PulseUnderwriting::PLEDGE_MINIMUM,
                'max:'.PulseUnderwriting::PLEDGE_MAXIMUM,
            ],
        ];
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
