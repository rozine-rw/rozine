<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

/** Verify and reject alike: the reviewed revision and submission, and the reason the audit trail keeps. */
class DecideAuditorLicenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*')
            || ($this->user()->tokenCan('staff:auditors:read') && $this->user()->tokenCan('staff:auditors:verify')));
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['request_id' => ['required', 'uuid'], 'expected_revision' => ['required', 'integer', 'min:1'],
            'submission_id' => ['required', 'string', 'ulid'], 'reason' => ['required', 'string', 'max:2000']];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['reason.required' => 'Give the reason for this decision.', 'reason.max' => 'Keep the reason under 2,000 characters.'];
    }
}
