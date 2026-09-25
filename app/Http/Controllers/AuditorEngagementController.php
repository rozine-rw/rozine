<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Auditor\AcceptAuditEngagementTerms;
use App\Application\Auditor\FindAuditEngagementOperation;
use App\Application\Auditor\GetAuditEngagementTerms;
use App\Application\Identity\AuthorizeActiveRole;
use App\Http\Requests\Auditor\AcceptEngagementTermsRequest;
use App\Http\Resources\AuditorEngagementResource;
use App\Http\Resources\OperationResource;
use Illuminate\Http\Request;

/** Shared JSON boundary used by the web agreement surface and the versioned API. */
class AuditorEngagementController extends Controller
{
    public function __construct(private AuthorizeActiveRole $identity) {}

    public function show(Request $request, GetAuditEngagementTerms $terms): AuditorEngagementResource
    {
        [$userId, $revision] = $this->readContext($request);

        return new AuditorEngagementResource([...$terms->handle($userId, $revision), 'identity_context_revision' => $revision]);
    }

    public function accept(AcceptEngagementTermsRequest $request, AcceptAuditEngagementTerms $terms): OperationResource
    {
        return new OperationResource($terms->handle((int) $request->user()?->getAuthIdentifier(),
            (int) $request->validated('identity_context_revision'), (string) $request->validated('release_id'),
            (int) $request->validated('expected_revision'), (string) $request->validated('sha256'),
            $request->boolean('accepted'), (string) $request->validated('request_id')));
    }

    public function operation(Request $request, FindAuditEngagementOperation $operations): OperationResource
    {
        [$userId, $revision] = $this->readContext($request);

        return new OperationResource($operations->handle($userId, $revision, (string) $request->route('request_id')));
    }

    /** @return array{int, int} */
    private function readContext(Request $request): array
    {
        abort_if($request->routeIs('api.*') && ! $request->user()?->tokenCan('auditor:read'), 403);
        $userId = (int) $request->user()?->getAuthIdentifier();

        return [$userId, (int) $this->identity->context($userId, 'auditor')['context_revision']];
    }
}
