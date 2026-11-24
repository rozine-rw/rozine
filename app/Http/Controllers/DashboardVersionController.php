<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\GetAccountPulse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * A fingerprint of everything the launcher shows (Erastus, 24 Nov 2026: the dashboard updates by itself
 * when Compliance decides). The page asks every few seconds while it is open and reloads its props only
 * when the fingerprint changes, so an approval shows without a refresh.
 */
class DashboardVersionController extends Controller
{
    public function __invoke(Request $request, GetAccountPulse $pulse): JsonResponse
    {
        return response()->json(['version' => $pulse->handle((int) $request->user()?->getAuthIdentifier())])
            ->header('Cache-Control', 'no-store, private');
    }
}
