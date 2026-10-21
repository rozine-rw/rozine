<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends a browser that opens a data endpoint directly (a typed or bookmarked URL) to the page the
 * data belongs on, instead of showing it raw JSON. The pages' own requests (`useHttp`, Inertia)
 * and API clients ask for JSON or mark themselves as XHR, so they pass through unchanged.
 */
class RedirectBrowserVisit
{
    /** @param  Closure(Request): (Response)  $next */
    public function handle(Request $request, Closure $next, string $route): Response
    {
        if ($request->isMethod('GET') && ! $request->ajax() && ! $request->expectsJson()) {
            return redirect()->route($route, array_intersect_key((array) $request->route()?->parameters(), ['business' => true]));
        }

        return $next($request);
    }
}
