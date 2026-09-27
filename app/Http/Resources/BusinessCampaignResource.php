<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessCampaignResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $page */
        $page = $this->resource;
        $home = ['url' => route($request->routeIs('api.*') ? 'api.v1.business.index' : 'business.home', [], false), 'method' => 'get'];

        $canCancel = $page['can_cancel'] && (! $request->routeIs('api.*') || $request->user()?->tokenCan('business:command'));

        return ['contract_version' => 'business-campaign-v1', 'identity_context_revision' => $page['identity_context_revision'], 'server_time' => now()->toIso8601String(),
            'home' => null, 'shell_links' => ['home' => $home, 'launcher' => ['url' => route('dashboard', [], false), 'method' => 'get'], 'reports' => null, 'profile' => null],
            'campaign' => ['id' => $page['id'], 'revision' => $page['revision'], 'lifecycle' => $page['lifecycle']],
            'note' => ['id' => $page['id'], 'title' => $page['title'], 'photos' => [], 'performance' => null, 'progress' => $page['progress']],
            'allowed_actions' => $canCancel ? ['campaign.cancel'] : [], 'actions' => ['cancel' => $canCancel
                ? ['url' => route(($request->routeIs('api.*') ? 'api.v1.' : '').'business.campaigns.cancel', ['business' => $page['business_id'], 'campaign' => $page['id']], false), 'method' => 'post'] : null],
            'links' => ['close' => $home, 'operation' => BusinessPublicationResource::lookup($request, $page['identity_context_revision'], command: 'campaign.cancel')]];
    }
}
