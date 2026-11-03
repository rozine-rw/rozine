<?php

declare(strict_types=1);

namespace App\Http\Requests\Investor;

use App\Application\Primary\Contracts\CartCheckouts;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Reading the Investor's own whole-cart checkout, or one of its commands by its own `request_id`
 * (#383, contract cart-checkout-1 §5). A reserve targets the cart; a confirmation or release
 * targets the checkout it names.
 */
class ShowCartCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*') || $this->user()->tokenCan('investor:read'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $context = ['identity_context_revision' => ['required', 'integer', 'min:0']];

        return $this->routeIs('*.cart_reservation') ? $context : [...$context,
            'command' => ['required', 'string', Rule::in([CartCheckouts::RESERVE_COMMAND, CartCheckouts::CONFIRM_COMMAND, CartCheckouts::RELEASE_COMMAND])],
            'checkout' => ['nullable', 'ulid', Rule::requiredIf(fn (): bool => $this->input('command') !== CartCheckouts::RESERVE_COMMAND)]];
    }
}
