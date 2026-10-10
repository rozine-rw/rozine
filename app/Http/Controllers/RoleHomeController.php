<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Business\ListBusinessApplications;
use App\Application\Identity\AuthorizeActiveRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The launcher opens an app at its role home (`investor.home`, `business.home`). There is no page
 * between the launcher and the app: once the active role is authorized, the role home sends the
 * viewer to the app's designed home, Deals for an Investor and the business's own Home for a
 * Business member. Someone without the active role still gets the access-denied answer.
 */
class RoleHomeController extends Controller
{
    public function __invoke(Request $request, AuthorizeActiveRole $action, ListBusinessApplications $applications): RedirectResponse
    {
        $role = explode('.', (string) $request->route()?->getName())[0];
        $userId = (int) $request->user()?->getAuthIdentifier();
        $identity = $action->context($userId, $role, null);
        if ($role === 'investor') {
            return redirect()->route('investor.deals');
        }

        $business = $applications->handle($userId, $identity['context_revision'], null, 1)['entries'][0]['business_id'] ?? null;

        return $business === null ? redirect()->route('dashboard') : redirect()->route('business.show', ['business' => $business]);
    }
}
