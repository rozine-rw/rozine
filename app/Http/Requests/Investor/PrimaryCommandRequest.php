<?php

declare(strict_types=1);

namespace App\Http\Requests\Investor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The Investor purchase commands as the C3 checkout sends them. The route names the campaign,
 * reservation or commitment; a body identifier may only repeat it, never select another. Party,
 * wallet, terms, fees and policy are resolved on the server.
 */
class PrimaryCommandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*')
            || ($this->user()->tokenCan('investor:read') && $this->user()->tokenCan('investor:command')));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $common = ['request_id' => ['required', 'uuid'], 'identity_context_revision' => ['required', 'integer', 'min:0']];
        $revision = ['required', 'integer', 'min:0', 'max:1000000'];

        return match (true) {
            $this->routeIs('*.primary.reserve') => [...$common, 'campaign_id' => ['required', Rule::in([(string) $this->route('campaign')])],
                'units' => ['required', 'string', 'regex:/^[1-9][0-9]{0,4}$/D'], 'expected_campaign_revision' => $revision, 'quote_revision' => $revision],
            $this->routeIs('*.primary.confirm') => [...$common, 'reservation_id' => ['required', Rule::in([(string) $this->route('reservation')])],
                'expected_reservation_revision' => $revision, 'disclosure_version' => ['required', 'string', 'max:100'],
                'disclosure_sha256' => ['required', 'string', 'regex:/^[0-9a-f]{64}$/D'], 'acknowledged' => ['required', 'accepted']],
            $this->routeIs('*.primary.release') => [...$common, 'reservation_id' => ['required', Rule::in([(string) $this->route('reservation')])],
                'expected_reservation_revision' => $revision],
            default => [...$common, 'commitment_id' => ['required', Rule::in([(string) $this->route('commitment')])],
                'expected_commitment_revision' => $revision],
        };
    }
}
