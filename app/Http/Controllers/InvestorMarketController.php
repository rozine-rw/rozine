<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Primary\GetInvestorCart;
use App\Application\Primary\GetInvestorViewer;
use App\Application\Primary\ManageInvestorCart;
use App\Http\Requests\Investor\CartCommandRequest;
use App\Http\Requests\Investor\ShowInvestorPageRequest;
use App\Http\Resources\InvestorCartResource;
use App\Http\Resources\InvestorShellPageResource;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The Investor Market (`investor.market`) and Cart (`investor.cart`) web pages, and the cart's
 * commands on the web session and API v1 alike. Secondary trading has no read or command yet, so
 * Market draws the design's empty state under current Investor authority. The Cart (BRS v2.0
 * INV-R02) holds the deals an Investor picked, each line with its own units: a deal is added from
 * Deals (`investor.cart_add`), a line's units change (`investor.cart_units`) and a line goes
 * (`investor.cart_remove`).
 */
class InvestorMarketController extends Controller
{
    public function market(ShowInvestorPageRequest $request, GetInvestorViewer $viewer): Response
    {
        return Inertia::render('investor/market', (new InvestorShellPageResource($viewer->handle($this->user($request), $this->context($request))))->resolve($request));
    }

    public function cart(ShowInvestorPageRequest $request, GetInvestorCart $cart): Response
    {
        return Inertia::render('investor/cart', (new InvestorCartResource($cart->handle($this->user($request), $this->context($request))))->resolve($request));
    }

    public function addToCart(CartCommandRequest $request, ManageInvestorCart $cart, string $campaign): JsonResponse
    {
        return response()->json(['data' => [...$cart->add($this->user($request), $request->integer('identity_context_revision'), $campaign,
            $request->integer('units')), 'in_cart' => true]]);
    }

    public function changeCartUnits(CartCommandRequest $request, ManageInvestorCart $cart, string $campaign): JsonResponse
    {
        return response()->json(['data' => [...$cart->change($this->user($request), $request->integer('identity_context_revision'), $campaign,
            $request->integer('units')), 'in_cart' => true]]);
    }

    public function removeFromCart(CartCommandRequest $request, ManageInvestorCart $cart, string $campaign): JsonResponse
    {
        return response()->json(['data' => [...$cart->remove($this->user($request), $request->integer('identity_context_revision'), $campaign), 'in_cart' => false]]);
    }

    private function user(ShowInvestorPageRequest|CartCommandRequest $request): int
    {
        return (int) $request->user()?->getAuthIdentifier();
    }

    private function context(ShowInvestorPageRequest $request): ?int
    {
        $context = $request->validated('identity_context_revision');

        return $context === null ? null : (int) $context;
    }
}
