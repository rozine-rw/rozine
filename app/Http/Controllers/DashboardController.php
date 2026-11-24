<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\AddInvestorAccess;
use App\Application\Identity\ApplyForRole;
use App\Application\Identity\GetIdentityContext;
use App\Application\Identity\GetInvestorVerification;
use App\Application\Identity\GetStaffAccess;
use App\Application\Identity\RequestRoleAccess;
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
     * Business member who may add Investor access gets the terms they accept to do so. Business and
     * Auditor access is applied for from the card once the person's KYC is sent (role switch, 23 Nov
     * 2026), so the launcher says where that application stands.
     */
    public function __invoke(Request $request, GetIdentityContext $identity, GetStaffAccess $staff, GetInvestorVerification $verification, AddInvestorAccess $investor, RequestRoleAccess $access, ApplyForRole $applications): Response
    {
        $userId = (int) $request->user()?->getAuthIdentifier();
        $context = $identity->handle($userId);
        $standing = ($context['party']['kind'] ?? null) === 'person' ? $applications->standing($userId) : null;

        return Inertia::render('dashboard', [
            'staff_access' => (new StaffAccessResource($staff->handle($userId)))->resolve($request),
            'identity' => (new IdentityContextResource($context))->resolve($request),
            'verification' => $context['code'] === 'IDENTITY_VERIFICATION_REQUIRED' ? $verification->stage($userId) : null,
            'investor_access' => in_array('identity.add_investor', $context['allowed_actions'], true) ? ['terms' => $investor->terms(),
                'link' => ['url' => route('identity.investor-access.store', absolute: false), 'method' => 'post']] : null,
            // Everyone starts as an Investor; once their KYC is sent they may apply for one other role (23 Nov 2026).
            'role_application' => $standing === null ? null : ['kyc' => $standing['kyc'], 'status' => $standing['status'], 'role' => $standing['role']],
            'access_requests' => [...$access->offer($userId), 'link' => ['url' => route('identity.access-requests.store', absolute: false), 'method' => 'post']],
        ]);
    }
}
