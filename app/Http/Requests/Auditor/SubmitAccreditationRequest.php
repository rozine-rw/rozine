<?php

declare(strict_types=1);

namespace App\Http\Requests\Auditor;

/**
 * A first-time or renewal submission, sent as multipart form data with the certificate. Only the
 * transport shape is checked here: the licence, expiry and certificate type are refused by the
 * domain, whose refusal is recorded under the request ID.
 */
class SubmitAccreditationRequest extends AuditorCommandRequest
{
    /** @return array<string, list<string>> */
    protected function commandRules(): array
    {
        return [
            'licence' => ['required', 'string', 'max:255'],
            'expires_on' => ['required', 'string', 'max:32'],
            'certificate' => ['required', 'file', 'max:10240'],
        ];
    }
}
