<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Http\Controllers\StaffSectionController;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The live Admin console sections for a viewer's staff permissions, keyed as the console frame
 * names them. The staff home and every console page build their navigation here. A queue section
 * is linked wherever the viewer holds the permission its queue checks; every other design section
 * links to its pending page, so the sidebar always matches the design. The server still authorizes
 * each visit.
 */
class StaffNavigationResource extends JsonResource
{
    /**
     * The Dashboard and Staff & Roles are for every enabled staff member (`admin.open`). Activity &
     * Audit names participants and their identity decisions, so it takes the supervisory
     * `investors.verify` that already reads Investors' identity history.
     *
     * @var array<string, array{permission: string, route: string}>
     */
    private const SECTIONS = [
        'today' => ['permission' => 'admin.open', 'route' => 'staff.dashboard'],
        'staff' => ['permission' => 'admin.open', 'route' => 'staff.staff.index'],
        'events' => ['permission' => 'investors.verify', 'route' => 'staff.events.index'],
        'investors' => ['permission' => 'investors.verify', 'route' => 'staff.investors.index'],
        'applications' => ['permission' => 'applications.review', 'route' => 'staff.applications.index'],
        'disbursements' => ['permission' => 'disbursements.view', 'route' => 'staff.disbursements.index'],
    ];

    /** @return array<string, array{url: string, method: string}|null> */
    public function toArray(Request $request): array
    {
        /** @var list<string> $permissions */
        $permissions = $this->resource;
        $prefix = $request->routeIs('api.*') ? 'api.v1.' : '';
        $links = ['launcher' => ['url' => route('dashboard', [], false), 'method' => 'get']];
        foreach (self::SECTIONS as $section => $source) {
            $links[$section] = in_array($source['permission'], $permissions, true)
                ? ['url' => route($prefix.$source['route'], [], false), 'method' => 'get'] : null;
        }

        // Every other design section opens its pending page until its screen is wired. Payments is
        // the disbursement queue for whoever may see it. These are web pages, so they keep web URLs.
        $pending = [];
        foreach (StaffSectionController::SECTIONS as $slug => $section) {
            $pending[$section] = ['url' => route('staff.sections.show', ['section' => $slug], false), 'method' => 'get'];
        }

        return [...$links, ...array_diff_key($pending, $links),
            'payments' => $links['disbursements'] ?? ['url' => route('staff.sections.show', ['section' => 'payments'], false), 'method' => 'get'],
            'repayments' => null, 'ledger' => null];
    }
}
