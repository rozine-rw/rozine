<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\GetStaffAccess;
use App\Application\Operations\BrowseReconciliation;
use App\Http\Requests\Staff\ShowReconciliationRequest;
use App\Http\Resources\StaffReconciliationResource;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The designed Reconciliation page (`reconciliation.view`): yesterday's close, the provider
 * clearing accounts beside what the provider reported, and every open break, aged, linking to the
 * Investor or Business it sits on where the viewer may open them.
 */
class StaffReconciliationController extends Controller
{
    public function __invoke(ShowReconciliationRequest $request, BrowseReconciliation $reconciliation, GetStaffAccess $access): Response|StaffReconciliationResource
    {
        $actorId = (int) $request->user()?->getAuthIdentifier();
        $page = $reconciliation->handle($actorId);
        $staff = $access->handle($actorId);

        $resource = new StaffReconciliationResource(['reconciliation' => $page, 'roles' => $staff['roles'], 'permissions' => $staff['allowed_actions']]);

        return $request->routeIs('api.*') ? $resource : Inertia::render('admin/reconciliation', $resource->resolve($request));
    }
}
