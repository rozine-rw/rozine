<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A staff independence review of one Audit Partner against a Business, from the Business's 360: the
 * four conflict facts, when any past role at the Business ended, the evidence checked and why, under
 * one request, the review revision it answers and the server time the form was issued at.
 */
class RecordAuditorIndependenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['request_id' => ['required', 'uuid'], 'expected_revision' => ['required', 'integer', 'min:0'],
            'checked_at' => ['required', 'date_format:Y-m-d\TH:i:s\Z'],
            'financial_interest' => ['required', 'boolean'], 'current_role_tie' => ['required', 'boolean'],
            'family_or_business_conflict' => ['required', 'boolean'], 'unresolved_conflict' => ['required', 'boolean'],
            'role_tie_ended_on' => ['nullable', 'date_format:Y-m-d'], 'reference' => ['nullable', 'string', 'max:10000'],
            'reason' => ['nullable', 'string', 'max:10000']];
    }
}
