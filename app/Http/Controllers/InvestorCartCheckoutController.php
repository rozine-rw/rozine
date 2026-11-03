<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Primary\Contracts\CartCheckouts;
use App\Application\Primary\Contracts\InvestorCartQuotes;
use App\Application\Primary\QuoteInvestorCart;
use App\Application\Primary\ReserveInvestorCart;
use App\Http\Requests\Investor\CartCheckoutRequest;
use App\Http\Requests\Investor\ShowCartCheckoutRequest;
use App\Http\Resources\OperationResource;
use Illuminate\Http\JsonResponse;

/**
 * The whole-cart checkout transport (#383 step 5, contract cart-checkout-1 §5) for the web session
 * and API v1 alike. A quote answers `CART_QUOTED` with the quote, or the whole-cart refusal with its
 * facts; it is not journaled, so it has no operation id. Reserve, confirm and release answer their
 * recorded operation unchanged, as the single-deal checkout does: `receipt` holds the facts it
 * recorded, `current` and `next` are null and the cart page reloads its own authorized facts.
 */
class InvestorCartCheckoutController extends Controller
{
    public function __construct(private ReserveInvestorCart $checkout) {}

    public function quote(CartCheckoutRequest $request, QuoteInvestorCart $quote): OperationResource
    {
        return OperationResource::fromResult($quote->answer($this->user($request), $request->integer('identity_context_revision'),
            $request->integer('expected_cart_revision')), InvestorCartQuotes::CONTRACT_VERSION);
    }

    public function reserve(CartCheckoutRequest $request): OperationResource
    {
        return $this->present($this->checkout->handle($this->user($request), $request->integer('identity_context_revision'),
            $request->integer('expected_cart_revision'), (string) $request->validated('quote_id'), $request->integer('expected_quote_revision'),
            (string) $request->validated('quote_disclosure_sha256'), (string) $request->validated('request_id')));
    }

    public function reservation(ShowCartCheckoutRequest $request, string $checkout): JsonResponse
    {
        return response()->json(['data' => $this->checkout->reservation($this->user($request), $request->integer('identity_context_revision'), $checkout)]);
    }

    public function confirm(CartCheckoutRequest $request, string $checkout): OperationResource
    {
        return $this->present($this->checkout->confirm($this->user($request), $request->integer('identity_context_revision'), $checkout,
            $request->integer('expected_revision'), (string) $request->validated('quote_id'), $request->integer('expected_quote_revision'),
            (string) $request->validated('acknowledged_disclosure_sha256'), (string) $request->validated('request_id')));
    }

    public function release(CartCheckoutRequest $request, string $checkout): OperationResource
    {
        return $this->present($this->checkout->release($this->user($request), $request->integer('identity_context_revision'), $checkout,
            $request->integer('expected_revision'), (string) $request->validated('request_id')));
    }

    public function operation(ShowCartCheckoutRequest $request, string $requestId): OperationResource
    {
        $user = $this->user($request);
        $context = $request->integer('identity_context_revision');
        $command = (string) $request->validated('command');

        return $this->present($command === CartCheckouts::RESERVE_COMMAND ? $this->checkout->find($user, $context, $requestId)
            : $this->checkout->findOperation($user, $context, $command, (string) $request->validated('checkout'), $requestId));
    }

    private function user(CartCheckoutRequest|ShowCartCheckoutRequest $request): int
    {
        return (int) $request->user()?->getAuthIdentifier();
    }

    /** @param  array<string, mixed>  $result */
    private function present(array $result): OperationResource
    {
        return new OperationResource([...$result, 'data' => ['receipt' => $result['data'] === [] ? null : $result['data'], 'current' => null, 'next' => null],
            'allowed_actions' => []]);
    }
}
