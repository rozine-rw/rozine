<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Onboarding a business for its owner: a company (by its RDB code) or a sole trader, its profile, the
 * account of its official representative, the evidence checked and the reason. Each opening of the form
 * carries one request key for the registry lookup and one for the mandate, so a resubmit replays both.
 */
class OnboardBusinessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['request_id' => ['required', 'uuid'], 'mandate_request_id' => ['required', 'uuid', 'different:request_id'],
            'kind' => ['required', 'in:organization,person'], 'rdb_code' => ['nullable', 'required_if:kind,organization', 'prohibited_if:kind,person', 'regex:/^[0-9]{9}$/D'],
            'name' => ['required', 'string', 'max:180'], 'industry' => ['required', 'string', 'max:180'], 'district' => ['required', 'string', 'max:180'],
            'established_year' => ['nullable', 'integer', 'min:1800', 'max:'.now()->year], 'representative_email' => ['required', 'email', 'max:255'],
            'evidence_reference' => ['required', 'string', 'max:255'], 'reason' => ['required', 'string', 'max:2000'], 'attested' => ['accepted']];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['kind.required' => 'Choose a company or a sole trader.', 'kind.in' => 'Choose a company or a sole trader.',
            'rdb_code.required_if' => 'Enter the RDB company code.', 'rdb_code.regex' => 'The RDB company code is 9 digits.',
            'rdb_code.prohibited_if' => 'A sole trader has no RDB company code.',
            'name.required' => "Enter the business's name.", 'name.max' => 'Keep the name under 180 characters.',
            'industry.required' => 'Enter the sector.', 'industry.max' => 'Keep the sector under 180 characters.',
            'district.required' => 'Enter the district.', 'district.max' => 'Keep the district under 180 characters.',
            'established_year.integer' => 'Enter the year as four digits.', 'established_year.min' => 'Enter a year from 1800.',
            'established_year.max' => 'The year cannot be in the future.',
            'representative_email.required' => "Enter the representative's email.", 'representative_email.email' => 'Enter a valid email address.',
            'evidence_reference.required' => 'Say which documents you checked.', 'evidence_reference.max' => 'Keep the evidence under 255 characters.',
            'reason.required' => 'Give the reason for this change.', 'reason.max' => 'Keep the reason under 2,000 characters.',
            'attested.accepted' => 'Confirm that the people named are everyone with authority over this business.'];
    }
}
