<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\GetStaffAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The staff entry (`admin.home`). There is no page between the launcher and the Admin console: an
 * account with staff access goes straight to the console's Dashboard, whose sidebar carries every
 * section the account may open. Anyone else still gets STAFF_ACCESS_REQUIRED.
 */
class StaffHomeController extends Controller
{
    public function __invoke(Request $request, GetStaffAccess $action): RedirectResponse
    {
        $action->handle((int) $request->user()?->getAuthIdentifier(), true);

        return redirect()->route('staff.dashboard');
    }
}
