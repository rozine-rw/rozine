<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Concerns\PasswordValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/** Closing an account takes the account's password and the word DELETE typed out, as the design asks. */
class CloseAccountRequest extends FormRequest
{
    use PasswordValidationRules;

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'password' => $this->currentPasswordRules(),
            'confirmation' => ['required', 'string', function (string $attribute, mixed $value, \Closure $fail): void {
                if (mb_strtoupper(trim((string) $value)) !== 'DELETE') {
                    $fail(__('Type DELETE to confirm.'));
                }
            }],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
