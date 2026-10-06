<?php

declare(strict_types=1);

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CancelCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*')
            || ($this->user()->tokenCan('business:read') && $this->user()->tokenCan('business:command')));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['request_id' => ['required', 'uuid'], 'identity_context_revision' => ['required', 'integer', 'min:0'],
            'campaign_id' => ['required', 'ulid', Rule::in([(string) $this->route('campaign')])],
            'expected_campaign_revision' => ['required', 'integer', 'min:0'], 'reason' => ['nullable', 'string', 'max:1000']];
    }
}
