<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Wallet\FindBusinessRepaymentOperation;
use App\Application\Wallet\PayBusinessRepayment;
use App\Http\Requests\Business\PayRepaymentRequest;
use App\Http\Requests\Business\ShowBusinessWalletOperationRequest;
use App\Http\Resources\OperationResource;
use Illuminate\Http\Request;

class BusinessRepaymentController extends Controller
{
    public function pay(PayRepaymentRequest $request, PayBusinessRepayment $action): OperationResource
    {
        return $this->present($request, $action->handle((int) $request->user()?->getAuthIdentifier(), (int) $request->validated('identity_context_revision'),
            (string) $request->route('business'), (string) $request->validated('request_id'), strtolower((string) $request->validated('note_id')),
            (string) $request->validated('option'), (int) $request->validated('expected_servicing_revision'),
            ['currency' => (string) $request->validated('quoted_total.currency'), 'amount' => (string) $request->validated('quoted_total.amount')]));
    }

    public function operation(ShowBusinessWalletOperationRequest $request, FindBusinessRepaymentOperation $action): OperationResource
    {
        return $this->present($request, $action->handle((int) $request->user()?->getAuthIdentifier(), (int) $request->validated('identity_context_revision'),
            (string) $request->route('business'), (string) $request->validated('command'), (string) $request->route('request_id')));
    }

    /**
     * The recorded REPAYMENT_RECEIVED receipt, unchanged, with its own lookup link. The repayments page
     * reloads its servicing facts, so neither a current projection nor a next step is attached.
     *
     * @param  array<string, mixed>  $result
     */
    private function present(Request $request, array $result): OperationResource
    {
        $receipt = $result['data']['receipt'] ?? null;
        if ($result['status'] === 'completed' && is_array($receipt)) {
            $placeholder = (string) $receipt['request_id'];
            $receipt = [...$receipt, 'link' => ['url' => route(($request->routeIs('api.*') ? 'api.v1.' : '').'business.repayments.operations.show',
                ['business' => (string) $request->route('business'), 'request_id' => $placeholder, 'command' => 'repayment.pay',
                    'identity_context_revision' => $request->integer('identity_context_revision')], false), 'method' => 'get']];
        }

        return new OperationResource([...$result, 'data' => ['receipt' => $receipt, 'current' => null, 'next' => null], 'allowed_actions' => []]);
    }
}
