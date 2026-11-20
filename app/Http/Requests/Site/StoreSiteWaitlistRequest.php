<?php

declare(strict_types=1);

namespace App\Http\Requests\Site;

use App\Enums\PulseContactMethod;
use App\Enums\PulseSignupType;
use App\Models\PulseSignup;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The landing page waitlist: who is joining, their name and one way to reach them.
 * A single field takes either a phone number or an email, so the contact method is
 * read from what was typed rather than asked for.
 */
class StoreSiteWaitlistRequest extends FormRequest
{
    /**
     * Phone numbers arrive with spaces as people type them; store the digits.
     */
    protected function prepareForValidation(): void
    {
        $contact = $this->input('contact');

        if (! is_string($contact)) {
            return;
        }

        $contact = trim($contact);

        if (! str_contains($contact, '@')) {
            $contact = (string) preg_replace('/[\s\-()]/', '', $contact);
        }

        $this->merge(['contact' => $contact]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'role' => ['required', Rule::enum(PulseSignupType::class)],
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'contact' => [
                'required',
                'string',
                'max:255',
                $this->contactMethod() === PulseContactMethod::Email
                    ? 'email:rfc'
                    : 'regex:/^\+?[0-9]{9,15}$/',
                Rule::unique(PulseSignup::class, 'contact'),
            ],
            'ref' => ['nullable', 'string', 'max:64', 'alpha_num:ascii'],
        ];
    }

    /**
     * Get custom messages for the validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $isBusiness = $this->input('role') === PulseSignupType::Business->value;

        return [
            'name.required' => $isBusiness ? 'Add your business name.' : 'Add your name.',
            'name.min' => $isBusiness ? 'Add your business name.' : 'Add your name.',
            'contact.required' => 'Add a valid phone number or email.',
            'contact.email' => 'Add a valid phone number or email.',
            'contact.regex' => 'Add a valid phone number or email.',
            'contact.unique' => $this->contactMethod() === PulseContactMethod::Email
                ? 'This email is already on the waitlist.'
                : 'This phone number is already on the waitlist.',
        ];
    }

    /**
     * How the person asked to be reached, read from what they typed.
     */
    public function contactMethod(): PulseContactMethod
    {
        return str_contains((string) $this->input('contact'), '@')
            ? PulseContactMethod::Email
            : PulseContactMethod::Phone;
    }
}
