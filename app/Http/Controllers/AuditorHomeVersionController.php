<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Auditor\GetAuditorPulse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * A fingerprint of everything the Auditor home shows (Erastus, 24 Nov 2026: the home is live). The
 * page asks every few seconds while it is open and reloads its props only when the fingerprint
 * changes, so an audit dispatched in one of the partner's districts shows without a refresh.
 */
class AuditorHomeVersionController extends Controller
{
    public function __invoke(Request $request, GetAuditorPulse $pulse): JsonResponse
    {
        return response()->json(['version' => $pulse->handle((int) $request->user()?->getAuthIdentifier())])
            ->header('Cache-Control', 'no-store, private');
    }
}
