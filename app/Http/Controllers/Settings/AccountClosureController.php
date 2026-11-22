<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Application\Identity\CloseAccount;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\CloseAccountRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Close account (soft delete), from the Close account section of the profile settings, which lists
 * what still blocks it. Closing needs the password and DELETE typed out, and signs the person out.
 */
class AccountClosureController extends Controller
{
    public function store(CloseAccountRequest $request, CloseAccount $closure): RedirectResponse
    {
        $blockers = $closure->handle((int) $request->user()?->getAuthIdentifier(), $request->validated('reason'));
        if ($blockers !== []) {
            throw ValidationException::withMessages(['confirmation' => [__('Your account still has open items. Settle them first.')]]);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Your account has been closed.')]);

        return redirect('/');
    }
}
