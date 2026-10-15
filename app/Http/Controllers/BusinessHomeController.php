<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Business\GetBusinessHome;
use App\Application\Business\GetBusinessMarket;
use App\Application\Business\GetBusinessProfile;
use App\Application\Business\GetBusinessReports;
use App\Application\Identity\AuthorizeActiveRole;
use App\Application\Identity\ListBusinessComplaints;
use App\Http\Requests\Business\ShowBusinessHomeRequest;
use App\Http\Resources\BusinessAuditPrepResource;
use App\Http\Resources\BusinessHomeResource;
use App\Http\Resources\BusinessMarketResource;
use App\Http\Resources\BusinessProfileResource;
use App\Http\Resources\BusinessRatingResource;
use App\Http\Resources\BusinessReportsResource;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The Business app's own pages for one business: Home, its Reports, Market and Profile tabs, and
 * the Rating and audit-prep sheets drawn over Home. Reports, Market, Profile, Rating and audit prep
 * are web pages only: the bearer transport has no such read yet.
 */
class BusinessHomeController extends Controller
{
    public function __construct(private AuthorizeActiveRole $identity) {}

    public function show(ShowBusinessHomeRequest $request, GetBusinessHome $home): Response|BusinessHomeResource
    {
        $resource = new BusinessHomeResource($home->handle($this->userId($request), $this->revision($request), (string) $request->route('business')));

        return $request->routeIs('api.*') ? $resource : Inertia::render('business/home', $resource->resolve($request));
    }

    public function rating(ShowBusinessHomeRequest $request, GetBusinessHome $home): Response
    {
        $resource = new BusinessRatingResource($home->handle($this->userId($request), $this->revision($request), (string) $request->route('business')));

        return Inertia::render('business/rating', $resource->resolve($request));
    }

    /** With no monthly audit open there is nothing to get ready for, so a stale link lands on Home. */
    public function auditPrep(ShowBusinessHomeRequest $request, GetBusinessHome $home): Response|RedirectResponse
    {
        $business = (string) $request->route('business');
        $data = $home->handle($this->userId($request), $this->revision($request), $business);
        if ($data['audit_window'] === null) {
            return to_route('business.show', ['business' => $business]);
        }

        return Inertia::render('business/audit-prep', (new BusinessAuditPrepResource($data))->resolve($request));
    }

    public function reports(ShowBusinessHomeRequest $request, GetBusinessReports $reports): Response
    {
        $resource = new BusinessReportsResource($reports->handle($this->userId($request), $this->revision($request), (string) $request->route('business')));

        return Inertia::render('business/reports', $resource->resolve($request));
    }

    public function market(ShowBusinessHomeRequest $request, GetBusinessMarket $market): Response
    {
        $resource = new BusinessMarketResource($market->handle($this->userId($request), $this->revision($request), (string) $request->route('business')));

        return Inertia::render('business/market', $resource->resolve($request));
    }

    /**
     * The bare Profile URL lands on the menu (a wide screen opens Company information beside it);
     * `profile/{section}` opens that section on a phone too.
     */
    public function profile(ShowBusinessHomeRequest $request, GetBusinessProfile $profile, ListBusinessComplaints $complaints): Response
    {
        $section = $request->route('section');
        $business = (string) $request->route('business');
        $resource = new BusinessProfileResource([...$profile->handle($this->userId($request), $this->revision($request), $business),
            'complaints' => $complaints->handle($this->userId($request), $this->revision($request), $business),
            'section' => is_string($section) ? $section : 'company', 'landing' => $section === null,
            'two_factor' => $request->user()?->hasEnabledTwoFactorAuthentication() === true,
            'login_alerts' => $request->user()?->getAttribute('login_alerts') !== false]);

        return Inertia::render('business/profile', $resource->resolve($request));
    }

    private function userId(ShowBusinessHomeRequest $request): int
    {
        return (int) $request->user()?->getAuthIdentifier();
    }

    private function revision(ShowBusinessHomeRequest $request): int
    {
        return (int) ($request->validated('identity_context_revision') ?? $this->identity->context($this->userId($request), 'business')['context_revision']);
    }
}
