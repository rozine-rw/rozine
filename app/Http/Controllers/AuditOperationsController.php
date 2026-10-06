<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Auditor\FindAuditResolutionOperation;
use App\Application\Auditor\GetAuditOperationsCase;
use App\Application\Auditor\ResolveAuditAssignment;
use App\Http\Requests\Auditor\ResolveAuditAssignmentRequest;
use App\Http\Requests\Auditor\ShowAuditResolutionOperationRequest;
use App\Http\Resources\AuditOperationsResource;
use App\Http\Resources\OperationResource;
use Illuminate\Http\Request;

class AuditOperationsController extends Controller
{
    public function __construct(private GetAuditOperationsCase $cases) {}

    public function show(Request $request): AuditOperationsResource
    {
        abort_if($request->routeIs('api.*') && ! $request->user()?->tokenCan('staff:audit:read'), 403);

        return new AuditOperationsResource($this->cases->handle((int) $request->user()?->getAuthIdentifier(), (string) $request->route('assignment')));
    }

    public function resolve(ResolveAuditAssignmentRequest $request, ResolveAuditAssignment $action): OperationResource
    {
        $result = $action->handle((int) $request->user()?->getAuthIdentifier(), (string) $request->route('assignment'),
            (int) $request->validated('expected_revision'), (string) $request->route('decision'), (string) $request->validated('reason', ''), (string) $request->validated('request_id'));

        return $this->present($request, $result);
    }

    public function operation(ShowAuditResolutionOperationRequest $request, FindAuditResolutionOperation $action): OperationResource
    {
        return $this->present($request, $action->handle((int) $request->user()?->getAuthIdentifier(), (string) $request->validated('command'), (string) $request->route('request_id')));
    }

    /** @param array<string, mixed> $result */
    private function present(Request $request, array $result): OperationResource
    {
        $data = (array) $result['data'];
        $id = $data['assignment_id'] ?? null;
        $actions = [];
        if (is_string($id)) {
            $actions = $this->cases->handle((int) $request->user()?->getAuthIdentifier(), $id)['allowed_actions'];
            $data['next'] = ['url' => route(($request->routeIs('api.*') ? 'api.v1.' : '').'staff.audit.show', ['assignment' => $id], false), 'method' => 'get'];
        }

        return new OperationResource([...$result, 'data' => $data, 'allowed_actions' => $actions]);
    }
}
