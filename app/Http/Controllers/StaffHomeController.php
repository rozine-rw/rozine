<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\GetStaffAccess;
use App\Http\Resources\StaffAccessResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StaffHomeController extends Controller
{
    public function __invoke(Request $request, GetStaffAccess $action): Response
    {
        return Inertia::render('identity/staff-home', [
            'staff_access' => (new StaffAccessResource($action->handle((int) $request->user()?->getAuthIdentifier(), true)))->resolve($request),
        ]);
    }
}
