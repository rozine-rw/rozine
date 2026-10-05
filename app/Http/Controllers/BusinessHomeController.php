<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Business\GetBusinessHome;
use App\Application\Identity\AuthorizeActiveRole;
use App\Http\Requests\Business\ShowBusinessHomeRequest;
use App\Http\Resources\BusinessHomeResource;
use Inertia\Inertia;
use Inertia\Response;

class BusinessHomeController extends Controller
{
    public function show(ShowBusinessHomeRequest $request, AuthorizeActiveRole $identity, GetBusinessHome $home): Response|BusinessHomeResource
    {
        $userId = (int) $request->user()?->getAuthIdentifier();
        $revision = $request->validated('identity_context_revision') ?? $identity->context($userId, 'business')['context_revision'];
        $resource = new BusinessHomeResource($home->handle($userId, (int) $revision, (string) $request->route('business')));

        return $request->routeIs('api.*') ? $resource : Inertia::render('business/home', $resource->resolve($request));
    }
}
