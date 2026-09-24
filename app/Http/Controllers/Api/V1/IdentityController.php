<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Application\Identity\GetIdentityContext;
use App\Http\Controllers\Controller;
use App\Http\Resources\IdentityContextResource;
use Illuminate\Http\Request;

class IdentityController extends Controller
{
    public function __invoke(Request $request, GetIdentityContext $identity): IdentityContextResource
    {
        return new IdentityContextResource($identity->handle((int) $request->user()?->getAuthIdentifier()));
    }
}
