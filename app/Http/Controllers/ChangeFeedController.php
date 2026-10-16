<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Operations\ReadChanges;
use App\Http\Requests\Operations\ListChangesRequest;
use App\Http\Resources\ChangeFeedResource;

class ChangeFeedController extends Controller
{
    public function index(ListChangesRequest $request, ReadChanges $action): ChangeFeedResource
    {
        $after = $request->validated('after');

        return new ChangeFeedResource($action->handle((int) $request->user()?->getAuthIdentifier(), $request->topics(), is_string($after) ? $after : null));
    }
}
