<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessApplicationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $page */
        $page = $this->resource;
        $review = $page['review'];
        $record = $review['application'];
        $steps = ['business' => 0, 'raise' => 1, 'review' => 2, 'submitted' => 3];
        $view = $request->query('view_step');
        $step = is_string($view) && isset($steps[$view]) && $steps[$view] <= $steps[$record['step']] ? $view : $record['step'];
        $parameters = ['business' => $record['business_id'], 'application' => $record['id']];
        $home = self::link($request->routeIs('api.*') ? 'api.v1.identity.show' : 'business.home');
        $back = $step === 'business' || $step === 'submitted' ? $home
            : self::link(self::prefix($request).'show', [...$parameters, 'view_step' => $step === 'raise' ? 'business' : 'raise']);
        $placeholder = '00000000-0000-0000-0000-000000000000';
        $lookup = self::link(self::prefix($request).'operations.show', ['request_id' => $placeholder]);
        $lookup['url'] = str_replace($placeholder, '{request_id}', $lookup['url']);
        $actions = [];
        foreach (['save', 'evaluate', 'submit'] as $command) {
            $actions[$command] = ['url' => route(self::prefix($request).$command, $parameters, false), 'method' => 'post'];
        }

        return ['contract_version' => 'business-application-v1', 'business_id' => $page['business_id'],
            'identity_context_revision' => $page['identity_context_revision'], 'server_time' => now()->toIso8601String(),
            'allowed_actions' => $request->routeIs('api.*') && ! $request->user()?->tokenCan('business:command') ? [] : $page['allowed_actions'],
            'step' => $step, 'application' => self::draft($record), 'evidence' => $page['evidence'],
            'quote' => $review['quote'], 'acceptance' => $review['acceptance'], 'submission' => $review['submission'], 'home' => null,
            'shell_links' => ['home' => $home, 'launcher' => self::link($request->routeIs('api.*') ? 'api.v1.identity.show' : 'dashboard'), 'reports' => null, 'profile' => null],
            'links' => ['close' => $home, 'back' => $back, 'operation' => $lookup], 'actions' => $actions];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function commandData(Request $request, array $data): array
    {
        $record = $data['application'];
        $result = ['application' => self::draft($record), 'next' => self::link(self::prefix($request).'show',
            ['business' => $record['business_id'], 'application' => $record['id']])];
        if (array_key_exists('acceptance', $data)) {
            $result = [...$result, 'quote' => $data['quote'], 'acceptance' => $data['acceptance'], 'submission' => $data['submission']];
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    private static function draft(array $record): array
    {
        $fields = $record['draft'];

        return ['id' => $record['id'], 'revision' => $record['revision'], 'title' => $fields['title'],
            'target' => $fields['target'] === null ? null : ['currency' => 'RWF', 'amount' => $fields['target']],
            'term_months' => $fields['term_months'], 'use_of_funds' => $fields['use_of_funds'], 'story' => $fields['story']];
    }

    public static function prefix(Request $request): string
    {
        return $request->routeIs('api.*') ? 'api.v1.business.applications.' : 'business.applications.';
    }

    /**
     * @param  array<string, string|int>  $parameters
     * @return array{url: string, method: string}
     */
    private static function link(string $route, array $parameters = []): array
    {
        return ['url' => route($route, $parameters, false), 'method' => 'get'];
    }
}
