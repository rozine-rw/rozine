<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Environment\EnvironmentIsolation;
use App\Application\Pulse\RegisterSiteBusiness;
use App\Application\Pulse\RegisterSiteInvestor;
use App\Application\Pulse\RegisterSiteWaitlist;
use App\Enums\PulseSignupType;
use App\Http\Requests\Site\StoreSiteBusinessRequest;
use App\Http\Requests\Site\StoreSiteInvestorRequest;
use App\Http\Requests\Site\StoreSiteWaitlistRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The public rozine.rw marketing site.
 */
class SiteController extends Controller
{
    /**
     * Show the marketing site. Every figure it displays is computed on the
     * client. App entry is available locally and in isolated environments.
     */
    public function index(EnvironmentIsolation $isolation): Response
    {
        return Inertia::render('home', [
            'showAppEntry' => app()->environment('local') || $isolation->isIsolated(),
        ]);
    }

    /**
     * Record an investor signup taken from the site.
     */
    public function storeInvestor(StoreSiteInvestorRequest $request, RegisterSiteInvestor $register): RedirectResponse
    {
        $register->handle([
            'name' => (string) $request->validated('name'),
            'contact_method' => (string) $request->validated('contact_method'),
            'contact' => (string) $request->validated('contact'),
            'country' => (string) $request->validated('country'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ], (int) $request->validated('pledge_amount'));

        return back();
    }

    /**
     * Record a business signup taken from the site.
     */
    public function storeBusiness(StoreSiteBusinessRequest $request, RegisterSiteBusiness $register): RedirectResponse
    {
        $register->handle([
            'name' => (string) $request->validated('name'),
            'contact_method' => (string) $request->validated('contact_method'),
            'contact' => (string) $request->validated('contact'),
            'province' => (string) $request->validated('province'),
            'district' => (string) $request->validated('district'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ], [
            'annual_revenue' => (int) $request->validated('annual_revenue'),
            'annual_costs' => (int) $request->validated('annual_costs'),
            'term_months' => (int) $request->validated('term_months'),
        ]);

        return back();
    }

    /**
     * Put someone on the landing page waitlist and answer with their place in
     * the queue and the link they can share. A referral code that matches no
     * signup is dropped rather than refused, so a mistyped link never blocks
     * anyone from joining.
     */
    public function storeWaitlist(StoreSiteWaitlistRequest $request, RegisterSiteWaitlist $register): JsonResponse
    {
        $ref = $request->validated('ref');

        $joined = $register->handle([
            'name' => trim((string) $request->validated('name')),
            'contact_method' => $request->contactMethod()->value,
            'contact' => (string) $request->validated('contact'),
            'referred_by' => is_string($ref) && $ref !== '' ? $ref : null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ], PulseSignupType::from((string) $request->validated('role')));

        return response()->json([
            'queue_number' => $joined['queue_number'],
            'position' => $joined['position'],
            'referral_link' => route('site.join', ['ref' => $joined['referral_code']]),
        ], 201);
    }

    /**
     * The link a waitlist signup shares: it opens the landing page at the
     * waitlist form and carries the referral code along.
     */
    public function join(Request $request): RedirectResponse
    {
        $ref = $request->query('ref');
        $query = is_string($ref) && preg_match('/^[A-Za-z0-9]{1,64}$/', $ref) === 1 ? ['ref' => $ref] : [];

        return redirect()->to(route('home', $query).'#join');
    }
}
