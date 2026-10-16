<?php

declare(strict_types=1);

namespace App\Http\Requests\Investor;

use App\Application\Primary\ManagePrimaryCheckout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** The lookup of one purchase command by its own `request_id`, within the campaign it was sent for. */
class ShowPrimaryOperationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*') || $this->user()->tokenCan('investor:read'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['identity_context_revision' => ['required', 'integer', 'min:0'],
            'command' => ['required', 'string', Rule::in(ManagePrimaryCheckout::COMMANDS)],
            'reservation' => ['nullable', 'ulid']];
    }
}
