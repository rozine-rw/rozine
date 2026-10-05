<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Primary\GetInvestorCommitment;
use App\Application\Primary\ManagePrimaryCheckout;
use App\Http\Requests\Investor\PrimaryCommandRequest;
use App\Http\Requests\Investor\ShowCommitmentRequest;
use App\Http\Requests\Investor\ShowPrimaryOperationRequest;
use App\Http\Resources\InvestorCommitmentResource;
use App\Http\Resources\OperationResource;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

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

    /**
     * The commitment page (`investor.commitments.show`). On the web a commitment the Investor may
     * not read, or whose state is not yet sourced, renders the page's own scoped refusal.
     */
    public function commitment(ShowCommitmentRequest $request, GetInvestorCommitment $action, string $commitment): Response|InvestorCommitmentResource
    {
        $context = $request->validated('identity_context_revision');
        $context = $context === null ? null : (int) $context;
        if ($request->routeIs('api.*')) {
            return new InvestorCommitmentResource([...$action->handle($this->user($request), $context, $commitment), 'refusal' => null]);
        }
        $page = $action->page($this->user($request), $context, $commitment);

        return Inertia::render('investor/commitment', (new InvestorCommitmentResource($page))->resolve($request))->toResponse($request)
            ->setStatusCode($page['refusal']['status'] ?? 200);
    }

    private function user(PrimaryCommandRequest|ShowPrimaryOperationRequest|ShowCommitmentRequest $request): int
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
