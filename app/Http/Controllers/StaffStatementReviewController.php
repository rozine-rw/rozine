<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Evidence\FindStatementReviewOperation;
use App\Application\Evidence\GetStaffStatementAuditReadiness;
use App\Application\Evidence\RequestStatementReview;
use App\Http\Requests\Staff\RequestStatementReviewRequest;
use App\Http\Resources\OperationResource;
use Illuminate\Http\Request;

/**
 * Staff request a statement review for a Business, and look up that request's receipt after a lost
 * response (design resolution R1.3). The receipt points back to the Business's readiness.
 */
class StaffStatementReviewController extends Controller
{
    public function __construct(private GetStaffStatementAuditReadiness $readiness) {}

    public function store(RequestStatementReviewRequest $request, RequestStatementReview $action): OperationResource
    {
        return $this->present($request, $action->handle((int) $request->user()?->getAuthIdentifier(), (string) $request->route('business'),
            $request->binding(), (string) $request->validated('reason', ''), (string) $request->validated('request_id')));
    }

    public function operation(Request $request, FindStatementReviewOperation $action): OperationResource
    {
        abort_if($request->routeIs('api.*') && ! ($request->user()?->tokenCan('staff:audit:read') ?? false), 403);

        return $this->present($request, $action->handle((int) $request->user()?->getAuthIdentifier(), (string) $request->route('business'), (string) $request->route('request_id')));
    }

    /** @param array<string, mixed> $result */
    private function present(Request $request, array $result): OperationResource
    {
        $business = (string) $request->route('business');
        $readiness = $this->readiness->handle((int) $request->user()?->getAuthIdentifier(), $business);

        return new OperationResource([...$result, 'data' => [...(array) $result['data'],
            'next' => ['url' => route('api.v1.staff.businesses.statement-audit.readiness', ['business' => $business], false), 'method' => 'get']],
            'allowed_actions' => $readiness['allowed_actions']]);
    }
}
