<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Http\Controllers\StaffSectionController;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The live Admin console sections for a viewer's staff permissions, keyed as the console frame
 * names them. The staff home and every console page build their navigation here. A live section
 * (a queue or a directory) is linked wherever the viewer holds the permission its page checks;
 * every other design section links to its pending page, so the sidebar always matches the design.
 * The server still authorizes each visit.
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
        'businesses' => ['permission' => 'businesses.view', 'route' => 'staff.businesses.index'],
        'auditors' => ['permission' => 'audit.partners.verify', 'route' => 'staff.auditors.index'],
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
        // the disbursement queue, and Compliance the investor identity review queue, for whoever may
        // see them. These are web pages, so they keep web URLs.
        $pendingPage = fn (string $slug): array => ['url' => route('staff.sections.show', ['section' => $slug], false), 'method' => 'get'];
        $pending = [];
        foreach (StaffSectionController::SECTIONS as $slug => $section) {
            $pending[$section] = $pendingPage($slug);
        }
        $compliance = in_array('investors.verify', $permissions, true)
            ? ['url' => route($prefix.'staff.investor-verifications.index', [], false), 'method' => 'get'] : $pendingPage('compliance');
        // The staging mail testers page exists only on staging (the `uat` isolation profile), for
        // whoever may manage the list; it sits in the console group of the sidebar.
        $mailTesters = $prefix === '' && app()->environment(['staging', 'uat']) && in_array('staging.mail.testers.manage', $permissions, true)
            ? ['url' => route('staff.staging-mail-testers.index', [], false), 'method' => 'get'] : null;

        return [...$links, ...array_diff_key($pending, $links),
            'payments' => $links['disbursements'] ?? $pendingPage('payments'), 'compliance' => $compliance,
            'repayments' => null, 'ledger' => null, 'mail_testers' => $mailTesters];
    }
}
