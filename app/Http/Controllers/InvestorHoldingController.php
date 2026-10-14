<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Operations\ReadChanges;
use App\Application\Primary\GetInvestorHolding;
use App\Http\Requests\Investor\ShowHoldingRequest;
use App\Http\Resources\InvestorHoldingResource;
use Inertia\Inertia;
use Inertia\Response;

/** One issued Holding (`investor.holdings.show`), on the web and API v1. */
class InvestorHoldingController extends Controller
{
    /** The beacon cursor is taken before the facts, so every change they could miss is still ahead of it. */
    public function show(ShowHoldingRequest $request, GetInvestorHolding $holding, ReadChanges $changes, string $holdingId): Response|InvestorHoldingResource
    {
        $context = $request->validated('identity_context_revision');
        $cursor = $changes->cursor((int) $request->user()?->getAuthIdentifier());
        $resource = new InvestorHoldingResource([...$holding->handle((int) $request->user()?->getAuthIdentifier(), $context === null ? null : (int) $context, $holdingId),
            'changes_cursor' => $cursor]);

        return $request->routeIs('api.*') ? $resource : Inertia::render('investor/holding', $resource->resolve($request));
    }
}
