<?php

declare(strict_types=1);

namespace App\Http\Requests\Investor;

use Illuminate\Foundation\Http\FormRequest;

/** `complaint.open` and `complaint.reply` input only. Whether the text may be recorded is decided on the server. */
class ComplaintMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['request_id' => ['required', 'uuid'], 'body' => ['present', 'nullable', 'string', 'max:4000']];
    }
}
