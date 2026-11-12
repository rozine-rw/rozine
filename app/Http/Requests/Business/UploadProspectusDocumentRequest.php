<?php

declare(strict_types=1);

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;

/** `prospectus.document.upload` input only. The kind, name, size and file type are checked on the server. */
class UploadProspectusDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['request_id' => ['required', 'uuid'], 'identity_context_revision' => ['sometimes', 'integer', 'min:0'],
            'kind' => ['required', 'string', 'max:16'], 'file' => ['required', 'file', 'max:10240']];
    }
}
