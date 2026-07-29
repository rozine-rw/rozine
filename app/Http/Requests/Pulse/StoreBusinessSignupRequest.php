<?php

namespace App\Http\Requests\Pulse;

use App\Concerns\PulseSignupValidationRules;
use App\Enums\PulseSector;
use App\Support\PulseUnderwriting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class StoreBusinessSignupRequest extends FormRequest
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
            'annual_revenue' => ['required', 'integer', 'min:'.PulseUnderwriting::MINIMUM_REVENUE],
            'annual_costs' => ['required', 'integer', 'min:1', 'lt:annual_revenue'],
            'sector' => ['required', Rule::enum(PulseSector::class)],
            'registered_year' => [
                'required',
                'integer',
                'min:'.PulseUnderwriting::EARLIEST_REGISTRATION_YEAR,
                'max:'.Carbon::now()->year,
            ],
            'term_months' => ['required', 'integer', Rule::in(PulseUnderwriting::TERMS)],
            'listed' => ['sometimes', 'boolean'],
        ]);
    }

    /**
     * Get custom messages for the validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge($this->signupMessages(), [
            'annual_revenue.min' => 'Tell us what your business made over the last 12 months.',
            'annual_costs.lt' => 'Your costs have to be lower than your revenue to pre-qualify.',
        ]);
    }

    /**
     * Get the sector the business trades in.
     */
    public function sector(): PulseSector
    {
        return PulseSector::from($this->string('sector')->toString());
    }
}
