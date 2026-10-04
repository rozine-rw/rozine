<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\AuthorizeActiveRole;
use App\Application\Wallet\FindBusinessWalletOperation;
use App\Application\Wallet\GetBusinessWallet;
use App\Application\Wallet\RecordBusinessDeposit;
use App\Http\Requests\Business\BusinessDepositRequest;
use App\Http\Requests\Business\ShowBusinessWalletOperationRequest;
use App\Http\Requests\Business\ShowBusinessWalletRequest;
use App\Http\Resources\BusinessWalletResource;
use App\Http\Resources\OperationResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BusinessWalletController extends Controller
{
    public function __construct(private AuthorizeActiveRole $identity, private GetBusinessWallet $wallet) {}

    public function show(ShowBusinessWalletRequest $request): Response|BusinessWalletResource
    {
        $userId = (int) $request->user()?->getAuthIdentifier();
        $revision = $request->validated('identity_context_revision') ?? $this->identity->context($userId, 'business')['context_revision'];
        $resource = new BusinessWalletResource($this->wallet->handle($userId, (int) $revision, (string) $request->route('business'),
            $request->safe()->only(['kind', 'amount', 'movement', 'before', 'receipt'])));

        return $request->routeIs('api.*') ? $resource : Inertia::render('business/wallet', $resource->resolve($request));
    }

    public function deposit(BusinessDepositRequest $request, RecordBusinessDeposit $action): OperationResource
    {
        $result = $action->handle((int) $request->user()?->getAuthIdentifier(), (int) $request->validated('identity_context_revision'),
            (string) $request->route('business'), (string) $request->validated('request_id'),
            ['currency' => (string) $request->validated('amount.currency'), 'amount' => (string) $request->validated('amount.amount')],
            (string) $request->validated('method_id'));

        return $this->present($request, $result);
    }

    public function operation(ShowBusinessWalletOperationRequest $request, FindBusinessWalletOperation $action): OperationResource
    {
        return $this->present($request, $action->handle((int) $request->user()?->getAuthIdentifier(), (int) $request->validated('identity_context_revision'),
            (string) $request->route('business'), (string) $request->validated('command'), (string) $request->route('request_id')));
    }

    /**
     * The recorded result, unchanged, beside a freshly authorized projection of the intent it
     * recorded: `current` is the deposit as the wallet now reads it; `next` is null because the page
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
            $business = (string) $request->route('business');
            $context = $request->integer('identity_context_revision');
            $page = (new BusinessWalletResource($this->wallet->handle((int) $request->user()?->getAuthIdentifier(), $context, $business,
                ['receipt' => (string) $result['data']['intent_id']])))->resolve($request);
            $current = $page['receipt'];
            $allowed = $page['allowed_actions'];
            $receipt = [...$receipt, 'link' => BusinessWalletResource::lookup($request, $business, $context, $receipt['request_id'])];
        }

        return new OperationResource([...$result, 'data' => ['receipt' => $receipt, 'current' => $current, 'next' => null], 'allowed_actions' => $allowed]);
    }
}
