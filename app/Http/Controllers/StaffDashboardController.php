<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\GetStaffAccess;
use App\Application\Operations\BrowseOperationsDashboard;
use App\Http\Requests\Staff\ShowStaffDashboardRequest;
use App\Http\Resources\StaffDashboardResource;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The designed Operations Center (`admin.open`): every enabled staff member's console home, with
 * the platform's headline figures, the queues that need a human and capital raised over the
 * chosen window. Figures the platform does not record yet read as not tracked.
 */
class StaffDashboardController extends Controller
{
    public function __invoke(ShowStaffDashboardRequest $request, BrowseOperationsDashboard $dashboard, GetStaffAccess $access): Response|StaffDashboardResource
    {
        $actorId = (int) $request->user()?->getAuthIdentifier();
        $from = $request->validated('from');
        $to = $request->validated('to');
        $board = $dashboard->handle($actorId, $from === null ? null : (string) $from, $to === null ? null : (string) $to);
        $staff = $access->handle($actorId);

        $resource = new StaffDashboardResource(['dashboard' => $board, 'roles' => $staff['roles'], 'permissions' => $staff['allowed_actions']]);

        return $request->routeIs('api.*') ? $resource : Inertia::render('admin/today', $resource->resolve($request));
    }
}
