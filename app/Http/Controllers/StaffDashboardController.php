<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\GetStaffAccess;
use App\Application\Operations\BrowseLedger;
use App\Application\Operations\BrowseOperationsDashboard;
use App\Application\Operations\BrowseReconciliation;
use App\Domain\Identity\IdentityViolation;
use App\Http\Requests\Staff\ShowStaffDashboardRequest;
use App\Http\Resources\StaffDashboardResource;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The designed Operations Center (`admin.open`): every enabled staff member's console home, with
 * the platform's headline figures, the queues that need a human and capital raised over the
 * chosen window, the open reconciliation breaks and the newest ledger postings for whoever may view
 * them. Figures the platform does not record yet read as not tracked.
 */
class StaffDashboardController extends Controller
{
    public function __invoke(
        ShowStaffDashboardRequest $request, BrowseOperationsDashboard $dashboard, BrowseReconciliation $reconciliation, BrowseLedger $ledger, GetStaffAccess $access,
    ): Response|StaffDashboardResource {
        $actorId = (int) $request->user()?->getAuthIdentifier();
        $staff = $access->handle($actorId, true);
        if (! in_array('admin.open', $staff['allowed_actions'], true)) {
            throw new IdentityViolation('STAFF_PERMISSION_REQUIRED');
        }
        $from = $request->validated('from');
        $to = $request->validated('to');
        $from = $from === null ? null : (string) $from;
        $to = $to === null ? null : (string) $to;
        $page = null;
        $resolvePage = function () use ($dashboard, $reconciliation, $ledger, $staff, $actorId, $from, $to, &$page): array {
            if ($page !== null) {
                return $page;
            }

            $board = $dashboard->handle($actorId, $from, $to);
            $breaks = in_array('reconciliation.view', $staff['allowed_actions'], true) ? $reconciliation->handle($actorId)['breaks'] : null;
            $activity = in_array('ledger.view', $staff['allowed_actions'], true) ? $ledger->latest($actorId, 6) : null;

            return $page = ['dashboard' => $board, 'breaks' => $breaks, 'activity' => $activity, 'roles' => $staff['roles'],
                'permissions' => $staff['allowed_actions']];
        };

        if ($request->routeIs('api.*')) {
            return new StaffDashboardResource($resolvePage());
        }

        $resolved = null;
        $resolveResource = function () use ($request, $resolvePage, &$resolved): array {
            return $resolved ??= (new StaffDashboardResource($resolvePage()))->resolve($request);
        };
        $props = [];
        foreach (['contract_version', 'server_time', 'viewer', 'nav', 'badges', 'search', 'kpis', 'attention', 'breaks',
            'portfolio_health', 'activity', 'funnel', 'pending_applications', 'sector_exposure', 'treasury', 'collections'] as $key) {
            $props[$key] = fn () => $resolveResource()[$key];
        }
        $props['capital_raised'] = function () use ($dashboard, $actorId, $from, $to, &$resolved): array {
            return $resolved['capital_raised'] ?? StaffDashboardResource::capitalRaised($dashboard->capitalRaised($actorId, $from, $to));
        };

        return Inertia::render('admin/today', $props);
    }
}
