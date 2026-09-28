<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Wallet\FindWalletOperation;
use App\Application\Wallet\GetInvestorWallet;
use App\Application\Wallet\RecordDepositIntent;
use App\Http\Requests\Investor\DepositRequest;
use App\Http\Requests\Investor\ShowWalletOperationRequest;
use App\Http\Requests\Investor\ShowWalletRequest;
use App\Http\Resources\InvestorWalletResource;
use App\Http\Resources\OperationResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvestorWalletController extends Controller
{
    public function __construct(private GetInvestorWallet $wallet) {}

    public function show(ShowWalletRequest $request): Response|InvestorWalletResource
    {
        $context = $request->validated('identity_context_revision');
        $resource = new InvestorWalletResource($this->commandsFor($request, $this->wallet->handle((int) $request->user()?->getAuthIdentifier(),
            $context === null ? null : (int) $context, $request->safe()->only(['kind', 'amount', 'movement', 'before', 'receipt']))));

        return $request->routeIs('api.*') ? $resource : Inertia::render('investor/wallet', $resource->resolve($request));
    }

    public function deposit(DepositRequest $request, RecordDepositIntent $action): OperationResource
    {
        $result = $action->handle((int) $request->user()?->getAuthIdentifier(), (int) $request->validated('identity_context_revision'),
            (string) $request->validated('request_id'), ['currency' => (string) $request->validated('amount.currency'), 'amount' => (string) $request->validated('amount.amount')],
            (string) $request->validated('method_id'));

        return $this->present($request, $result);
    }

    public function operation(ShowWalletOperationRequest $request, FindWalletOperation $action): OperationResource
    {
        return $this->present($request, $action->handle((int) $request->user()?->getAuthIdentifier(), (int) $request->validated('identity_context_revision'),
            (string) $request->validated('command'), (string) $request->route('request_id')));
    }

    /**
     * The recorded result, unchanged, beside a freshly authorized projection of the intent it
     * recorded: `current` may carry the separate credit receipt; `next` is null because the page
     * reloads its own facts.
     *
     * @param  array<string, mixed>  $result
     */
    private function present(Request $request, array $result): OperationResource
    {
        $receipt = $result['data']['receipt'] ?? null;
        $current = null;
        $allowed = [];
        if ($result['status'] === 'completed') {
            $context = $request->integer('identity_context_revision');
            $page = (new InvestorWalletResource($this->commandsFor($request, $this->wallet->handle((int) $request->user()?->getAuthIdentifier(), $context,
                ['receipt' => (string) $result['data']['intent_id']]))))->resolve($request);
            $current = $page['receipt'];
            $allowed = $page['allowed_actions'];
            $receipt = [...$receipt, 'link' => InvestorWalletResource::lookup($request, $context, $receipt['request_id'])];
        }

        return new OperationResource([...$result, 'data' => ['receipt' => $receipt, 'current' => $current, 'next' => null], 'allowed_actions' => $allowed]);
    }

    /**
     * An API token without `investor:command` is offered no command.
     *
     * @param  array<string, mixed>  $facts
     * @return array<string, mixed>
     */
    private function commandsFor(Request $request, array $facts): array
    {
        return $request->routeIs('api.*') && ! $request->user()?->tokenCan('investor:command') ? [...$facts, 'allowed_actions' => []] : $facts;
    }
}
