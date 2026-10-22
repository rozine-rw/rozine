<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Auditor\BrowseAuditReportRegister;
use App\Application\Auditor\RemindAuditReport;
use App\Application\Auditor\SealedAuditReportDocument;
use App\Application\Identity\GetStaffAccess;
use App\Http\Requests\Staff\ListAuditReportRegisterRequest;
use App\Http\Resources\StaffAuditReportRegisterResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The design's Reports oversight (`audit.reports.view`): every audit with the state of its report,
 * a sealed report's PDF, and a reminder to whoever owes the next step (`audit.assignments.manage`).
 */
class StaffAuditReportController extends Controller
{
    public function index(ListAuditReportRegisterRequest $request, BrowseAuditReportRegister $register, GetStaffAccess $access): Response
    {
        $actorId = (int) $request->user()?->getAuthIdentifier();
        $rows = $register->register($actorId);
        $staff = $access->handle($actorId);

        return Inertia::render('admin/audit-reports', (new StaffAuditReportRegisterResource(['rows' => $rows, 'chip' => (string) $request->validated('chip', 'all'),
            'roles' => $staff['roles'], 'permissions' => $staff['allowed_actions']]))->resolve($request));
    }

    public function remind(Request $request, RemindAuditReport $remind, string $assignment): RedirectResponse
    {
        $remind->handle((int) $request->user()?->getAuthIdentifier(), $assignment);

        return back(fallback: route('staff.reports.index'));
    }

    public function pdf(Request $request, BrowseAuditReportRegister $register, string $report): HttpResponse
    {
        $document = new SealedAuditReportDocument($register->sealed((int) $request->user()?->getAuthIdentifier(), $report));

        return new HttpResponse($document->pdf(), 200, ['Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$document->filename().'"', 'X-Content-Type-Options' => 'nosniff']);
    }
}
