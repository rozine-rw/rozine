<?php

declare(strict_types=1);

namespace App\Http\Requests\Disbursement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * `{request_id, expected_revision, reason}`. The route names the disbursement; a body identifier may
 * only repeat it, never select another. The reason's content is judged by the recorded command, so
 * a bad reason is a journaled refusal.
 */
class DisbursementCommandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*')
            || ($this->user()->tokenCan('staff:disbursements:read') && $this->user()->tokenCan('staff:disbursements:manage')));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['request_id' => ['required', 'uuid'], 'expected_revision' => ['required', 'integer', 'min:0', 'max:1000000'],
            'reason' => ['present', 'nullable', 'string', 'max:4000'], 'disbursement_id' => ['sometimes', Rule::in([(string) $this->route('disbursement')])]];
    }
}
