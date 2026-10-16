<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\GetStaffAccess;
use App\Application\Operations\CheckSystemHealth;
use App\Http\Requests\Staff\CheckSystemHealthRequest;
use App\Http\Resources\StaffSystemHealthResource;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The designed System Health page (`health.view`): every monitored signal with its reading and
 * status, worst first. Nothing here changes anything.
 */
class StaffSystemHealthController extends Controller
{
    public function __invoke(CheckSystemHealthRequest $request, CheckSystemHealth $health, GetStaffAccess $access): Response|StaffSystemHealthResource
    {
        $actorId = (int) $request->user()?->getAuthIdentifier();
        $signals = $health->handle($actorId);
        $staff = $access->handle($actorId);

        $resource = new StaffSystemHealthResource(['signals' => $signals, 'search' => trim((string) $request->validated('q', '')),
            'roles' => $staff['roles'], 'permissions' => $staff['allowed_actions']]);

        return $request->routeIs('api.*') ? $resource : Inertia::render('admin/system-health', $resource->resolve($request));
    }
}
