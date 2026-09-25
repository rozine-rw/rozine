<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Auditor\GetAuditEngagementSummary;
use App\Application\Business\ListBusinessApplications;
use App\Application\Identity\AuthorizeActiveRole;
use App\Http\Requests\Business\ListApplicationsRequest;
use App\Http\Resources\AuditorEngagementSummaryResource;
use App\Http\Resources\BusinessApplicationsResource;
use App\Http\Resources\IdentityContextResource;
use Inertia\Inertia;
use Inertia\Response;

class RoleHomeController extends Controller
{
    public function __invoke(ListApplicationsRequest $request, AuthorizeActiveRole $action, ListBusinessApplications $applications, GetAuditEngagementSummary $engagements): Response
    {
        $role = explode('.', (string) $request->route()?->getName())[0];
        $userId = (int) $request->user()?->getAuthIdentifier();
        $expectedContext = $request->validated('identity_context_revision');
        $identity = $action->context($userId, $role, $expectedContext === null ? null : (int) $expectedContext);

        return Inertia::render('identity/role-home', [
            'identity' => (new IdentityContextResource($identity))->resolve($request),
            'role' => $role,
            'section' => $request->query('section') === 'access' ? 'access' : 'overview',
            'engagement' => $role === 'auditor' ? (new AuditorEngagementSummaryResource($engagements->handle($userId, $identity['context_revision'])))->resolve($request) : null,
            'business_applications' => $role === 'business' ? (new BusinessApplicationsResource($applications->handle($userId, $identity['context_revision'],
                $request->validated('before'), (int) ($request->validated('limit') ?? 20))))->resolve($request) : null,
        ]);
    }
}
