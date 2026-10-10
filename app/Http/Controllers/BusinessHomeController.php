<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Business\GetBusinessHome;
use App\Application\Business\GetBusinessMarket;
use App\Application\Business\GetBusinessProfile;
use App\Application\Business\GetBusinessReports;
use App\Application\Identity\AuthorizeActiveRole;
use App\Http\Requests\Business\ShowBusinessHomeRequest;
use App\Http\Resources\BusinessHomeResource;
use App\Http\Resources\BusinessMarketResource;
use App\Http\Resources\BusinessProfileResource;
use App\Http\Resources\BusinessRatingResource;
use App\Http\Resources\BusinessReportsResource;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The Business app's own pages for one business: Home, its Reports, Market and Profile tabs, and
 * the Rating sheet drawn over Home. Reports, Market, Profile and Rating are web pages only: the
 * bearer transport has no such read yet.
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
    public function profile(ShowBusinessHomeRequest $request, GetBusinessProfile $profile): Response
    {
        $section = $request->route('section');
        $resource = new BusinessProfileResource([...$profile->handle($this->userId($request), $this->revision($request), (string) $request->route('business')),
            'section' => is_string($section) ? $section : 'company', 'landing' => $section === null,
            'two_factor' => $request->user()?->hasEnabledTwoFactorAuthentication() === true]);

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
