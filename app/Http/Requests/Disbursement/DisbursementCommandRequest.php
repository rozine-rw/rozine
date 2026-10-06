<?php

declare(strict_types=1);

namespace App\Http\Requests\Disbursement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * `{request_id, expected_revision, reason}`, plus `step_up_proof` on approve (#96 answer 3) and the
 * optional `independence_declared` on authorize and approve (#96 5956161592). The
 * route names the disbursement; a body identifier may only repeat it, never select another. The
 * reason's content is judged by the recorded command, so a bad reason is a journaled refusal.
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
            'reason' => ['present', 'nullable', 'string', 'max:4000'], 'disbursement_id' => ['sometimes', Rule::in([(string) $this->route('disbursement')])],
            'step_up_proof' => [$this->route('command') === 'approve' ? 'nullable' : 'prohibited', 'string', 'max:128'],
            'independence_declared' => [in_array($this->route('command'), ['authorize', 'approve'], true) ? 'sometimes' : 'prohibited', 'boolean']];
    }
}
