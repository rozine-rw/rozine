<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

/** `complaint.answer` and `complaint.resolve` input only. The permission and the text are decided on the server. */
class AnswerComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['request_id' => ['required', 'uuid'], 'body' => ['present', 'nullable', 'string', 'max:4000'], 'resolves' => ['required', 'boolean']];
    }
}
