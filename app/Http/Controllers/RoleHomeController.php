<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\AuthorizeActiveRole;
use App\Http\Resources\IdentityContextResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoleHomeController extends Controller
{
    public function __invoke(Request $request, AuthorizeActiveRole $action): Response
    {
        $role = explode('.', (string) $request->route()?->getName())[0];

        return Inertia::render('identity/role-home', [
            'identity' => (new IdentityContextResource($action->context((int) $request->user()?->getAuthIdentifier(), $role)))->resolve($request),
            'role' => $role,
            'section' => $request->query('section') === 'access' ? 'access' : 'overview',
        ]);
    }
}
