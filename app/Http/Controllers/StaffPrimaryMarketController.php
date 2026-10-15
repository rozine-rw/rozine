<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\GetStaffAccess;
use App\Application\Operations\BrowsePrimaryMarket;
use App\Http\Requests\Staff\ListPrimaryMarketRequest;
use App\Http\Resources\StaffPrimaryMarketResource;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The designed Primary Market Monitor (`primary.view`): every raise still filling with its fill,
 * investors and days left, searched. Raises fill or fail on their own clock, so nothing here can
 * feature, pause, fill or fail one.
 */
class StaffPrimaryMarketController extends Controller
{
    /** The design shows a first page and opens the rest on request. */
    private const LIMIT = 50;

    public function __invoke(ListPrimaryMarketRequest $request, BrowsePrimaryMarket $market, GetStaffAccess $access): Response|StaffPrimaryMarketResource
    {
        $actorId = (int) $request->user()?->getAuthIdentifier();
        $page = $market->handle($actorId);
        $staff = $access->handle($actorId);

        $resource = new StaffPrimaryMarketResource([...$page, 'search' => trim((string) $request->validated('q', '')),
            'limit' => (int) $request->validated('limit', self::LIMIT), 'roles' => $staff['roles'], 'permissions' => $staff['allowed_actions']]);

        return $request->routeIs('api.*') ? $resource : Inertia::render('admin/primary-market', $resource->resolve($request));
    }
}
