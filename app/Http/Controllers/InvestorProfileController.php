<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Primary\GetInvestorDeals;
use App\Application\Primary\GetInvestorProfile;
use App\Http\Requests\Investor\ShowProfileRequest;
use App\Http\Resources\InvestorProfileResource;
use App\Http\Resources\InvestorVerifiedResource;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The Investor Profile (`investor.profile`) and the "You're verified" page its verification row
 * opens once the identity is verified (`investor.verified`), web pages only. The account's own
 * name, email and creation date come from the authenticated user, as the Auditor profile reads
 * its name.
 */
class InvestorProfileController extends Controller
{
    public function show(ShowProfileRequest $request, GetInvestorProfile $profile): Response
    {
        $context = $request->validated('identity_context_revision');
        $user = $request->user();
        /** @var CarbonInterface $joined */
        $joined = $user?->getAttribute('created_at');
        $facts = $profile->handle((int) $user?->getAuthIdentifier(), $context === null ? null : (int) $context);

        return Inertia::render('investor/profile', (new InvestorProfileResource([...$facts, 'section' => $request->validated('section') ?? 'overview',
            'name' => (string) $user?->getAttribute('name'), 'email' => (string) $user?->getAttribute('email'), 'member_since' => $joined->toIso8601String()]))->resolve($request));
    }

    /** The wallet and the open deals; a person still being verified is sent back to their verification. */
    public function verified(Request $request, GetInvestorDeals $deals): Response|RedirectResponse
    {
        $page = $deals->page((int) $request->user()?->getAuthIdentifier(), null, []);
        if ($page['verification'] !== null) {
            return redirect()->route('investor.verification');
        }

        return Inertia::render('investor/verified', (new InvestorVerifiedResource($page))->resolve($request));
    }
}
