<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Auditor\VerifyAuditReportSeal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditSealVerificationController extends Controller
{
    public function __invoke(Request $request, VerifyAuditReportSeal $action): JsonResponse|Response
    {
        $seal = $action->handle((string) $request->route('report'));

        return $request->routeIs('api.*') ? response()->json(['data' => $seal]) : Inertia::render('audit/verify-seal', ['seal' => $seal]);
    }
}
