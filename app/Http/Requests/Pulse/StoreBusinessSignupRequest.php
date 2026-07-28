<?php

namespace App\Http\Requests\Pulse;

use App\Concerns\PulseSignupValidationRules;
use App\Support\PulseUnderwriting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
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
            'statement_path' => ['required', 'string', Rule::in(array_keys($this->parsedStatements()))],
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
            'statement_path.in' => 'Upload a statement before claiming your pass.',
        ]);
    }

    /**
     * Get the average annual inflow read off the submitted statement.
     */
    public function annualInflow(): int
    {
        return $this->parsedStatements()[$this->string('statement_path')->toString()];
    }

    /**
     * Get the inflow figures keyed by the statements this session has uploaded.
     *
     * @return array<string, int>
     */
    private function parsedStatements(): array
    {
        /** @var array<string, int> $statements */
        $statements = $this->session()->get('pulse.statements', []);

        return $statements;
    }
}
