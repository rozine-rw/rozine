<?php

declare(strict_types=1);

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;

/** `prospectus.sign` input only: the draft version the representative read. The gates are re-checked on the server. */
class SignBusinessProspectusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['request_id' => ['required', 'uuid'], 'identity_context_revision' => ['sometimes', 'integer', 'min:0'],
            'expected_version' => ['required', 'integer', 'min:1']];
    }
}
