<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Wallet\GetStaffLedger;
use App\Http\Requests\Staff\ShowLedgerRequest;
use App\Http\Resources\StaffLedgerResource;
use Inertia\Inertia;
use Inertia\Response;

/** The read-only staff ledger drill-down (`staff.ledger.index`) on both transports. */
class StaffLedgerController extends Controller
{
    public function index(ShowLedgerRequest $request, GetStaffLedger $action): Response|StaffLedgerResource
    {
        /** @var array{search?: string|null, before?: string, entry?: string} $query */
        $query = $request->safe()->only(['search', 'before', 'entry']);
        $resource = new StaffLedgerResource([...$action->page((int) $request->user()?->getAuthIdentifier(), $query), 'before' => $query['before'] ?? null]);

        return $request->routeIs('api.*') ? $resource : Inertia::render('admin/ledger', $resource->resolve($request));
    }
}
