<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\ListInvestorComplaints;
use App\Application\Primary\GetInvestorDeals;
use App\Application\Primary\GetInvestorProfile;
use App\Application\Wallet\LinkPayoutAccount;
use App\Application\Wallet\UnlinkPayoutAccount;
use App\Http\Requests\Investor\LinkPayoutAccountRequest;
use App\Http\Requests\Investor\ShowProfileRequest;
use App\Http\Requests\Investor\UnlinkPayoutAccountRequest;
use App\Http\Resources\InvestorProfileResource;
use App\Http\Resources\InvestorVerifiedResource;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The Investor Profile (`investor.profile`) and the "You're verified" page its verification row
 * opens once the identity is verified (`investor.verified`), web pages only. The account's own
 * name, email, creation date and whether two-factor sign-in is on come from the authenticated
 * user, as the Auditor profile reads its name. Linked accounts are linked and unlinked from the
 * profile with plain Inertia forms; a refusal comes back as form errors on the same section.
 */
class InvestorProfileController extends Controller
{
    /** Refusals that are not about one field, in the words the page shows. */
    private const MESSAGES = [
        'POLICY_INPUT_REQUIRED' => 'Accounts cannot be linked yet. Try again later.',
        'SYNTHETIC_TESTER_REQUIRED' => 'Only approved testers can link accounts on this test site.',
        'PAYOUT_ACCOUNT_NOT_FOUND' => 'That account is no longer linked.',
    ];

    public function show(ShowProfileRequest $request, GetInvestorProfile $profile, ListInvestorComplaints $complaints): Response
    {
        $context = $request->validated('identity_context_revision');
        $user = $request->user();
        /** @var CarbonInterface $joined */
        $joined = $user?->getAttribute('created_at');
        $facts = $profile->handle((int) $user?->getAuthIdentifier(), $context === null ? null : (int) $context);

        return Inertia::render('investor/profile', (new InvestorProfileResource([...$facts, 'section' => $request->validated('section') ?? 'overview',
            'name' => (string) $user?->getAttribute('name'), 'email' => (string) $user?->getAttribute('email'), 'member_since' => $joined->toIso8601String(),
            'two_factor' => $user?->hasEnabledTwoFactorAuthentication() === true, 'login_alerts' => $user?->getAttribute('login_alerts') !== false,
            'complaints' => $complaints->handle((int) $user?->getAuthIdentifier())]))->resolve($request));
    }

    public function link(LinkPayoutAccountRequest $request, LinkPayoutAccount $action): RedirectResponse
    {
        return $this->answer($action->handle((int) $request->user()?->getAuthIdentifier(), (string) $request->validated('request_id'),
            $request->safe()->only(['type', 'network', 'bank', 'number'])));
    }

    public function unlink(UnlinkPayoutAccountRequest $request, string $method, UnlinkPayoutAccount $action): RedirectResponse
    {
        return $this->answer($action->handle((int) $request->user()?->getAuthIdentifier(), (string) $request->validated('request_id'), $method));
    }

    /** @param  array<string, mixed>  $result */
    private function answer(array $result): RedirectResponse
    {
        if ($result['status'] !== 'completed') {
            /** @var array<string, list<string>> $fields */
            $fields = $result['field_errors'];
            throw ValidationException::withMessages($fields === []
                ? ['number' => [self::MESSAGES[$result['code']] ?? 'We could not change your linked accounts. Try again.']]
                : $fields);
        }

        return redirect()->route('investor.profile', ['section' => 'linked']);
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
