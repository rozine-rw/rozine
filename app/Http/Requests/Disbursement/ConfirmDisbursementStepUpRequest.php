<?php

declare(strict_types=1);

namespace App\Http\Requests\Disbursement;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The staff step-up exchange body `{expected_revision, intent_digest, code}` (#96 answer 11). It
 * carries no `request_id`: the exchange is never journaled, and a lost answer needs a fresh
 * step-up.
 */
class ConfirmDisbursementStepUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*')
            || ($this->user()->tokenCan('staff:disbursements:read') && $this->user()->tokenCan('staff:disbursements:manage')));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['expected_revision' => ['required', 'integer', 'min:1', 'max:1000000'], 'intent_digest' => ['required', 'string', 'regex:/^[0-9a-f]{64}$/D'],
            'code' => ['required', 'string', 'max:16'], 'request_id' => ['prohibited']];
    }
}
