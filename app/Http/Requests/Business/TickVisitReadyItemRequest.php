<?php

declare(strict_types=1);

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;

/** `business.visit_ready.tick` input only. Whether the item is on the open checklist, and the actor permitted, is decided on the server. */
class TickVisitReadyItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['request_id' => ['required', 'uuid'], 'identity_context_revision' => ['sometimes', 'integer', 'min:0'],
            'due_on' => ['required', 'date_format:Y-m-d'], 'item' => ['required', 'string', 'max:40'], 'ticked' => ['required', 'boolean']];
    }
}
