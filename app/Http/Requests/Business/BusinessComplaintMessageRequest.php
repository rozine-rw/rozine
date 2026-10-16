<?php

declare(strict_types=1);

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;

/** `complaint.open` and `complaint.reply` input for a Business. The actor's authority and the text are decided on the server. */
class BusinessComplaintMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['request_id' => ['required', 'uuid'], 'identity_context_revision' => ['sometimes', 'integer', 'min:0'],
            'body' => ['present', 'nullable', 'string', 'max:4000']];
    }
}
