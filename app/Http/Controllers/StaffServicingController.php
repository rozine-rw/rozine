<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Operations\BrowseStaffServicing;
use App\Http\Requests\Staff\ListServicingRequest;
use Illuminate\Http\JsonResponse;

class StaffServicingController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(ListServicingRequest $request, BrowseStaffServicing $servicing): JsonResponse
    {
        return response()->json(['data' => ['contract_version' => 'staff-servicing-read-v1', 'server_time' => now()->toIso8601String(),
            'allowed_actions' => [], ...$servicing->handle((int) $request->user()?->getAuthIdentifier())]]);
    }
}
