<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;

/**
 * The live Admin console sections, keyed as the console frame names them. The staff home and every
 * console page build their navigation here, so a section is linked wherever the viewer holds the
 * permission its queue checks and nowhere else; the server still authorizes each visit.
 */
final class StaffNavigation
{
    /** @var array<string, array{permission: string, route: string}> */
    private const SECTIONS = [
        'investors' => ['permission' => 'investors.verify', 'route' => 'staff.investor-verifications.index'],
        'applications' => ['permission' => 'applications.review', 'route' => 'staff.applications.index'],
        'disbursements' => ['permission' => 'disbursements.view', 'route' => 'staff.disbursements.index'],
    ];

    /**
     * @param  list<string>  $permissions
     * @return array<string, array{url: string, method: string}|null>
     */
    public static function links(Request $request, array $permissions): array
    {
        $prefix = $request->routeIs('api.*') ? 'api.v1.' : '';
        $links = ['launcher' => ['url' => route('dashboard', [], false), 'method' => 'get'], 'today' => null];
        foreach (self::SECTIONS as $section => $source) {
            $links[$section] = in_array($source['permission'], $permissions, true)
                ? ['url' => route($prefix.$source['route'], [], false), 'method' => 'get'] : null;
        }

        return [...$links, 'repayments' => null, 'businesses' => null, 'auditors' => null, 'staff' => null, 'ledger' => null, 'events' => null];
    }
}
