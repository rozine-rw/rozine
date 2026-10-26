<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\GetIdentityContext;
use App\Application\Identity\GetInvestorVerification;
use App\Application\Identity\GetStaffAccess;
use App\Domain\Identity\InvestorAccessTerms;
use App\Http\Resources\IdentityContextResource;
use App\Http\Resources\StaffAccessResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * The launcher. A person asked to verify their identity also gets where their own check stands,
     * so one already with Compliance is told it is under review instead of being asked again. A
     * Business member who may add Investor access gets the terms they accept to do so.
     */
    public function __invoke(Request $request, GetIdentityContext $identity, GetStaffAccess $staff, GetInvestorVerification $verification): Response
    {
        $userId = (int) $request->user()?->getAuthIdentifier();
        $context = $identity->handle($userId);

        return Inertia::render('dashboard', [
            'staff_access' => (new StaffAccessResource($staff->handle($userId)))->resolve($request),
            'identity' => (new IdentityContextResource($context))->resolve($request),
            'verification' => $context['code'] === 'IDENTITY_VERIFICATION_REQUIRED' ? $verification->stage($userId) : null,
            'investor_access' => in_array('identity.add_investor', $context['allowed_actions'], true) ? ['terms' => InvestorAccessTerms::toArray(),
                'link' => ['url' => route('identity.investor-access.store', absolute: false), 'method' => 'post']] : null,
        ]);
    }
}
