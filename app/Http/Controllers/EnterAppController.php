<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\EnterSoleApp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Where sign-in and email verification land. An account with one app opens it straight away,
 * where it left off; anyone else, and anyone who opens the launcher on purpose, sees the launcher.
 */
class EnterAppController extends Controller
{
    public function __invoke(Request $request, EnterSoleApp $enter): RedirectResponse
    {
        return match ($app = $enter->handle((int) $request->user()?->getAuthIdentifier())) {
            null => redirect()->route('dashboard', $request->query()),
            'admin' => redirect()->route('staff.dashboard'),
            default => redirect()->route('identity.roles.resume', ['role' => $app]),
        };
    }
}
