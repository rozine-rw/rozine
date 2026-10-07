<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Environment\ManageStagingMailTesters;
use App\Application\Identity\GetStaffAccess;
use App\Http\Resources\StaffAccessResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StaffHomeController extends Controller
{
    public function __invoke(Request $request, GetStaffAccess $action, ManageStagingMailTesters $testers): Response
    {
        $access = $action->handle((int) $request->user()?->getAuthIdentifier(), true);
        // The staging mail testers page exists only on staging, for whoever may manage it.
        $stagingMailTesters = $testers->available() && in_array('staging.mail.testers.manage', $access['allowed_actions'], true)
            ? ['url' => route('staff.staging-mail-testers.index', [], false), 'method' => 'get'] : null;

        return Inertia::render('identity/staff-home', [
            'staff_access' => (new StaffAccessResource($access))->resolve($request),
            'staging_mail_testers' => $stagingMailTesters,
        ]);
    }
}
