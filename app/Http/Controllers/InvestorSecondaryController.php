<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Secondary\TradeOnSecondaryMarket;
use App\Http\Requests\Investor\SecondaryCommandRequest;
use App\Http\Resources\OperationResource;

/**
 * The Investor's resale commands on the web session (BRS v3.0 §12): `investor.market_list`,
 * `investor.market_reserve`, `investor.market_settle` and `investor.market_withdraw`. Each answers
 * the market's recorded result; the Market page reloads its own facts after it.
 */
class InvestorSecondaryController extends Controller
{
    public function list(SecondaryCommandRequest $request, TradeOnSecondaryMarket $market): OperationResource
    {
        return OperationResource::fromResult($market->list($this->user($request), $request->integer('identity_context_revision'),
            (string) $request->validated('lot_id'), (string) $request->validated('units'), (string) $request->validated('price'),
            (string) $request->validated('request_id')), '');
    }

    public function reserve(SecondaryCommandRequest $request, TradeOnSecondaryMarket $market, string $listing): OperationResource
    {
        return OperationResource::fromResult($market->reserve($this->user($request), $request->integer('identity_context_revision'), $listing,
            (string) $request->validated('units'), (string) $request->validated('request_id')), '');
    }

    public function settle(SecondaryCommandRequest $request, TradeOnSecondaryMarket $market, string $listing): OperationResource
    {
        return OperationResource::fromResult($market->settle($this->user($request), $request->integer('identity_context_revision'), $listing,
            (string) $request->validated('reservation_id'), (string) $request->validated('request_id')), '');
    }

    public function withdraw(SecondaryCommandRequest $request, TradeOnSecondaryMarket $market, string $listing): OperationResource
    {
        return OperationResource::fromResult($market->withdraw($this->user($request), $request->integer('identity_context_revision'), $listing,
            (string) $request->validated('request_id')), '');
    }

    private function user(SecondaryCommandRequest $request): int
    {
        return (int) $request->user()?->getAuthIdentifier();
    }
}
