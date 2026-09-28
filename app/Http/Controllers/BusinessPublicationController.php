<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Business\ManageBusinessCampaigns;
use App\Application\Identity\AuthorizeActiveRole;
use App\Http\Requests\Business\CancelCampaignRequest;
use App\Http\Requests\Business\PublishApplicationRequest;
use App\Http\Requests\Business\ShowApplicationRequest;
use App\Http\Resources\BusinessCampaignResource;
use App\Http\Resources\BusinessPublicationResource;
use App\Http\Resources\OperationResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BusinessPublicationController extends Controller
{
    public function __construct(private ManageBusinessCampaigns $campaigns, private AuthorizeActiveRole $identity) {}

    public function show(ShowApplicationRequest $request): Response|BusinessPublicationResource
    {
        $resource = new BusinessPublicationResource($this->campaigns->publishPage((int) $request->user()?->getAuthIdentifier(), $this->context($request),
            (string) $request->route('business'), (string) $request->route('application')));

        return $request->routeIs('api.*') ? $resource : Inertia::render('business/publish', $resource->resolve($request));
    }

    public function publish(PublishApplicationRequest $request): OperationResource
    {
        $result = $this->campaigns->publish((int) $request->user()?->getAuthIdentifier(), (int) $request->validated('identity_context_revision'),
            (string) $request->route('business'), (string) $request->route('application'), (int) $request->validated('expected_application_revision'),
            (string) $request->validated('fee_disclosure_version'), (string) $request->validated('request_id'));

        return $this->present($request, $result);
    }

    public function campaign(ShowApplicationRequest $request): Response|BusinessCampaignResource
    {
        $resource = new BusinessCampaignResource($this->campaigns->campaign((int) $request->user()?->getAuthIdentifier(), $this->context($request),
            (string) $request->route('business'), (string) $request->route('campaign')));

        return $request->routeIs('api.*') ? $resource : Inertia::render('business/campaign', $resource->resolve($request));
    }

    public function cancel(CancelCampaignRequest $request): OperationResource
    {
        $result = $this->campaigns->cancel((int) $request->user()?->getAuthIdentifier(), (int) $request->validated('identity_context_revision'),
            (string) $request->route('business'), (string) $request->route('campaign'), (int) $request->validated('expected_campaign_revision'),
            $request->validated('reason'), (string) $request->validated('request_id'));

        return $this->presentCancellation($request, $result);
    }

    /** @param array<string, mixed> $result */
    public function presentCancellation(Request $request, array $result): OperationResource
    {
        $data = $result['data'];
        $current = null;
        if (isset($data['campaign_id'], $data['business_id'])) {
            $current = (new BusinessCampaignResource($this->campaigns->campaign((int) $request->user()?->getAuthIdentifier(), $request->integer('identity_context_revision'),
                $data['business_id'], $data['campaign_id'])))->resolve($request);
        }
        if (isset($data['receipt'])) {
            $data['receipt']['link'] = BusinessPublicationResource::lookup($request, $request->integer('identity_context_revision'), $data['receipt']['request_id'], 'campaign.cancel');
        }

        return new OperationResource([...$result, 'data' => ['receipt' => $data['receipt'] ?? null, 'current' => $current, 'next' => null],
            'allowed_actions' => $current['allowed_actions'] ?? []]);
    }

    /** @param array<string, mixed> $result */
    public function present(Request $request, array $result): OperationResource
    {
        $data = $result['data'];
        $current = null;
        $next = null;
        if (isset($data['application_id'], $data['business_id'])) {
            $current = (new BusinessPublicationResource($this->campaigns->publishPage((int) $request->user()?->getAuthIdentifier(), $request->integer('identity_context_revision'),
                $data['business_id'], $data['application_id'])))->resolve($request);
            $next = $current['listing']['campaign'] ?? null;
            $data['receipt']['link'] = BusinessPublicationResource::lookup($request, $request->integer('identity_context_revision'), $data['receipt']['request_id']);
        }

        return new OperationResource([...$result, 'data' => ['receipt' => $data['receipt'] ?? null, 'current' => $current, 'next' => $next],
            'allowed_actions' => $current['allowed_actions'] ?? []]);
    }

    private function context(ShowApplicationRequest $request): int
    {
        return (int) ($request->validated('identity_context_revision')
            ?? $this->identity->context((int) $request->user()?->getAuthIdentifier(), 'business')['context_revision']);
    }
}
