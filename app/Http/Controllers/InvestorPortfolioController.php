<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Operations\ReadChanges;
use App\Application\Primary\GetInvestorPortfolio;
use App\Http\Requests\Investor\ShowPortfolioRequest;
use App\Http\Resources\InvestorPortfolioResource;
use Inertia\Inertia;
use Inertia\Response;

/** The Investor Portfolio (`investor.portfolio`), a web page only. */
class InvestorPortfolioController extends Controller
{
    /** The beacon cursor is taken before the facts, so every change they could miss is still ahead of it. */
    public function show(ShowPortfolioRequest $request, GetInvestorPortfolio $portfolio, ReadChanges $changes): Response
    {
        $context = $request->validated('identity_context_revision');
        $cursor = $changes->cursor((int) $request->user()?->getAuthIdentifier());
        $facts = $portfolio->handle((int) $request->user()?->getAuthIdentifier(), $context === null ? null : (int) $context);

        return Inertia::render('investor/portfolio', (new InvestorPortfolioResource(['portfolio' => $facts, 'tab' => (string) ($request->validated('tab') ?? 'active'),
            'changes_cursor' => $cursor]))->resolve($request));
    }
}
