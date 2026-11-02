<?php

declare(strict_types=1);

namespace App\Http\Requests\Auditor;

/**
 * An Audit Partner's factual statement review (#396): the evidence, transcription and earlier
 * verification it was made against, and the review itself. The review's content is the domain's to
 * refuse, so those refusals are journalled and replay under the request key.
 */
class VerifyStatementsRequest extends AuditorCommandRequest
{
    /** @return array<string, list<string>> */
    protected function commandRules(): array
    {
        return ['expected_evidence_revision' => ['required', 'integer', 'min:0'], 'expected_verification_revision' => ['required', 'integer', 'min:0'],
            'transcription_id' => ['required', 'ulid'], 'transcription_sha256' => ['required', 'string', 'regex:/^[0-9a-f]{64}$/'], 'review' => ['required', 'array']];
    }
}
