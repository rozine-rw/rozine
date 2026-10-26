<?php

declare(strict_types=1);

namespace App\Http\Requests\Identity;

use Illuminate\Foundation\Http\FormRequest;

class AddInvestorAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'expected_revision' => ['required', 'integer', 'min:0'],
            'terms_version' => ['required', 'string', 'max:64'],
            'terms_sha256' => ['required', 'string', 'size:64'],
            'accepted' => ['accepted'],
            'request_id' => ['required', 'uuid'],
        ];
    }
}
