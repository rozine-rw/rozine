<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\GetIdentityContext;
use App\Application\Identity\GetStaffAccess;
use App\Http\Resources\IdentityContextResource;
use App\Http\Resources\StaffAccessResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, GetIdentityContext $identity, GetStaffAccess $staff): Response
    {
        return Inertia::render('dashboard', [
            'staff_access' => (new StaffAccessResource($staff->handle((int) $request->user()?->getAuthIdentifier())))->resolve($request),
            'identity' => (new IdentityContextResource(
                $identity->handle((int) $request->user()?->getAuthIdentifier()),
            ))->resolve($request),
        ]);
    }
}
