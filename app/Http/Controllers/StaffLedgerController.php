<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\GetStaffAccess;
use App\Application\Wallet\GetStaffLedger;
use App\Http\Requests\Staff\ShowLedgerRequest;
use App\Http\Resources\StaffLedgerResource;
use Inertia\Inertia;
use Inertia\Response;

/** The read-only staff ledger drill-down (`staff.ledger.index`) on both transports. */
class StaffLedgerController extends Controller
{
    public function index(ShowLedgerRequest $request, GetStaffLedger $action, GetStaffAccess $access): Response|StaffLedgerResource
    {
        $actorId = (int) $request->user()?->getAuthIdentifier();
        /** @var array{search?: string|null, before?: string, entry?: string} $query */
        $query = $request->safe()->only(['search', 'before', 'entry']);
        $resource = new StaffLedgerResource([...$action->page($actorId, $query), 'before' => $query['before'] ?? null,
            'roles' => $access->handle($actorId)['roles']]);

        return $request->routeIs('api.*') ? $resource : Inertia::render('admin/ledger', $resource->resolve($request));
    }
}
