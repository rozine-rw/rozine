<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Application\Identity\LegalDocuments;
use Illuminate\Validation\Rule;

/**
 * The tick boxes for the Terms, the Privacy Notice and the Disclaimers, shared by sign-up and the
 * review page: each document must be ticked, and must come with the digest of the wording shown.
 */
trait LegalConsentValidationRules
{
    /** @return array<string, list<mixed>> */
    protected function legalConsentRules(): array
    {
        $rules = ['legal_documents' => ['required', 'array:'.implode(',', $this->legalDocumentKeys())],
            'legal_agreements' => ['required', 'array:'.implode(',', $this->legalDocumentKeys())]];
        foreach (app(LegalDocuments::class)->current() as $document) {
            $rules['legal_agreements.'.$document['key']] = ['accepted'];
            $rules['legal_documents.'.$document['key']] = ['required', 'string', Rule::in([$document['sha256']])];
        }

        return $rules;
    }

    /** @return array<string, string> */
    protected function legalConsentMessages(): array
    {
        $messages = ['legal_agreements.required' => 'Tick each box to agree before you continue.',
            'legal_documents.required' => 'These documents have changed. Read the new versions before you continue.',
            'legal_documents.array' => 'These documents have changed. Read the new versions before you continue.',
            'legal_agreements.array' => 'Tick each box to agree before you continue.'];
        foreach (app(LegalDocuments::class)->current() as $document) {
            $messages['legal_agreements.'.$document['key'].'.accepted'] = "Tick the box to agree to the {$document['title']}.";
            foreach (['required', 'string', 'in'] as $rule) {
                $messages['legal_documents.'.$document['key'].'.'.$rule] = "There is a newer version of the {$document['title']}. Read it before you continue.";
            }
        }

        return $messages;
    }

    /** @return list<string> */
    private function legalDocumentKeys(): array
    {
        return array_column(app(LegalDocuments::class)->current(), 'key');
    }
}
