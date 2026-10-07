<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The live Admin console sections for a viewer's staff permissions, keyed as the console frame
 * names them. The staff home and every console page build their navigation here, so a section is
 * linked wherever the viewer holds the permission its queue checks and nowhere else; the server
 * still authorizes each visit.
 */
class StaffNavigationResource extends JsonResource
{
    /** @var array<string, array{permission: string, route: string}> */
    private const SECTIONS = [
        'investors' => ['permission' => 'investors.verify', 'route' => 'staff.investor-verifications.index'],
        'applications' => ['permission' => 'applications.review', 'route' => 'staff.applications.index'],
        'disbursements' => ['permission' => 'disbursements.view', 'route' => 'staff.disbursements.index'],
    ];

    /** @return array<string, array{url: string, method: string}|null> */
    public function toArray(Request $request): array
    {
        /** @var list<string> $permissions */
        $permissions = $this->resource;
        $prefix = $request->routeIs('api.*') ? 'api.v1.' : '';
        $links = ['launcher' => ['url' => route('dashboard', [], false), 'method' => 'get'], 'today' => null];
        foreach (self::SECTIONS as $section => $source) {
            $links[$section] = in_array($source['permission'], $permissions, true)
                ? ['url' => route($prefix.$source['route'], [], false), 'method' => 'get'] : null;
        }

        return [...$links, 'repayments' => null, 'businesses' => null, 'auditors' => null, 'staff' => null, 'ledger' => null, 'events' => null];
    }
}
