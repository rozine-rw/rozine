<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Auditor\GetAuditEngagementSummary;
use App\Application\Auditor\GetAuditorAccreditation;
use App\Application\Auditor\ListAuditJobs;
use App\Application\Auditor\ListAuditorFiledReports;
use App\Application\Identity\AuthorizeActiveRole;
use App\Http\Resources\AuditorHomeResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The Auditor role home (`auditor.home`): the designed Home, read through the protected Auditor
 * actions. The identity context is authorized first, exactly as every Auditor page is, so a login
 * without the active Auditor role or without two-factor authentication gets the access page
 * instead; the engagement summary and dispatch standing are shown on the page itself.
 */
class AuditorHomeController extends Controller
{
    /** The partner's most recent filings, shown as Home's recent activity. */
    private const RECENT_FILINGS = 5;

    public function __invoke(Request $request, AuthorizeActiveRole $identity, GetAuditorAccreditation $accreditation,
        GetAuditEngagementSummary $engagements, ListAuditJobs $jobs, ListAuditorFiledReports $reports): Response
    {
        $userId = (int) $request->user()?->getAuthIdentifier();
        $revision = (int) $identity->context($userId, 'auditor')['context_revision'];

        return Inertia::render('auditor/home', (new AuditorHomeResource([
            'accreditation' => $accreditation->handle($userId, $revision),
            'name' => (string) $request->user()?->getAttribute('name'),
            'engagement' => $engagements->handle($userId, $revision),
            'jobs' => $jobs->handle($userId, $revision),
            'reports' => $reports->handle($userId, $revision, limit: self::RECENT_FILINGS),
        ]))->resolve($request));
    }
}
