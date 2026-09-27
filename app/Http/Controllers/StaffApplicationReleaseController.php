<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Business\ManageBusinessCampaigns;
use App\Http\Requests\Business\ReleaseApplicationRequest;
use App\Http\Resources\OperationResource;
use App\Http\Resources\StaffApplicationReleaseResource;
use Illuminate\Http\Request;

class StaffApplicationReleaseController extends Controller
{
    public function __construct(private ManageBusinessCampaigns $campaigns) {}

    public function show(Request $request): StaffApplicationReleaseResource
    {
        $this->authorizeRead($request);

        return new StaffApplicationReleaseResource($this->campaigns->staffPage((int) $request->user()?->getAuthIdentifier(), (string) $request->route('application')));
    }

    public function release(ReleaseApplicationRequest $request): OperationResource
    {
        return $this->present($request, $this->campaigns->release((int) $request->user()?->getAuthIdentifier(), (string) $request->route('application'),
            (int) $request->validated('expected_revision'), (string) $request->validated('reason'), (string) $request->validated('request_id')));
    }

    public function operation(Request $request): OperationResource
    {
        $this->authorizeRead($request);

        return $this->present($request, $this->campaigns->findRelease((int) $request->user()?->getAuthIdentifier(), (string) $request->route('request_id')));
    }

    /** @param array<string, mixed> $result */
    private function present(Request $request, array $result): OperationResource
    {
        $data = $result['data'];
        $current = null;
        if (isset($data['application_id'])) {
            $current = (new StaffApplicationReleaseResource($this->campaigns->staffPage((int) $request->user()?->getAuthIdentifier(), $data['application_id'])))->resolve($request);
            $data['receipt']['link'] = StaffApplicationReleaseResource::lookup($request, $data['receipt']['request_id']);
        }

        return new OperationResource([...$result, 'data' => ['receipt' => $data['receipt'] ?? null, 'current' => $current, 'next' => null],
            'allowed_actions' => $current['release']['allowed_actions'] ?? []]);
    }

    private function authorizeRead(Request $request): void
    {
        abort_if($request->routeIs('api.*') && ! $request->user()?->tokenCan('staff:applications:read'), 403);
    }
}
