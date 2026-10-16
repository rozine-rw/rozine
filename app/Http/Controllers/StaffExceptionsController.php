<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\GetStaffAccess;
use App\Application\Operations\BrowseExceptions;
use App\Http\Requests\Staff\ListExceptionsRequest;
use App\Http\Resources\StaffExceptionsResource;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The designed Exceptions page (`exceptions.view`): every open variance and halt, aged and
 * unowned, filtered by kind and searched, each opening the record it sits on where the viewer may
 * open it. Nothing here can be assigned or resolved.
 */
class StaffExceptionsController extends Controller
{
    /** The design shows the oldest fifty and opens the rest on request. */
    private const LIMIT = 50;

    public function __invoke(ListExceptionsRequest $request, BrowseExceptions $exceptions, GetStaffAccess $access): Response|StaffExceptionsResource
    {
        $actorId = (int) $request->user()?->getAuthIdentifier();
        $page = $exceptions->handle($actorId);
        $staff = $access->handle($actorId);
        $kind = $request->validated('kind');

        $resource = new StaffExceptionsResource([...$page, 'search' => trim((string) $request->validated('q', '')),
            'kind' => $kind === null ? null : (string) $kind, 'limit' => (int) $request->validated('limit', self::LIMIT),
            'roles' => $staff['roles'], 'permissions' => $staff['allowed_actions']]);

        return $request->routeIs('api.*') ? $resource : Inertia::render('admin/exceptions', $resource->resolve($request));
    }
}
