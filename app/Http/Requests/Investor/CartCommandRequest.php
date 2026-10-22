<?php

declare(strict_types=1);

namespace App\Http\Requests\Investor;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Adding a deal to the cart, changing a line's units and taking a line out (BRS v2.0 INV-R02).
 * The route names the campaign; units are whole RWF 5,000 notes. The Party, the deal's state and
 * the 50% limit are resolved on the server.
 */
class CartCommandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*')
            || ($this->user()->tokenCan('investor:read') && $this->user()->tokenCan('investor:command')));
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        $context = ['identity_context_revision' => ['required', 'integer', 'min:0']];

        return $this->routeIs('*.cart_remove') ? $context : [...$context, 'units' => ['required', 'integer', 'min:1', 'max:99999']];
    }
}
