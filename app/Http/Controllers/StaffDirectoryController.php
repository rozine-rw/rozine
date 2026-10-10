<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\BrowseStaffDirectory;
use App\Application\Identity\GetStaffAccess;
use App\Http\Requests\Staff\ListStaffDirectoryRequest;
use App\Http\Resources\StaffDirectoryResource;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The designed Staff & Roles directory (`admin.open`): every operator with their role and account
 * state, and one operator's 360 with their access history. Read only.
 */
class StaffDirectoryController extends Controller
{
    public function index(ListStaffDirectoryRequest $request, BrowseStaffDirectory $directory, GetStaffAccess $access): Response|StaffDirectoryResource
    {
        $actorId = (int) $request->user()?->getAuthIdentifier();
        /** @var 'all'|'active'|'frozen' $chip */
        $chip = (string) $request->validated('chip', 'all');
        $search = trim((string) $request->validated('q', ''));
        $operator = $request->validated('operator');
        $staff = $access->handle($actorId);

        $resource = new StaffDirectoryResource([...$directory->directory($actorId, $chip, $search), 'chip' => $chip, 'search' => $search,
            'member' => $operator === null ? null : $directory->member($actorId, (int) $operator),
            'roles' => $staff['roles'], 'permissions' => $staff['allowed_actions']]);

        return $request->routeIs('api.*') ? $resource : Inertia::render('admin/parties', $resource->resolve($request));
    }
}
