<?php

namespace App\Concerns;

use App\Enums\PulseContactMethod;
use App\Models\PulseSignup;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait PulseSignupValidationRules
{
    /**
     * Get the validation rules shared by every Pulse waitlist signup.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function signupRules(): array
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
                $this->isEmailSignup()
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
        ];
    }

    /**
     * Get the messages shared by every Pulse waitlist signup.
     *
     * @return array<string, string>
     */
    protected function signupMessages(): array
    {
        return [
            'contact.unique' => $this->isEmailSignup()
                ? 'This email is already in the waitlist.'
                : 'This phone number is already in the waitlist.',
        ];
    }

    /**
     * Reduce the contact detail to the one form the waitlist stores it in, so
     * the same person cannot join twice by punctuating it differently.
     */
    protected function prepareForValidation(): void
    {
        $contact = trim((string) $this->input('contact'));

        if ($contact === '') {
            return;
        }

        $this->merge([
            'contact' => $this->isEmailSignup() ? mb_strtolower($contact) : $this->normalisePhone($contact),
        ]);
    }

    /**
     * Determine whether the signup is being reached by email.
     */
    private function isEmailSignup(): bool
    {
        return $this->input('contact_method') === PulseContactMethod::Email->value;
    }

    /**
     * Reduce a phone number to its local Rwandan form where it has one.
     */
    private function normalisePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if (strlen($digits) === 12 && str_starts_with($digits, '250')) {
            return '0'.substr($digits, 3);
        }

        if (strlen($digits) === 9 && str_starts_with($digits, '7')) {
            return '0'.$digits;
        }

        return $digits;
    }
}
