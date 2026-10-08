<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Business\BrowseBusinessDirectory;
use App\Application\Identity\GetStaffAccess;
use App\Http\Requests\Staff\ListBusinessDirectoryRequest;
use App\Http\Resources\StaffBusinessDirectoryResource;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The designed Business directory (`businesses.view`): every Business with its notes, Investors and
 * capital, and one Business's 360 with its notes and history. Read only; no Business command is
 * offered here because none exists for staff yet.
 */
class StaffBusinessDirectoryController extends Controller
{
    /** The design's directory shows the first sixty matches and says how many more there are. */
    private const LIMIT = 60;

    public function __construct(private BrowseBusinessDirectory $directory) {}

    public function index(ListBusinessDirectoryRequest $request, GetStaffAccess $access): Response|StaffBusinessDirectoryResource
    {
        $actorId = (int) $request->user()?->getAuthIdentifier();
        /** @var 'all'|'healthy'|'watch'|'distressed'|'frozen' $chip */
        $chip = (string) $request->validated('chip', 'all');
        /** @var 'raised'|'name' $sort */
        $sort = (string) $request->validated('sort', 'raised');
        $sector = trim((string) $request->validated('sector', ''));
        $sector = $sector === 'all' ? '' : $sector;
        $search = trim((string) $request->validated('q', ''));
        $selected = $request->validated('business');
        $staff = $access->handle($actorId);

        $resource = new StaffBusinessDirectoryResource([...$this->directory->directory($actorId, $chip, $sort, $sector, $search, self::LIMIT),
            'chip' => $chip, 'sort' => $sort, 'sector' => $sector, 'search' => $search,
            'business' => $selected === null ? null : $this->directory->business($actorId, (string) $selected),
            'roles' => $staff['roles'], 'permissions' => $staff['allowed_actions']]);

        return $request->routeIs('api.*') ? $resource : Inertia::render('admin/parties', $resource->resolve($request));
    }
}
