<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\GetStaffAccess;
use App\Application\Operations\BrowseNotes;
use App\Http\Requests\Staff\ListNotesRequest;
use App\Http\Resources\StaffNotesResource;
use Inertia\Inertia;
use Inertia\Response;

/** The designed Notes register (`primary.view`): every published raise as a note, by where it sits now, searched and tabbed. */
class StaffNotesController extends Controller
{
    /** The design shows a first page and opens the rest on request. */
    private const LIMIT = 50;

    public function __invoke(ListNotesRequest $request, BrowseNotes $notes, GetStaffAccess $access): Response
    {
        $actorId = (int) $request->user()?->getAuthIdentifier();
        $page = $notes->handle($actorId);
        $staff = $access->handle($actorId);

        return Inertia::render('admin/notes', (new StaffNotesResource([...$page, 'tab' => (string) $request->validated('tab', 'all'),
            'search' => trim((string) $request->validated('q', '')), 'limit' => (int) $request->validated('limit', self::LIMIT),
            'roles' => $staff['roles'], 'permissions' => $staff['allowed_actions']]))->resolve($request));
    }
}
