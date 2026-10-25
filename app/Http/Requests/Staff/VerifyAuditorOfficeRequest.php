<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A partner's registered office as staff verified it: the coordinates, how far they may be off,
 * the evidence checked and the reason the audit trail keeps. Dispatch measures the 30 km from it.
 */
class VerifyAuditorOfficeRequest extends FormRequest
{
    /** Decimal degrees with at most seven places, as the location record keeps them. */
    private const COORDINATE = 'regex:/^-?(?:0|[1-9][0-9]{0,2})(?:\.[0-9]{1,7})?$/';

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['request_id' => ['required', 'uuid'], 'expected_revision' => ['required', 'integer', 'min:0'],
            'latitude' => ['required', 'string', self::COORDINATE, 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'string', self::COORDINATE, 'numeric', 'between:-180,180'],
            'uncertainty_m' => ['required', 'integer', 'min:0', 'max:30000'],
            'reference' => ['required', 'string', 'max:255'], 'reason' => ['required', 'string', 'max:2000']];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        $messages = [];
        foreach (['latitude' => 'Enter the latitude in decimal degrees, for example -1.9441.', 'longitude' => 'Enter the longitude in decimal degrees, for example 30.0619.',
            'uncertainty_m' => 'Enter how many metres the point may be off, from 0 to 30,000.'] as $field => $message) {
            foreach (['required', 'string', 'regex', 'numeric', 'between', 'integer', 'min', 'max'] as $rule) {
                $messages[$field.'.'.$rule] = $message;
            }
        }

        return [...$messages, 'reference.required' => 'Say what evidence you checked.', 'reference.max' => 'Keep the evidence reference under 255 characters.',
            'reason.required' => 'Give the reason for this verification.', 'reason.max' => 'Keep the reason under 2,000 characters.'];
    }
}
