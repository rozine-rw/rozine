<?php

declare(strict_types=1);

namespace App\Http\Requests\Investor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The whole-cart checkout commands (#383, contract cart-checkout-1 §5): quote the saved cart,
 * hold every line of the quote at once, confirm or release the held checkout. The route names the
 * checkout; a body identifier may only repeat it. The Party, lines, prices, band and fees are
 * resolved on the server, so none is sent.
 */
class CartCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (! $this->routeIs('api.*')
            || ($this->user()->tokenCan('investor:read') && $this->user()->tokenCan('investor:command')));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $context = ['identity_context_revision' => ['required', 'integer', 'min:0']];
        $revision = ['required', 'integer', 'min:0', 'max:1000000'];
        $command = [...$context, 'request_id' => ['required', 'uuid']];
        $sha256 = ['required', 'string', 'regex:/^[0-9a-f]{64}$/D'];
        $quote = ['quote_id' => ['required', 'ulid'], 'expected_quote_revision' => $revision];
        $checkout = ['checkout_id' => ['sometimes', Rule::in([(string) $this->route('checkout')])], 'expected_revision' => $revision];

        return match (true) {
            $this->routeIs('*.cart_quote') => [...$context, 'expected_cart_revision' => $revision],
            $this->routeIs('*.cart_reserve') => [...$command, ...$quote, 'expected_cart_revision' => $revision, 'quote_disclosure_sha256' => $sha256],
            $this->routeIs('*.cart_confirm') => [...$command, ...$quote, ...$checkout, 'acknowledged_disclosure_sha256' => $sha256,
                'acknowledged' => ['required', 'accepted']],
            default => [...$command, ...$checkout],
        };
    }
}
