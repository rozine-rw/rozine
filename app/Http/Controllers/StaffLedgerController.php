<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\GetStaffAccess;
use App\Application\Operations\BrowseLedger;
use App\Http\Requests\Staff\ListLedgerRequest;
use App\Http\Resources\StaffLedgerResource;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The designed Ledger (`ledger.view`): every wallet ledger posting, newest first, searched on the
 * server and paged back, each opening its lines in the entry drawer. Nothing here can be posted or edited.
 */
class StaffLedgerController extends Controller
{
    /** The design shows the newest fifty and opens the rest on request. */
    private const LIMIT = 50;

    public function __invoke(ListLedgerRequest $request, BrowseLedger $ledger, GetStaffAccess $access): Response|StaffLedgerResource
    {
        $actorId = (int) $request->user()?->getAuthIdentifier();
        $search = trim((string) $request->validated('q', ''));
        $limit = (int) $request->validated('limit', self::LIMIT);
        $entry = $request->validated('entry');
        $before = $request->validated('before');
        $before = $before === null ? null : (string) $before;
        $page = $ledger->handle($actorId, $search === '' ? null : $search, $limit, $entry === null ? null : (string) $entry, $before);
        $staff = $access->handle($actorId);

        $resource = new StaffLedgerResource([...$page, 'search' => $search, 'limit' => $limit, 'before' => $before, 'roles' => $staff['roles'],
            'permissions' => $staff['allowed_actions']]);

        return $request->routeIs('api.*') ? $resource : Inertia::render('admin/ledger', $resource->resolve($request));
    }
}
