<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `BusinessHomeProps` for both transports. It shapes already authorized facts and adds real routes
 * only: a destination with no live route yet (withdraw, notifications, rating, reports, profile)
 * is null, and headroom, on-time share and unread notifications are not invented.
 */
class BusinessHomeResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->resource;
        $entry = $data['entry'];
        $raises = $data['raises'];
        $business = (string) $entry['business_id'];
        $profile = $entry['profile'];
        $campaign = fn (string $id): array => self::link($request, 'business.campaigns.show', ['business' => $business, 'campaign' => $id]);
        $notes = array_map(fn (array $note): array => ['id' => $note['campaign_id'], 'title' => $note['title'], 'status' => $note['status'],
            'created_at' => $note['created_at'], 'funded_pct' => $note['funded_pct'], 'investors' => $note['investors'], 'raised' => $note['raised'],
            'target' => $note['target'], 'link' => $campaign($note['campaign_id'])], $raises['notes']);
        $live = array_find($notes, fn (array $note): bool => $note['status'] === 'active');
        $allowed = $request->routeIs('api.*') && ! $request->user()?->tokenCan('business:command') ? [] : $entry['allowed_actions'];
        $draft = ($entry['application']['status'] ?? null) === 'draft' ? $entry['application']['id'] : null;
        $placeholder = '00000000-0000-0000-0000-000000000000';
        $wallet = fn (array $query = []): array => self::link($request, 'business.wallet.show', ['business' => $business, ...$query]);

        return ['business' => ['name' => $profile['name'], 'company_code' => $profile['company_code'], 'industry' => $profile['industry'], 'district' => $profile['district']],
            'rating' => $raises['rating'], 'wallet' => ['available' => $data['wallet']['available']], 'unread_notifications' => 0,
            'live_raise' => $live === null ? null : ['title' => $live['title'], 'funded_pct' => $live['funded_pct'], 'investors' => $live['investors'],
                'raised' => $live['raised'], 'target' => $live['target'], 'link' => $live['link']],
            'today' => array_map(fn (array $released): array => ['kind' => 'application_approved', 'title' => $released['title'], 'fee' => ['currency' => 'RWF', 'amount' => '0'],
                'link' => self::link($request, 'business.applications.publish.show', ['business' => $business, 'application' => $released['application_id']])], $raises['released']),
            'capital' => [...$raises['capital'], 'repaid' => $data['wallet']['repaid'], 'on_time_pct' => null],
            'notes' => $notes, 'headroom' => null,
            'links' => ['home' => self::link($request, 'business.show', ['business' => $business]),
                'launcher' => ['url' => route($request->routeIs('api.*') ? 'api.v1.identity.show' : 'dashboard', [], false), 'method' => 'get'],
                'reports' => null, 'profile' => null, 'wallet' => $wallet(), 'deposit' => $wallet(['kind' => 'deposit']), 'withdraw' => null,
                'notifications' => null, 'rating' => null,
                'apply' => $draft === null ? null : self::link($request, 'business.applications.show', ['business' => $business, 'application' => $draft])],
            'create_application' => $draft !== null || ! in_array('application.create', $allowed, true) ? null : [
                'action' => ['url' => route(self::prefix($request).'business.applications.create', ['business' => $business], false), 'method' => 'post'],
                'operation' => ['url' => str_replace($placeholder, '{request_id}', route(self::prefix($request).'business.applications.operations.show', ['request_id' => $placeholder], false)), 'method' => 'get'],
                'identity_context_revision' => $data['identity_context_revision'], 'expected_revision' => 0]];
    }

    /**
     * @param  array<string, string>  $parameters
     * @return array{url: string, method: 'get'}
     */
    private static function link(Request $request, string $name, array $parameters = []): array
    {
        return ['url' => route(self::prefix($request).$name, $parameters, false), 'method' => 'get'];
    }

    private static function prefix(Request $request): string
    {
        return $request->routeIs('api.*') ? 'api.v1.' : '';
    }
}
