<?php

declare(strict_types=1);

namespace App\Http\Requests\Identity;

use Illuminate\Foundation\Http\FormRequest;

/** One document for a role-switch application: a company or licence document, or a director's or owner's ID. */
class UploadRoleApplicationDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['request_id' => ['required', 'uuid'], 'identity_context_revision' => ['required', 'integer', 'min:0'],
            'expected_revision' => ['required', 'integer', 'min:0'], 'slot' => ['required', 'string', 'max:40', 'regex:/^(?:[a-z_]+|id:[A-Z0-9]{6,16})$/'],
            'file' => ['required', 'file', 'max:10240']];
    }
}
