<?php

declare(strict_types=1);

namespace App\Http\Requests\Business;

class SubmitApplicationRequest extends BusinessCommandRequest
{
    /** @return array<string, list<string>> */
    protected function commandRules(): array
    {
        return [
            'quote_id' => ['required', 'string', 'max:80'], 'quote_revision' => ['required', 'integer'],
            'evidence_version' => ['required', 'string', 'max:80'], 'mandate_version' => ['required', 'string', 'max:80'],
            'accepted_principal' => ['required', 'string', 'max:80'], 'terms' => ['present', 'boolean'], 'privacy' => ['present', 'boolean'],
            'signature_name' => ['present', 'nullable', 'string', 'max:1000'],
            'documents' => ['present', 'array', 'list', 'max:30'], 'documents.*' => ['array:kind,version,sha256'],
            'documents.*.kind' => ['required', 'string', 'max:100'], 'documents.*.version' => ['required', 'string', 'max:100'], 'documents.*.sha256' => ['required', 'string', 'max:100'],
            'disclosures' => ['present', 'array', 'list', 'max:30'], 'disclosures.*' => ['array:key,version,sha256'],
            'disclosures.*.key' => ['required', 'string', 'max:100'], 'disclosures.*.version' => ['required', 'string', 'max:100'], 'disclosures.*.sha256' => ['required', 'string', 'max:100'],
        ];
    }
}
