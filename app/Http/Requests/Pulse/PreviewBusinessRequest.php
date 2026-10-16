<?php

declare(strict_types=1);

namespace App\Http\Requests\Pulse;

use App\Concerns\PulseBusinessValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PreviewBusinessRequest extends FormRequest
{
    use PulseBusinessValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->businessSizingRules();
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return $this->businessSizingMessages();
    }
}
