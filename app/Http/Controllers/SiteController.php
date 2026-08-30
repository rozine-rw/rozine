<?php

namespace App\Http\Controllers;

use App\Application\Pulse\RegisterSiteBusiness;
use App\Application\Pulse\RegisterSiteInvestor;
use App\Http\Requests\Site\StoreSiteBusinessRequest;
use App\Http\Requests\Site\StoreSiteInvestorRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The public rozine.rw marketing site.
 */
class SiteController extends Controller
{
    /**
     * Show the marketing site. Every figure it displays is computed on the
     * client, so the page needs no server props.
     */
    public function index(): Response
    {
        return Inertia::render('home');
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
}
