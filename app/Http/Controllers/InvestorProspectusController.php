<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Business\GetInvestorProspectus;
use App\Http\Resources\InvestorProspectusResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** The signed prospectus behind a deal (`investor.deals.prospectus`), on both transports (BRS v3.0 PRO-R04). */
class InvestorProspectusController extends Controller
{
    public function __invoke(Request $request, GetInvestorProspectus $action, string $campaign): Response|InvestorProspectusResource
    {
        $resource = new InvestorProspectusResource($action->handle((int) $request->user()?->getAuthIdentifier(), $campaign));

        return $request->routeIs('api.*') ? $resource : Inertia::render('investor/prospectus', $resource->resolve($request));
    }
}
