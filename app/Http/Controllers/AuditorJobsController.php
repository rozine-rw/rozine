<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Auditor\FindAuditAssignmentOperation;
use App\Application\Auditor\GetOwnAuditConflict;
use App\Application\Auditor\ListAuditJobs;
use App\Application\Auditor\ListOwnAuditConflicts;
use App\Application\Auditor\ProjectAuditAssignmentOperation;
use App\Application\Auditor\RespondToAuditAssignment;
use App\Application\Business\GetAuditApplication;
use App\Application\Identity\AuthorizeActiveRole;
use App\Http\Requests\Auditor\ListAuditJobsRequest;
use App\Http\Requests\Auditor\RespondToAssignmentRequest;
use App\Http\Requests\Auditor\ShowAuditorOperationRequest;
use App\Http\Resources\AuditorConflictsResource;
use App\Http\Resources\AuditorFileResource;
use App\Http\Resources\AuditorJobsResource;
use App\Http\Resources\OperationResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditorJobsController extends Controller
{
    public function __construct(private AuthorizeActiveRole $identity, private GetOwnAuditConflict $conflicts, private ProjectAuditAssignmentOperation $outcomes) {}

    public function index(ListAuditJobsRequest $request, ListAuditJobs $jobs): Response|AuditorJobsResource
    {
        [$userId, $revision] = $this->reader($request);
        $limit = (int) $request->validated('limit', 25);
        $resource = new AuditorJobsResource([...$jobs->handle($userId, $revision, $request->validated('before'), $limit),
            'identity_context_revision' => $revision, 'limit' => $limit]);

        return $request->routeIs('api.*') ? $resource : Inertia::render('auditor/jobs', $resource->resolve($request));
    }

    public function show(Request $request, GetAuditApplication $application, ListAuditJobs $jobs): Response|AuditorFileResource
    {
        [$userId, $revision] = $this->reader($request);
        $background = $jobs->handle($userId, $revision);
        $resource = new AuditorFileResource(['record' => $application->handle($userId, $revision, (string) $request->route('assignment')),
            'jobs' => [...$background, 'identity_context_revision' => $revision, 'limit' => 25]]);

        return $request->routeIs('api.*') ? $resource : Inertia::render('auditor/file', $resource->resolve($request));
    }

    public function conflicts(ListAuditJobsRequest $request, ListOwnAuditConflicts $conflicts): Response|AuditorConflictsResource
    {
        [$userId, $revision] = $this->reader($request);
        $limit = (int) $request->validated('limit', 25);
        $resource = new AuditorConflictsResource([...$conflicts->handle($userId, $revision, $request->validated('before'), $limit),
            'identity_context_revision' => $revision, 'limit' => $limit]);

        return $request->routeIs('api.*') ? $resource : Inertia::render('auditor/conflicts', $resource->resolve($request));
    }

    public function conflict(Request $request): Response|AuditorConflictsResource
    {
        [$userId, $revision] = $this->reader($request);
        $resource = new AuditorConflictsResource(['data' => [$this->conflicts->handle($userId, $revision, (string) $request->route('assignment'))],
            'next_cursor' => null, 'identity_context_revision' => $revision, 'limit' => 25]);

        return $request->routeIs('api.*') ? $resource : Inertia::render('auditor/conflicts', $resource->resolve($request));
    }

    public function respond(RespondToAssignmentRequest $request, RespondToAuditAssignment $action): OperationResource
    {
        $userId = (int) $request->user()?->getAuthIdentifier();
        $revision = (int) $request->validated('identity_context_revision');
        $decision = (string) $request->route('decision');
        $result = $action->handle($userId, $revision, (string) $request->route('assignment'), (int) $request->validated('expected_revision'),
            $decision, $request->validated('kind'), (string) $request->validated('reason', ''), (string) $request->validated('request_id'), $request->validated('reason_code'));

        return $this->present($result, $userId, $revision);
    }

    public function operation(ShowAuditorOperationRequest $request, FindAuditAssignmentOperation $action): OperationResource
    {
        [$userId, $revision] = $this->reader($request);

        return $this->present($action->handle($userId, $revision, (string) $request->validated('command'), (string) $request->route('request_id')),
            $userId, $revision);
    }

    /** @return array{int, int} */
    private function reader(Request $request): array
    {
        abort_if($request->routeIs('api.*') && ! $request->user()?->tokenCan('auditor:read'), 403);
        $userId = (int) $request->user()?->getAuthIdentifier();

        return [$userId, (int) $this->identity->context($userId, 'auditor')['context_revision']];
    }

    /** @param array<string, mixed> $result */
    private function present(array $result, int $userId, int $revision): OperationResource
    {
        $result = $this->outcomes->handle($userId, $revision, $result);
        $data = (array) $result['data'];
        $assignmentId = $data['assignment_id'] ?? null;
        if ($result['status'] === 'completed') {
            $data['next'] = AuditorJobsResource::link('auditor.jobs.index');
            if ($result['code'] === 'ASSIGNMENT_ACCEPTED' && is_string($assignmentId) && $result['allowed_actions'] !== []) {
                $data['next'] = AuditorJobsResource::link('auditor.jobs.show', ['assignment' => $assignmentId]);
            } elseif ($result['code'] === 'CONFLICT_RECORDED' && is_string($assignmentId)) {
                $data['next'] = AuditorJobsResource::link('auditor.conflicts.show', ['assignment' => $assignmentId]);
            }
        }

        return new OperationResource([...$result, 'data' => $data]);
    }
}
