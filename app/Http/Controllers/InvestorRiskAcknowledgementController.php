<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Primary\AcknowledgeInvestorRisks;
use App\Http\Requests\Investor\RiskAcknowledgementRequest;
use App\Http\Resources\OperationResource;
use Illuminate\Http\JsonResponse;

/** The KYC-R05 risk statements and their acknowledgement, for the web session and API v1 alike. */
class InvestorRiskAcknowledgementController extends Controller
{
    public function __construct(private AcknowledgeInvestorRisks $risks) {}

    public function show(RiskAcknowledgementRequest $request): JsonResponse
    {
        $context = $request->validated('identity_context_revision');

        return response()->json(['data' => $this->risks->read($this->user($request), $context === null ? null : (int) $context)]);
    }

    public function store(RiskAcknowledgementRequest $request): OperationResource
    {
        $result = $this->risks->handle($this->user($request), $request->integer('identity_context_revision'), (string) $request->validated('version'),
            (string) $request->validated('statements_sha256'), (string) $request->validated('request_id'));

        return new OperationResource([...$result, 'data' => ['receipt' => $result['data'] === [] ? null : $result['data'], 'current' => null, 'next' => null],
            'allowed_actions' => []]);
    }

    private function user(RiskAcknowledgementRequest $request): int
    {
        return (int) $request->user()?->getAuthIdentifier();
    }
}
