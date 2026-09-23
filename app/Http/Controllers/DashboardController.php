<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\GetIdentityContext;
use App\Http\Resources\IdentityContextResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, GetIdentityContext $identity): Response
    {
        return Inertia::render('dashboard', [
            'identity' => (new IdentityContextResource(
                $identity->handle((int) $request->user()?->getAuthIdentifier()),
            ))->resolve($request),
        ]);
    }
}
