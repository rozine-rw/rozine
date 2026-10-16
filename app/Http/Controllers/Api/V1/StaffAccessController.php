<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Application\Identity\GetStaffAccess;
use App\Http\Controllers\Controller;
use App\Http\Resources\StaffAccessResource;
use Illuminate\Http\Request;

class StaffAccessController extends Controller
{
    public function __invoke(Request $request, GetStaffAccess $action): StaffAccessResource
    {
        abort_unless($request->user()->tokenCan('staff:access'), 403);

        return new StaffAccessResource($action->handle((int) $request->user()?->getAuthIdentifier()));
    }
}
