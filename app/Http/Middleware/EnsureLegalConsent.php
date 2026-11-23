<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Application\Identity\AcceptLegalDocuments;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Nobody acts in the Investor, Business or Auditor app, on the web or through API v1, until they
 * have agreed to the current Terms, Privacy Notice and Disclaimers. A page visit goes to the review
 * page and comes back afterwards; a command or API call is refused with LEGAL_CONSENT_REQUIRED.
 * Staff pages, settings, sign-out and the legal pages themselves are never held.
 */
class EnsureLegalConsent
{
    public function __construct(private AcceptLegalDocuments $acceptance) {}

    /** @param Closure(Request): (Response) $next */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null || ! $request->routeIs('investor.*', 'business.*', 'auditor.*', 'api.v1.investor.*', 'api.v1.business.*', 'api.v1.auditor.*')
            || $this->acceptance->satisfied((int) $user->getAuthIdentifier())) {
            return $next($request);
        }

        $review = route('legal.review', absolute: false);
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => 'Agree to the current Terms, Privacy Notice and Disclaimers before you continue.',
                'code' => 'LEGAL_CONSENT_REQUIRED', 'review' => $review], 409);
        }
        if ($request->isMethod('GET')) {
            $request->session()->put('url.intended', $request->fullUrl());
        }

        return redirect($review);
    }
}
