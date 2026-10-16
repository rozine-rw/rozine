<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Operations\ReadChanges;
use App\Application\Primary\GetInvestorCommitment;
use App\Application\Primary\ManagePrimaryCheckout;
use App\Http\Requests\Investor\PrimaryCommandRequest;
use App\Http\Requests\Investor\ShowCommitmentRequest;
use App\Http\Requests\Investor\ShowPrimaryOperationRequest;
use App\Http\Resources\InvestorCommitmentResource;
use App\Http\Resources\OperationResource;
use Closure;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * The Investor purchase transport (C3 v2 §2c, AC-04) for the web session and API v1 alike.
 * Each answer is the recorded operation, unchanged: `receipt` holds the facts it recorded and
 * `current` is null. On the web a completed reserve or confirm names `next` (the held checkout,
 * or the commitment); otherwise `next` is null and the page reloads its own authorized facts.
 */
class InvestorPrimaryController extends Controller
{
    public function __construct(private ManagePrimaryCheckout $checkout, private ReadChanges $changes) {}

    public function reserve(PrimaryCommandRequest $request, string $campaign): OperationResource
    {
        $result = $this->checkout->reserve($this->user($request), $request->integer('identity_context_revision'), $campaign,
            (string) $request->validated('units'), $request->integer('expected_campaign_revision'), $request->integer('quote_revision'),
            (string) $request->validated('request_id'));

        return $this->present($result, $this->next($request, $result, 'RESERVATION_HELD', 'investor.checkout',
            fn (array $receipt): array => ['campaign' => $campaign, 'reservation' => (string) $receipt['reservation_id']]));
    }

    public function confirm(PrimaryCommandRequest $request, string $reservation): OperationResource
    {
        $result = $this->checkout->confirm($this->user($request), $request->integer('identity_context_revision'), $reservation,
            $request->integer('expected_reservation_revision'), (string) $request->validated('disclosure_version'),
            (string) $request->validated('disclosure_sha256'), (string) $request->validated('request_id'));

        return $this->present($result, $this->next($request, $result, 'RESERVATION_CONFIRMED', 'investor.commitments.show',
            fn (array $receipt): array => ['commitment' => (string) $receipt['commitment_id']]));
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
        $cursor = $this->changes->cursor($this->user($request));
        if ($request->routeIs('api.*')) {
            return new InvestorCommitmentResource([...$action->handle($this->user($request), $context, $commitment), 'refusal' => null, 'changes_cursor' => $cursor]);
        }
        $page = $action->page($this->user($request), $context, $commitment);

        return Inertia::render('investor/commitment', (new InvestorCommitmentResource([...$page, 'changes_cursor' => $cursor]))->resolve($request))->toResponse($request)
            ->setStatusCode($page['refusal']['status'] ?? 200);
    }

    private function user(PrimaryCommandRequest|ShowPrimaryOperationRequest|ShowCommitmentRequest $request): int
    {
        return (int) $request->user()?->getAuthIdentifier();
    }

    /**
     * Where the web checkout goes once a command completes: held units open on the checkout, and
     * a confirmed purchase on its commitment page. The API, a refusal and any other outcome leave
     * `next` null, so the page reloads its own facts.
     *
     * @param  array<string, mixed>  $result
     * @param  Closure(array<string, mixed>): array<string, string>  $parameters
     * @return array{url: string, method: 'get'}|null
     */
    private function next(PrimaryCommandRequest $request, array $result, string $code, string $route, Closure $parameters): ?array
    {
        if ($request->routeIs('api.*') || $result['status'] !== 'completed' || $result['code'] !== $code) {
            return null;
        }

        return ['url' => route($route, $parameters($result['data']), false), 'method' => 'get'];
    }

    /**
     * @param  array<string, mixed>  $result
     * @param  array{url: string, method: 'get'}|null  $next
     */
    private function present(array $result, ?array $next = null): OperationResource
    {
        return new OperationResource([...$result, 'data' => ['receipt' => $result['data'] === [] ? null : $result['data'], 'current' => null, 'next' => $next],
            'allowed_actions' => []]);
    }
}
