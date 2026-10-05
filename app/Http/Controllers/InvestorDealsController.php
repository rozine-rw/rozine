<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Primary\GetInvestorDeals;
use App\Http\Requests\Investor\ShowDealsRequest;
use App\Http\Resources\InvestorDealsResource;
use Inertia\Inertia;
use Inertia\Response;

/** The Investor Deals deck and one deal (`investor.deals`, `investor.deals.show`) on both transports. */
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
