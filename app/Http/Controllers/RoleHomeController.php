<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Auditor\GetAuditEngagementSummary;
use App\Application\Business\ListBusinessApplications;
use App\Application\Identity\AuthorizeActiveRole;
use App\Domain\Identity\IdentityViolation;
use App\Http\Requests\Business\ListApplicationsRequest;
use App\Http\Resources\AuditorEngagementSummaryResource;
use App\Http\Resources\AuditorJobsResource;
use App\Http\Resources\BusinessApplicationsResource;
use App\Http\Resources\IdentityContextResource;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RoleHomeController extends Controller
{
    public function __invoke(ListApplicationsRequest $request, AuthorizeActiveRole $action, ListBusinessApplications $applications, GetAuditEngagementSummary $engagements): Response|RedirectResponse
    {
        $role = explode('.', (string) $request->route()?->getName())[0];
        $userId = (int) $request->user()?->getAuthIdentifier();
        $expectedContext = $request->validated('identity_context_revision');
        try {
            $identity = $action->context($userId, $role, $expectedContext === null ? null : (int) $expectedContext);
        } catch (IdentityViolation $violation) {
            // A person still being verified browses the Investor deals until the role opens.
            if ($role === 'investor' && $violation->reason === 'IDENTITY_VERIFICATION_REQUIRED') {
                return redirect()->route('investor.deals');
            }

            throw $violation;
        }

        return Inertia::render('identity/role-home', [
            'identity' => (new IdentityContextResource($identity))->resolve($request),
            'role' => $role,
            'links' => $role === 'auditor' ? AuditorJobsResource::links($request) : null,
            'section' => $request->query('section') === 'access' ? 'access' : 'overview',
            'engagement' => $role === 'auditor' ? (new AuditorEngagementSummaryResource($engagements->handle($userId, $identity['context_revision'])))->resolve($request) : null,
            'business_applications' => $role === 'business' ? (new BusinessApplicationsResource($applications->handle($userId, $identity['context_revision'],
                $request->validated('before'), (int) ($request->validated('limit') ?? 20))))->resolve($request) : null,
        ]);
    }
}
