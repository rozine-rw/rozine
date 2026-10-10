<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Primary\GetInvestorViewer;
use App\Http\Requests\Investor\ShowInvestorPageRequest;
use App\Http\Resources\InvestorShellPageResource;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The Investor Market (`investor.market`) and Cart (`investor.cart`), web pages only. Secondary
 * trading and a multi-note cart have no read or command yet, so each page draws the design's
 * empty state under current Investor authority and offers no action that would need one.
 */
class InvestorMarketController extends Controller
{
    public function market(ShowInvestorPageRequest $request, GetInvestorViewer $viewer): Response
    {
        return Inertia::render('investor/market', (new InvestorShellPageResource($this->viewer($request, $viewer)))->resolve($request));
    }

    public function cart(ShowInvestorPageRequest $request, GetInvestorViewer $viewer): Response
    {
        return Inertia::render('investor/cart', (new InvestorShellPageResource($this->viewer($request, $viewer)))->resolve($request));
    }

    /** @return array<string, mixed> */
    private function viewer(ShowInvestorPageRequest $request, GetInvestorViewer $viewer): array
    {
        $context = $request->validated('identity_context_revision');

        return $viewer->handle((int) $request->user()?->getAuthIdentifier(), $context === null ? null : (int) $context);
    }
}
