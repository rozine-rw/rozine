<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Auditor\FindAuditReportOperation;
use App\Application\Auditor\GetAuditEngagementSummary;
use App\Application\Auditor\GetAuditProcedure;
use App\Application\Auditor\GetAuditReport;
use App\Application\Auditor\IngestAuditLedger;
use App\Application\Auditor\ListAuditJobs;
use App\Application\Auditor\SaveAuditReportStep;
use App\Application\Auditor\StartAuditReport;
use App\Application\Business\GetAuditApplication;
use App\Application\Evidence\ReadAuditStatement;
use App\Application\Identity\AuthorizeActiveRole;
use App\Http\Requests\Auditor\SaveAuditReportStepRequest;
use App\Http\Requests\Auditor\ShowAuditReportOperationRequest;
use App\Http\Requests\Auditor\ShowAuditReportRequest;
use App\Http\Requests\Auditor\StartAuditReportRequest;
use App\Http\Resources\AuditorJobsResource;
use App\Http\Resources\AuditorProcedureResource;
use App\Http\Resources\OperationResource;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AuditorProcedureController extends Controller
{
    public function __construct(private AuthorizeActiveRole $identity) {}

    public function show(ShowAuditReportRequest $request, GetAuditProcedure $procedure, GetAuditApplication $applications, ListAuditJobs $jobs, GetAuditEngagementSummary $engagements): Response|AuditorProcedureResource
    {
        [$userId, $revision] = $this->reader($request);
        $page = $procedure->handle($userId, $revision, (string) $request->route('report'),
            $request->safe()->only(['observed_stock', 'cash', 'stock_units']), $request->validated('step'));
        $resource = new AuditorProcedureResource([...$page,
            'file' => $applications->handle($userId, $revision, $page['report']['assignment_id'], $page['report']['application_id']),
            'jobs' => [...$jobs->handle($userId, $revision), 'identity_context_revision' => $revision, 'limit' => 25,
                'engagement' => $engagements->handle($userId, $revision)]]);

        return $request->routeIs('api.*') ? $resource : Inertia::render('auditor/audit', $resource->resolve($request));
    }

    public function start(StartAuditReportRequest $request, StartAuditReport $action): OperationResource
    {
        return $this->present($request, $action->handle((int) $request->user()?->getAuthIdentifier(), (int) $request->validated('identity_context_revision'),
            (string) $request->route('assignment'), (int) $request->validated('expected_revision'),
            (string) $request->validated('application_id'), (int) $request->validated('application_revision'), (string) $request->validated('request_id')));
    }

    public function statement(Request $request, GetAuditReport $reports, ReadAuditStatement $statements): HttpResponse
    {
        [$userId, $revision] = $this->reader($request);
        $report = $reports->handle($userId, $revision, (string) $request->route('report'));
        $document = $statements->handle($userId, $revision, $report['assignment_id'], (string) $request->route('document'));
        $response = new HttpResponse($document['content'], 200, [
            'Content-Type' => $document['media_type'], 'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff', 'Content-Security-Policy' => "default-src 'none'; sandbox",
            'Repr-Digest' => 'sha-256=:'.base64_encode((string) hex2bin($document['sha256'])).':',
        ]);
        $response->headers->set('Content-Disposition', HeaderUtils::makeDisposition('attachment', $document['filename']));

        return $response;
    }

    public function save(SaveAuditReportStepRequest $request, SaveAuditReportStep $action, IngestAuditLedger $ledger): OperationResource
    {
        $document = $request->file('document');
        if ($document instanceof UploadedFile) {
            return $this->present($request, $ledger->handle((int) $request->user()?->getAuthIdentifier(), (int) $request->validated('identity_context_revision'),
                (string) $request->route('report'), (int) $request->validated('expected_revision'), $document->getClientOriginalName(), $document->getContent(),
                $request->validated('replaces'), (string) $request->validated('request_id')));
        }

        return $this->present($request, $action->handle((int) $request->user()?->getAuthIdentifier(), (int) $request->validated('identity_context_revision'),
            (string) $request->route('report'), (int) $request->validated('expected_revision'), (string) $request->validated('step'),
            $request->safe()->except(['audit_id', 'step', 'identity_context_revision', 'expected_revision', 'request_id']), (string) $request->validated('request_id')));
    }

    public function operation(ShowAuditReportOperationRequest $request, FindAuditReportOperation $action): OperationResource
    {
        [$userId, $revision] = $this->reader($request);

        return $this->present($request, $action->handle($userId, $revision, (string) $request->validated('command'), (string) $request->route('request_id')));
    }

    /** @return array{int, int} */
    private function reader(Request $request): array
    {
        abort_if($request->routeIs('api.*') && ! $request->user()?->tokenCan('auditor:read'), 403);
        $userId = (int) $request->user()?->getAuthIdentifier();

        return [$userId, (int) $this->identity->context($userId, 'auditor')['context_revision']];
    }

    /** @param array<string, mixed> $result */
    private function present(Request $request, array $result): OperationResource
    {
        if ($result['status'] === 'completed') {
            $result['data']['next'] = AuditorJobsResource::link(($request->routeIs('api.*') ? 'api.v1.' : '').'auditor.reports.show', ['report' => $result['data']['audit_id']]);
        }

        return new OperationResource($result);
    }
}
