<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffApplicationReleaseResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $page */
        $page = $this->resource;
        $prefix = $request->routeIs('api.*') ? 'api.v1.' : '';
        $application = $page['application'];
        $cause = $page['cause'];
        $mayRelease = $cause === null && $page['release'] === null && (! $request->routeIs('api.*') || $request->user()?->tokenCan('staff:applications:review'));
        $receipt = $page['release'];
        if ($receipt !== null) {
            $receipt['link'] = self::lookup($request, $receipt['request_id']);
        }

        return ['contract_version' => 'staff-application-release-v1', 'server_time' => now()->toIso8601String(),
            'application' => ['id' => $application['id'], 'revision' => $application['revision'], 'title' => $application['title']],
            'release' => ['revision' => $page['release'] === null ? 0 : 1,
                'state' => $page['release'] !== null ? 'released' : ($cause === null ? 'awaiting_staff_review' : 'refused'),
                'gates' => array_map(fn (string $key): array => ['key' => $key, 'state' => $page['gates'][$key] === null ? 'passed' : 'failed',
                    'cause' => $page['gates'][$key]], ['engine', 'authority', 'report']),
                'allowed_actions' => $mayRelease ? ['application.release'] : [],
                'actions' => ['release' => $mayRelease ? ['url' => route($prefix.'staff.applications.release', ['application' => $application['id']], false), 'method' => 'post'] : null],
                'receipt' => $receipt],
            'links' => ['operation' => self::lookup($request)]];
    }

    /** @return array{url: string, method: string} */
    public static function lookup(Request $request, ?string $requestId = null): array
    {
        $placeholder = '00000000-0000-0000-0000-000000000000';
        $url = route(($request->routeIs('api.*') ? 'api.v1.' : '').'staff.applications.operations.show', ['request_id' => $requestId ?? $placeholder], false);

        return ['url' => $requestId === null ? str_replace($placeholder, '{request_id}', $url) : $url, 'method' => 'get'];
    }
}
