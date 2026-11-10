<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Business\ProspectusGates;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The Listing Builder page: the prospectus inputs, the facts the Business cannot edit with the 3%
 * platform fee worked out, every gate with its fix, and the save and sign actions the actor may use.
 *
 * @phpstan-import-type Prospectus from \App\Application\Business\Contracts\BusinessProspectuses
 */
class BusinessProspectusResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Prospectus $page */
        $page = $this->resource;
        $route = ['business' => $page['business_id'], 'application' => $page['application_id']];
        $home = ['url' => route('business.show', ['business' => $page['business_id']], false), 'method' => 'get'];
        $facts = $page['facts'];
        $fee = $facts === null ? null : (string) intdiv((int) $facts['principal'] * 3, 100);

        return ['contract_version' => 'business-prospectus-v1', 'server_time' => now()->toIso8601String(), 'sections' => ProspectusGates::SECTIONS,
            'shell_links' => ['home' => $home, 'launcher' => ['url' => route('dashboard', [], false), 'method' => 'get'], ...BusinessHomeResource::tabs($request, $page['business_id'])],
            'application' => ['id' => $page['application_id'], 'title' => $page['title']], 'version' => $page['version'], 'inputs' => $page['inputs'],
            'signed' => $page['signed'], 'unavailable' => $page['unavailable'], 'evaluation' => $page['evaluation'],
            'facts' => $facts === null ? null : [...$facts, 'platform_fee' => $fee, 'net_of_fee' => (string) ((int) $facts['principal'] - (int) $fee)],
            'actions' => ['save' => $page['can_edit'] ? ['url' => route('business.applications.prospectus.save', $route, false), 'method' => 'put'] : null,
                'sign' => $page['can_sign'] ? ['url' => route('business.applications.prospectus.sign', $route, false), 'method' => 'post'] : null],
            'links' => ['close' => $home]];
    }
}
