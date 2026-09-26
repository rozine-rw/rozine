<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Auditor\ReviewAuditPublication;
use App\Application\Identity\AuthorizeActiveRole;
use App\Http\Requests\Auditor\ResolveAuditDisputeRequest;
use App\Http\Requests\Auditor\UpholdAuditDisputeRequest;
use App\Http\Requests\Business\DisputeAuditReportRequest;
use App\Http\Resources\AuditDisputeResource;
use App\Http\Resources\OperationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuditDisputeController extends Controller
{
    public function __construct(private ReviewAuditPublication $reviews, private AuthorizeActiveRole $identity) {}

    public function dispute(DisputeAuditReportRequest $request): OperationResource
    {
        $files = [];
        foreach ($request->file('proof_files', []) as $file) {
            $files[] = ['filename' => $file->getClientOriginalName(), 'content' => $file->getContent()];
        }

        return $this->present($request, $this->reviews->dispute((int) $request->user()?->getAuthIdentifier(), (string) $request->route('business'),
            (string) $request->route('report'), $this->integerInputs($request->safe()->except('proof_files')), $files), 'business');
    }

    public function uphold(UpholdAuditDisputeRequest $request): OperationResource
    {
        return $this->present($request, $this->reviews->uphold((int) $request->user()?->getAuthIdentifier(),
            (string) $request->route('report'), $this->integerInputs($request->validated())), 'auditor');
    }

    public function resolve(ResolveAuditDisputeRequest $request): OperationResource
    {
        return $this->present($request, $this->reviews->resolve((int) $request->user()?->getAuthIdentifier(), (string) $request->route('assignment'),
            (string) $request->route('report'), 'audit.dispute.'.(string) $request->route('decision_kind'), $this->integerInputs($request->validated())), 'staff');
    }

    public function show(Request $request): AuditDisputeResource
    {
        abort_if($request->routeIs('api.*') && ! $request->user()?->tokenCan('staff:audit:read'), 403);

        return new AuditDisputeResource($this->reviews->staffCase((int) $request->user()?->getAuthIdentifier(),
            (string) $request->route('assignment'), (string) $request->route('report')));
    }

    public function operation(Request $request): OperationResource
    {
        abort_if($request->routeIs('api.*') && ! $request->user()?->tokenCan('staff:audit:read'), 403);
        $input = $request->validate(['command' => ['required', 'in:audit.dispute.escalate,audit.dispute.resolve']]);

        return $this->present($request, $this->reviews->operation((int) $request->user()?->getAuthIdentifier(), null, 'staff',
            $input['command'], (string) $request->route('request_id')), 'staff');
    }

    public function proof(Request $request): Response
    {
        $role = (string) $request->route('review_role');
        $ability = $role === 'staff' ? 'staff:audit:read' : $role.':read';
        abort_if($request->routeIs('api.*') && ! $request->user()?->tokenCan($ability), 403);
        $input = $request->validate(['identity_context_revision' => ['sometimes', 'integer', 'min:0']]);
        $userId = (int) $request->user()?->getAuthIdentifier();
        $revision = $role === 'staff' ? null : (int) ($input['identity_context_revision'] ?? $this->identity->context($userId, $role)['context_revision']);
        $proof = $this->reviews->proof($userId, $revision, $role, (string) $request->route($role === 'business' ? 'business' : 'assignment'),
            (string) $request->route('report'), (string) $request->route('proof'));

        return response($proof['content'])->withHeaders(['Content-Type' => $proof['mime_type'], 'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff', 'Content-Disposition' => 'attachment; filename="'.$proof['filename'].'"']);
    }

    /** @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function integerInputs(array $input): array
    {
        foreach (['expected_revision', 'report_revision', 'mandate_version', 'identity_context_revision'] as $key) {
            if (isset($input[$key])) {
                $input[$key] = (int) $input[$key];
            }
        }

        return $input;
    }

    /** @param array<string, mixed> $result
     * @param  'business'|'auditor'|'staff'  $role
     */
    private function present(Request $request, array $result, string $role): OperationResource
    {
        if ($result['status'] === 'completed') {
            $parameters = ['report' => $result['data']['report_id']];
            if ($role === 'business') {
                $parameters['business'] = $result['data']['business_id'];
            } elseif ($role === 'staff') {
                $parameters['assignment'] = $result['data']['assignment_id'];
            }
            $name = match ($role) {
                'business' => 'business.audit-reports.show', 'auditor' => 'auditor.reports.show', 'staff' => 'staff.audit.disputes.show',
            };
            $result['data']['next'] = ['url' => route(($request->routeIs('api.*') ? 'api.v1.' : '').$name, $parameters, false), 'method' => 'get'];
        }

        return new OperationResource($result);
    }
}
