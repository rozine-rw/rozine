<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\GetStaffAccess;
use App\Application\Operations\BrowseActivityTrail;
use App\Http\Requests\Staff\ListActivityTrailRequest;
use App\Http\Resources\StaffActivityResource;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The designed Activity & Audit trail (`investors.verify`): the identity audit log and the command
 * journal, newest first, searched and filtered by date on the server. Nothing can be edited here.
 */
class StaffActivityController extends Controller
{
    /** The design shows the newest fifty and opens the rest on request. */
    private const LIMIT = 50;

    public function index(ListActivityTrailRequest $request, BrowseActivityTrail $trail, GetStaffAccess $access): Response|StaffActivityResource
    {
        $actorId = (int) $request->user()?->getAuthIdentifier();
        $search = trim((string) $request->validated('q', ''));
        /** @var 'today'|'7d'|'30d'|null $preset */
        $preset = $request->validated('preset');
        $from = $preset === null ? $request->validated('from') : null;
        $to = $preset === null ? $request->validated('to') : null;
        $limit = (int) $request->validated('limit', self::LIMIT);
        $cursorMode = ! $request->routeIs('api.*') && ! $request->has('limit');
        $cursor = $cursorMode ? $request->validated('cursor') : null;
        $entries = $trail->handle($actorId, $search, $preset, $from === null ? null : (string) $from, $to === null ? null : (string) $to, $limit, $cursor, $cursorMode);
        $staff = $access->handle($actorId);

        $resource = new StaffActivityResource([...$entries, 'search' => $search, 'preset' => $preset, 'from' => $from === null ? null : (string) $from,
            'to' => $to === null ? null : (string) $to, 'limit' => $limit, 'roles' => $staff['roles'], 'permissions' => $staff['allowed_actions'], 'cursor_mode' => $cursorMode]);

        if ($request->routeIs('api.*')) {
            return $resource;
        }

        $props = $resource->resolve($request);
        if ($cursorMode && $cursor !== null) {
            $props['events'] = Inertia::merge($props['events'])->matchOn('cursor_key');
        }

        return Inertia::render('admin/events', $props);
    }
}
