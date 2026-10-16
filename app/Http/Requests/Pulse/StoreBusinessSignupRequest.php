<?php

declare(strict_types=1);

namespace App\Http\Requests\Pulse;

use App\Concerns\PulseBusinessValidationRules;
use App\Concerns\PulseSignupValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBusinessSignupRequest extends FormRequest
{
    use PulseBusinessValidationRules;
    use PulseSignupValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge($this->signupRules(), $this->businessSizingRules(), [
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
        return array_merge($this->signupMessages(), $this->businessSizingMessages());
    }
}
