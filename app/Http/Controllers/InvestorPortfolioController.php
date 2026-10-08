<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Primary\GetInvestorPortfolio;
use App\Http\Requests\Investor\ShowPortfolioRequest;
use App\Http\Resources\InvestorPortfolioResource;
use Inertia\Inertia;
use Inertia\Response;

/** The Investor Portfolio (`investor.portfolio`), a web page only. */
class InvestorPortfolioController extends Controller
{
    public function show(ShowPortfolioRequest $request, GetInvestorPortfolio $portfolio): Response
    {
        $context = $request->validated('identity_context_revision');
        $facts = $portfolio->handle((int) $request->user()?->getAuthIdentifier(), $context === null ? null : (int) $context);

        return Inertia::render('investor/portfolio', (new InvestorPortfolioResource([...$facts, 'tab' => $request->validated('tab') ?? 'active']))->resolve($request));
    }
}
