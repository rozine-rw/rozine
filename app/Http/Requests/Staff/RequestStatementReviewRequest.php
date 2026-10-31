<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A staff request for a statement review, bound to the readiness the staff member reviewed
 * (contract initial-statement-audit-1 §6; design resolution R1.3). The reason itself is checked by the domain.
 */
class RequestStatementReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*') || ($this->user()->tokenCan('staff:audit:manage') && $this->user()->tokenCan('staff:audit:read')));
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['request_id' => ['required', 'uuid'], 'reason' => ['present', 'nullable', 'string', 'max:10000'],
            'expected_business_revision' => ['required', 'integer', 'min:0'], 'expected_mandate_version' => ['required', 'integer', 'min:0'],
            'expected_evidence_revision' => ['required', 'integer', 'min:0'], 'transcription_id' => ['required', 'ulid'],
            'transcription_sha256' => ['required', 'string', 'regex:/^[0-9a-f]{64}$/'], 'readiness_sha256' => ['required', 'string', 'regex:/^[0-9a-f]{64}$/']];
    }

    /** @return array{expected_business_revision: int, expected_mandate_version: int, expected_evidence_revision: int, transcription_id: string, transcription_sha256: string, readiness_sha256: string} */
    public function binding(): array
    {
        return ['expected_business_revision' => (int) $this->validated('expected_business_revision'), 'expected_mandate_version' => (int) $this->validated('expected_mandate_version'),
            'expected_evidence_revision' => (int) $this->validated('expected_evidence_revision'), 'transcription_id' => (string) $this->validated('transcription_id'),
            'transcription_sha256' => (string) $this->validated('transcription_sha256'), 'readiness_sha256' => (string) $this->validated('readiness_sha256')];
    }
}
