<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\AuthorizeActiveRole;
use App\Application\Identity\ChangeMembership;
use App\Application\Identity\ResolveVerifiedPerson;
use App\Application\Identity\SelectActiveRole;
use App\Http\Requests\Identity\ChangeMembershipRequest;
use App\Http\Requests\Identity\ResolvePersonRequest;
use App\Http\Requests\Identity\SelectActiveRoleRequest;
use App\Http\Resources\IdentityContextResource;
use App\Http\Resources\IdentityMutationResource;
use Illuminate\Http\Request;

class IdentityManagementController extends Controller
{
    public function resolvePerson(ResolvePersonRequest $request, ResolveVerifiedPerson $action): IdentityMutationResource
    {
        return new IdentityMutationResource($action->handle(
            (int) $request->user()?->getAuthIdentifier(), (int) $request->validated('user_id'),
            (string) $request->validated('identity_reference'), (string) $request->validated('evidence_reference'),
            (string) $request->validated('reason'), (string) $request->validated('request_id'),
        ));
    }

    public function membership(ChangeMembershipRequest $request, ChangeMembership $action): IdentityMutationResource
    {
        return new IdentityMutationResource($action->handle(
            (int) $request->user()?->getAuthIdentifier(), (string) $request->validated('party_id'),
            (string) $request->validated('role'), (string) $request->validated('status'),
            (int) $request->validated('expected_revision'), (string) $request->validated('evidence_reference'),
            (string) $request->validated('reason'), (string) $request->validated('request_id'),
        ));
    }

    public function selectRole(SelectActiveRoleRequest $request, SelectActiveRole $action): IdentityContextResource
    {
        return new IdentityContextResource($action->handle(
            (int) $request->user()?->getAuthIdentifier(), (string) $request->validated('role'),
            (int) $request->validated('expected_revision'), (string) $request->validated('request_id'),
        ));
    }

    public function role(Request $request, string $role, AuthorizeActiveRole $action): IdentityContextResource
    {
        abort_if($request->routeIs('api.*') && ! $request->user()->tokenCan('identity:access'), 403);

        return new IdentityContextResource($action->context((int) $request->user()?->getAuthIdentifier(), $role));
    }
}
