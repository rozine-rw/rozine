<?php

declare(strict_types=1);

namespace App\Http\Requests\Identity;

use App\Concerns\LegalConsentValidationRules;
use Illuminate\Foundation\Http\FormRequest;

/** A signed-in person ticking the Terms, the Privacy Notice and the Disclaimers on the review page. */
class AcceptLegalDocumentsRequest extends FormRequest
{
    use LegalConsentValidationRules;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return $this->legalConsentRules();
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return $this->legalConsentMessages();
    }
}
