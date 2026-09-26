<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Auditor\CosignAuditReport;
use App\Application\Auditor\FindAuditCosignOperation;
use App\Application\Auditor\GetBusinessAuditReport;
use App\Application\Identity\AuthorizeActiveRole;
use App\Http\Requests\Business\CosignAuditReportRequest;
use App\Http\Requests\Business\ShowAuditCosignOperationRequest;
use App\Http\Requests\Business\ShowAuditReportRequest;
use App\Http\Resources\BusinessAuditReportResource;
use App\Http\Resources\OperationResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BusinessAuditReportController extends Controller
{
    public function __construct(private AuthorizeActiveRole $identity) {}

    public function show(ShowAuditReportRequest $request, GetBusinessAuditReport $action): Response|BusinessAuditReportResource
    {
        $userId = (int) $request->user()?->getAuthIdentifier();
        $revision = $request->validated('identity_context_revision') ?? $this->identity->context($userId, 'business')['context_revision'];
        $page = $action->handle($userId, (int) $revision, (string) $request->route('business'), (string) $request->route('report'));
        $resource = new BusinessAuditReportResource([...$page, 'identity_context_revision' => (int) $revision]);

        return $request->routeIs('api.*') ? $resource : Inertia::render('business/audit-cosign', $resource->resolve($request));
    }

    public function cosign(CosignAuditReportRequest $request, CosignAuditReport $action): OperationResource
    {
        return $this->present($request, $action->handle((int) $request->user()?->getAuthIdentifier(), (int) $request->validated('identity_context_revision'),
            (string) $request->route('business'), (string) $request->route('report'), (int) $request->validated('expected_revision'),
            (int) $request->validated('report_revision'), (int) $request->validated('mandate_version'), (string) $request->validated('digest'),
            $request->boolean('accepted'), (string) $request->validated('note', ''), (string) $request->validated('request_id')));
    }

    public function operation(ShowAuditCosignOperationRequest $request, FindAuditCosignOperation $action): OperationResource
    {
        return $this->present($request, $action->handle((int) $request->user()?->getAuthIdentifier(),
            (int) $request->validated('identity_context_revision'), (string) $request->route('request_id'), (string) $request->validated('command', 'report.cosign')));
    }

    /** @param array<string, mixed> $result */
    private function present(Request $request, array $result): OperationResource
    {
        if ($result['status'] === 'completed') {
            $result['data']['next'] = BusinessAuditReportResource::link(BusinessAuditReportResource::prefix($request).'show',
                ['business' => $result['data']['business_id'], 'report' => $result['data']['report_id']]);
        }

        return new OperationResource($result);
    }
}
