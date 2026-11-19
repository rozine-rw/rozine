<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Operations\ReadChanges;
use App\Application\Primary\GetInvestorLot;
use App\Http\Requests\Investor\ShowHoldingRequest;
use App\Http\Resources\InvestorLotResource;
use Inertia\Inertia;
use Inertia\Response;

/** One lot of note units bought on the market (`investor.lots.show`), on the web. */
class InvestorLotController extends Controller
{
    /** The beacon cursor is taken before the facts, so every change they could miss is still ahead of it. */
    public function show(ShowHoldingRequest $request, GetInvestorLot $lot, ReadChanges $changes, string $lotId): Response
    {
        $context = $request->validated('identity_context_revision');
        $userId = (int) $request->user()?->getAuthIdentifier();
        $cursor = $changes->cursor($userId);
        $resource = new InvestorLotResource([...$lot->handle($userId, $context === null ? null : (int) $context, $lotId), 'changes_cursor' => $cursor]);

        return Inertia::render('investor/lot', $resource->resolve($request));
    }
}
