<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Primary\GetInvestorCheckout;
use App\Application\Primary\GetInvestorDeals;
use App\Http\Requests\Investor\ShowDealsRequest;
use App\Http\Resources\InvestorCheckoutResource;
use App\Http\Resources\InvestorDealsResource;
use Inertia\Inertia;
use Inertia\Response;

/** The Investor Deals deck, one deal and its checkout (`investor.deals`, `investor.deals.show`, `investor.checkout`) on both transports. */
class InvestorDealsController extends Controller
{
    public function index(ShowDealsRequest $request, GetInvestorDeals $action): Response|InvestorDealsResource
    {
        /** @var array{sort?: string, industry?: string, deal?: string} $query */
        $query = $request->safe()->only(['sort', 'industry', 'deal']);
        $resource = new InvestorDealsResource($action->page($this->user($request), $this->context($request), $query));

        return $request->routeIs('api.*') ? $resource : Inertia::render('investor/deals', $resource->resolve($request));
    }

    public function show(ShowDealsRequest $request, GetInvestorDeals $action, string $campaign): Response|InvestorDealsResource
    {
        $resource = new InvestorDealsResource($action->show($this->user($request), $this->context($request), $campaign), true);

        return $request->routeIs('api.*') ? $resource : Inertia::render('investor/deal', $resource->resolve($request));
    }

    /** The live checkout sheet for one deal (`investor.checkout`), on both transports. */
    public function checkout(ShowDealsRequest $request, GetInvestorCheckout $action, string $campaign): Response|InvestorCheckoutResource
    {
        $units = $request->validated('units');
        $reservation = $request->validated('reservation');
        $resource = new InvestorCheckoutResource($action->handle($this->user($request), $this->context($request), $campaign,
            $units === null ? null : (int) $units, is_string($reservation) ? strtolower($reservation) : null));

        return $request->routeIs('api.*') ? $resource : Inertia::render('investor/checkout', $resource->resolve($request));
    }

    private function user(ShowDealsRequest $request): int
    {
        return (int) $request->user()?->getAuthIdentifier();
    }

    private function context(ShowDealsRequest $request): ?int
    {
        $context = $request->validated('identity_context_revision');

        return $context === null ? null : (int) $context;
    }
}
