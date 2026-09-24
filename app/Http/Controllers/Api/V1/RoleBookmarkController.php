<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Application\Identity\GetRoleBookmark;
use App\Application\Identity\SaveRoleBookmark;
use App\Http\Controllers\Controller;
use App\Http\Requests\Identity\SaveRoleBookmarkRequest;
use App\Http\Resources\RoleBookmarkResource;
use Illuminate\Http\Request;

class RoleBookmarkController extends Controller
{
    public function show(Request $request, string $role, GetRoleBookmark $action): RoleBookmarkResource
    {
        abort_if($request->routeIs('api.*') && ! $request->user()->tokenCan('identity:access'), 403);

        return new RoleBookmarkResource($action->handle((int) $request->user()?->getAuthIdentifier(), $role));
    }

    public function store(SaveRoleBookmarkRequest $request, SaveRoleBookmark $action): RoleBookmarkResource
    {
        return new RoleBookmarkResource($action->handle(
            (int) $request->user()?->getAuthIdentifier(), (string) $request->validated('role'),
            (string) $request->validated('route'), $request->validated('parameters'), $request->validated('query'),
            (int) $request->validated('expected_revision'), (string) $request->validated('request_id'),
        ));
    }
}
