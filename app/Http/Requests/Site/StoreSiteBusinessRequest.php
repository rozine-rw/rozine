<?php

declare(strict_types=1);

namespace App\Http\Requests\Site;

use App\Concerns\PulseSignupValidationRules;
use App\Domain\Pulse\PulseUnderwriting;
use App\Enums\PulseContactMethod;
use App\Models\PulseSignup;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSiteBusinessRequest extends FormRequest
{
    use PulseSignupValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var array<string, list<string>> $districts */
        $districts = config('rwanda.districts');

        $province = $this->input('province');

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
            'province' => ['required', 'string', Rule::in(array_keys($districts))],
            'district' => [
                'required',
                'string',
                Rule::in(is_string($province) ? ($districts[$province] ?? []) : []),
            ],
            'annual_revenue' => ['required', 'integer', 'min:1'],
            'annual_costs' => ['required', 'integer', 'min:1', 'lt:annual_revenue'],
            'term_months' => ['required', 'integer', Rule::in(PulseUnderwriting::TERMS)],
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
