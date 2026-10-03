<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Primary\ManagePrimaryCheckout;
use App\Http\Requests\Investor\PrimaryCommandRequest;
use App\Http\Requests\Investor\ShowPrimaryOperationRequest;
use App\Http\Resources\OperationResource;

/**
 * The Investor purchase transport (C3 v2 §2c, AC-04) for the web session and API v1 alike.
 * Each answer is the recorded operation, unchanged: `receipt` holds the facts it recorded,
 * `current` is null and `next` is null, so the page reloads its own authorized facts afresh.
 */
class InvestorPrimaryController extends Controller
{
    public function __construct(private ManagePrimaryCheckout $checkout) {}

    public function reserve(PrimaryCommandRequest $request, string $campaign): OperationResource
    {
        return $this->present($this->checkout->reserve($this->user($request), $request->integer('identity_context_revision'), $campaign,
            (string) $request->validated('units'), $request->integer('expected_campaign_revision'), $request->integer('quote_revision'),
            (string) $request->validated('request_id')));
    }

    public function confirm(PrimaryCommandRequest $request, string $reservation): OperationResource
    {
        return $this->present($this->checkout->confirm($this->user($request), $request->integer('identity_context_revision'), $reservation,
            $request->integer('expected_reservation_revision'), (string) $request->validated('disclosure_version'),
            (string) $request->validated('disclosure_sha256'), (string) $request->validated('request_id')));
    }

    public function release(PrimaryCommandRequest $request, string $reservation): OperationResource
    {
        return $this->present($this->checkout->release($this->user($request), $request->integer('identity_context_revision'), $reservation,
            $request->integer('expected_reservation_revision'), (string) $request->validated('request_id')));
    }

    public function cancel(PrimaryCommandRequest $request, string $commitment): OperationResource
    {
        return $this->present($this->checkout->cancel($this->user($request), $request->integer('identity_context_revision'), $commitment,
            $request->integer('expected_commitment_revision'), (string) $request->validated('request_id')));
    }

    public function operation(ShowPrimaryOperationRequest $request, string $campaign, string $requestId): OperationResource
    {
        $reservation = $request->validated('reservation');

        return $this->present($this->checkout->find($this->user($request), $request->integer('identity_context_revision'),
            (string) $request->validated('command'), $campaign, is_string($reservation) ? $reservation : null, $requestId));
    }

    private function user(PrimaryCommandRequest|ShowPrimaryOperationRequest $request): int
    {
        return (int) $request->user()?->getAuthIdentifier();
    }

    /** @param  array<string, mixed>  $result */
    private function present(array $result): OperationResource
    {
        return new OperationResource([...$result, 'data' => ['receipt' => $result['data'] === [] ? null : $result['data'], 'current' => null, 'next' => null],
            'allowed_actions' => []]);
    }
}
