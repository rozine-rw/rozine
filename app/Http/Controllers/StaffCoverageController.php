<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Auditor\BrowseAuditCoverage;
use App\Application\Identity\GetStaffAccess;
use App\Http\Requests\Staff\ListCoverageRequest;
use App\Http\Resources\StaffCoverageResource;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Partner coverage (`audit.partners.verify`): every district a Business operates in, with the
 * partners dispatch could reach there and the audits still open, searched. Nothing here
 * dispatches, reassigns or suspends.
 */
class StaffCoverageController extends Controller
{
    public function __invoke(ListCoverageRequest $request, BrowseAuditCoverage $coverage, GetStaffAccess $access): Response|StaffCoverageResource
    {
        $actorId = (int) $request->user()?->getAuthIdentifier();
        $districts = $coverage->handle($actorId);
        $staff = $access->handle($actorId);

        $resource = new StaffCoverageResource(['districts' => $districts, 'search' => trim((string) $request->validated('q', '')),
            'roles' => $staff['roles'], 'permissions' => $staff['allowed_actions']]);

        return $request->routeIs('api.*') ? $resource : Inertia::render('admin/coverage', $resource->resolve($request));
    }
}
