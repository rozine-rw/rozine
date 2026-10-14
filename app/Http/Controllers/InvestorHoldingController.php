<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Primary\GetInvestorHolding;
use App\Http\Requests\Investor\ShowHoldingRequest;
use App\Http\Resources\InvestorHoldingResource;
use Inertia\Inertia;
use Inertia\Response;

/** One issued Holding (`investor.holdings.show`), on the web and API v1. */
class InvestorHoldingController extends Controller
{
    public function show(ShowHoldingRequest $request, GetInvestorHolding $holding, string $holdingId): Response|InvestorHoldingResource
    {
        $context = $request->validated('identity_context_revision');
        $resource = new InvestorHoldingResource($holding->handle((int) $request->user()?->getAuthIdentifier(), $context === null ? null : (int) $context, $holdingId));

        return $request->routeIs('api.*') ? $resource : Inertia::render('investor/holding', $resource->resolve($request));
    }
}
