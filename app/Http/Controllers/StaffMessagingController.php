<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\BrowseMessageHistory;
use App\Application\Identity\GetStaffAccess;
use App\Http\Requests\Staff\ListMessageHistoryRequest;
use App\Http\Resources\StaffMessagingResource;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The designed Messaging & Automations page (`messaging.view`). Its History tab lists every notice
 * that went out, searched on the server; automations, templates and campaigns are not editable
 * yet, so those tabs stay empty.
 */
class StaffMessagingController extends Controller
{
    /** The newest fifty receipts; older ones open on request. */
    private const LIMIT = 50;

    public function __invoke(ListMessageHistoryRequest $request, BrowseMessageHistory $history, GetStaffAccess $access): Response|StaffMessagingResource
    {
        $actorId = (int) $request->user()?->getAuthIdentifier();
        $search = trim((string) $request->validated('q', ''));
        $before = $request->validated('before');
        $page = $history->handle($actorId, $search === '' ? null : $search, $before === null ? null : (string) $before, self::LIMIT);
        $staff = $access->handle($actorId);

        $resource = new StaffMessagingResource([...$page, 'search' => $search, 'paged' => $before !== null, 'roles' => $staff['roles'],
            'permissions' => $staff['allowed_actions']]);

        return $request->routeIs('api.*') ? $resource : Inertia::render('admin/messaging', $resource->resolve($request));
    }
}
