<?php

declare(strict_types=1);

namespace App\Http\Requests\Investor;

use Illuminate\Foundation\Http\FormRequest;

class SaveVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['request_id' => ['required', 'uuid'], 'identity_context_revision' => ['required', 'integer', 'min:0'],
            'expected_revision' => ['required', 'integer', 'min:0'], 'step' => ['required', 'string', 'in:personal,document'],
            'date_of_birth' => ['nullable', 'string', 'max:20'], 'id_type' => ['nullable', 'string', 'max:20'], 'id_number' => ['nullable', 'string', 'max:40']];
    }
}
