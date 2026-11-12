<?php

declare(strict_types=1);

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;

/** `prospectus.save` input only. Whether the draft is well formed, complete and signable is decided on the server. */
class SaveBusinessProspectusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['request_id' => ['required', 'uuid'], 'identity_context_revision' => ['sometimes', 'integer', 'min:0'],
            'expected_version' => ['required', 'integer', 'min:0'], 'purpose' => ['present', 'nullable', 'string'],
            'use_of_funds' => ['present', 'array'], 'forecast' => ['present', 'array'], 'assumptions' => ['present', 'nullable', 'string'],
            'downside' => ['present', 'nullable', 'string'], 'profiles' => ['present', 'array'],
            'largest_customer_share_percent' => ['present', 'nullable'], 'risks' => ['present', 'array']];
    }
}
