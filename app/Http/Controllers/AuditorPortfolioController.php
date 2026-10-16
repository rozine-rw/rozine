<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Auditor\ListAuditJobs;
use App\Application\Auditor\ListAuditorFiledReports;
use App\Application\Auditor\ListOwnAuditConflicts;
use App\Application\Identity\AuthorizeActiveRole;
use App\Http\Resources\AuditorPortfolioResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The Auditor Portfolio (MVP-AUDITOR-SCR-07, AC-08): the partner's filed reports beside their
 * conflict register. Every read goes through the protected Auditor actions under the current
 * identity context; this controller only picks the report filter and carries the request.
 */
class AuditorPortfolioController extends Controller
{
    public function index(Request $request, AuthorizeActiveRole $identity, ListAuditorFiledReports $reports, ListAuditJobs $jobs,
        ListOwnAuditConflicts $conflicts): Response
    {
        $userId = (int) $request->user()?->getAuthIdentifier();
        $revision = (int) $identity->context($userId, 'auditor')['context_revision'];
        $filter = $request->query('filter');
        $filter = in_array($filter, AuditorPortfolioResource::FILTERS, true) ? $filter : AuditorPortfolioResource::FILTERS[0];

        return Inertia::render('auditor/portfolio', (new AuditorPortfolioResource([
            'identity_context_revision' => $revision,
            'filter' => $filter,
            'reports' => $reports->handle($userId, $revision, $filter === 'all' ? null : $filter === 'published'),
            'jobs' => $jobs->handle($userId, $revision),
            'conflicts' => $conflicts->handle($userId, $revision),
        ]))->resolve($request));
    }
}
