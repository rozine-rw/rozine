<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessPublicationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $page */
        $page = $this->resource;
        $application = $page['application'];
        $prefix = $request->routeIs('api.*') ? 'api.v1.' : '';
        $parameters = ['business' => $application['business_id'], 'application' => $application['id']];
        $cause = $page['cause'];
        $home = ['url' => route($request->routeIs('api.*') ? 'api.v1.business.index' : 'business.home', [], false), 'method' => 'get'];
        $listing = $page['listing'];
        if ($listing !== null) {
            $listing = ['receipt' => [...$listing['receipt'], 'link' => self::lookup($request, $page['identity_context_revision'], $listing['receipt']['request_id'])],
                'campaign' => ['url' => route($prefix.'business.campaigns.show', ['business' => $application['business_id'], 'campaign' => $listing['id']], false), 'method' => 'get']];
        }
        $allowed = $cause === null && $page['release'] !== null && $listing === null && $page['can_publish']
            && (! $request->routeIs('api.*') || $request->user()?->tokenCan('business:command'));

        return ['contract_version' => 'business-campaign-v1', 'identity_context_revision' => $page['identity_context_revision'], 'server_time' => now()->toIso8601String(),
            'application' => ['id' => $application['id'], 'title' => $application['title'], 'revision' => $application['revision'],
                'target' => $page['review']['quote']['principal'] ?? ['currency' => 'RWF', 'amount' => $application['draft']['target']]],
            'release' => ['state' => $page['release'] !== null ? 'released' : ($cause === null ? 'awaiting_staff_review' : 'refused'), 'causes' => $cause === null ? [] : [$cause]],
            'prerequisites' => [['key' => 'staff_release', 'met' => $page['release'] !== null],
                ['key' => 'signatures_retained', 'met' => $page['prerequisites']['signatures_retained']], ['key' => 'quote_current', 'met' => $page['prerequisites']['quote_current']], ['key' => 'terms_current', 'met' => $page['prerequisites']['terms_current']]],
            'listing_fee' => ['currency' => 'RWF', 'amount' => '0'], 'fee_disclosure' => $page['fee_disclosure'], 'listing' => $listing,
            'allowed_actions' => $allowed ? ['application.publish'] : [],
            'actions' => ['publish' => ['url' => route($prefix.'business.applications.publish', $parameters, false), 'method' => 'post']],
            'links' => ['close' => $home, 'operation' => self::lookup($request, $page['identity_context_revision']),
                'review' => $page['prerequisites']['quote_current'] && $page['prerequisites']['terms_current']
                    ? null : ['url' => route($prefix.'business.applications.show', $parameters, false), 'method' => 'get']],
            'home' => null, 'shell_links' => ['home' => $home, 'launcher' => ['url' => route('dashboard', [], false), 'method' => 'get'], 'reports' => null, 'profile' => null]];
    }

    /** @return array{url: string, method: string} */
    public static function lookup(Request $request, int $contextRevision, ?string $requestId = null, string $command = 'application.publish'): array
    {
        $placeholder = '00000000-0000-0000-0000-000000000000';
        $url = route(($request->routeIs('api.*') ? 'api.v1.' : '').'business.applications.operations.show',
            ['request_id' => $requestId ?? $placeholder, 'command' => $command, 'identity_context_revision' => $contextRevision], false);

        return ['url' => $requestId === null ? str_replace($placeholder, '{request_id}', $url) : $url, 'method' => 'get'];
    }
}
