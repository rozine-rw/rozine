<?php

declare(strict_types=1);

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;

/** `business.profile.update` input only. Whether each field is valid, and the actor permitted, is decided on the server. */
class SaveBusinessContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['request_id' => ['required', 'uuid'], 'identity_context_revision' => ['sometimes', 'integer', 'min:0'],
            'email' => ['present', 'nullable', 'string', 'max:200'], 'phone' => ['present', 'nullable', 'string', 'max:20'],
            'province' => ['present', 'nullable', 'string', 'max:40'], 'district' => ['present', 'nullable', 'string', 'max:40'],
            'sector' => ['present', 'nullable', 'string', 'max:120'], 'cell' => ['present', 'nullable', 'string', 'max:120'],
            'street' => ['present', 'nullable', 'string', 'max:200']];
    }
}
