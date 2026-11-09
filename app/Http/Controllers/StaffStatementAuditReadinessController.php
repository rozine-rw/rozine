<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Evidence\GetStaffStatementAuditReadiness;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Raw first-review readiness for staff who dispatch audits (contract initial-statement-audit-1 §6):
 * revisions, hashes, counts and the open engagement only, never originals or transcribed figures.
 */
class StaffStatementAuditReadinessController extends Controller
{
    public function show(Request $request, GetStaffStatementAuditReadiness $readiness): JsonResponse
    {
        abort_if($request->routeIs('api.*') && ! ($request->user()?->tokenCan('staff:audit:read') ?? false), 403);

        return new JsonResponse(['data' => $readiness->handle((int) $request->user()?->getAuthIdentifier(), (string) $request->route('business'))]);
    }
}
