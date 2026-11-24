<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\ReviewRoleApplications;
use App\Http\Requests\Staff\DecideRoleApplicationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response as FileResponse;

/**
 * The one place each role-switch application is decided (Erastus, 23 Nov 2026): business KYB from the
 * Businesses page and Auditor licences from the Auditors page. The drawer there shows every answer and
 * document; this controller serves those documents and records the decision, then returns to the page.
 */
class StaffRoleApplicationController extends Controller
{
    /** Refusals in the words the drawer shows. */
    private const MESSAGES = [
        'VERSION_CONFLICT' => 'This application changed since you opened it. Review it again.',
        'APPLICATION_NOT_SUBMITTED' => 'This application is no longer waiting for review.',
        'APPLICATION_NOT_FOUND' => 'This application no longer exists.',
        'KYC_APPROVAL_REQUIRED' => 'Approve the applicant\'s own KYC first (Users page). Then approve this application.',
        'INVESTOR_POSITIONS_OPEN' => 'The applicant still holds investments, open orders or money in their Investor wallet. They must clear these before switching role.',
        'APPLICATION_INCOMPLETE' => 'Something required is missing from this application. Ask for more instead.',
        'APPLICATION_DOCUMENT_INTEGRITY_FAILED' => 'A document no longer matches what was uploaded. Ask the applicant to upload it again.',
        'IDENTITY_RECONCILIATION_REQUIRED' => 'This account needs an identity operator, so it cannot be approved here.',
        'STAFF_PERMISSION_REQUIRED' => 'Your staff role cannot decide this application.',
        'IDEMPOTENCY_CONFLICT' => 'That request was already used for different details. Try again.',
    ];

    public function document(Request $request, ReviewRoleApplications $review): FileResponse
    {
        $document = $review->document((int) $request->user()?->getAuthIdentifier(), (string) $request->route('application'), (string) $request->route('document'));
        $disposition = $request->query('disposition') === 'inline' ? HeaderUtils::DISPOSITION_INLINE : HeaderUtils::DISPOSITION_ATTACHMENT;

        return response($document['content'], 200, [
            'Content-Type' => $document['media_type'],
            'Content-Disposition' => HeaderUtils::makeDisposition($disposition, $document['filename'], str_replace('%', '_', Str::ascii($document['filename'])) ?: 'document'),
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    public function decide(DecideRoleApplicationRequest $request, ReviewRoleApplications $review): RedirectResponse
    {
        $actorId = (int) $request->user()?->getAuthIdentifier();
        $applicationId = (string) $request->route('application');
        $decision = (string) $request->validated('decision');
        $result = $review->decide($actorId, $applicationId, (int) $request->validated('expected_revision'), $decision,
            (string) $request->validated('note', ''), (string) $request->validated('request_id'));
        if ($result['status'] !== 'completed') {
            /** @var array<string, list<string>> $fields */
            $fields = $result['field_errors'] ?? [];
            throw ValidationException::withMessages($fields !== [] ? $fields
                : ['form' => [self::MESSAGES[$result['code']] ?? __('We could not record this decision (:code). Try again.', ['code' => $result['code']])]]);
        }
        $application = $review->show($actorId, $applicationId);
        Inertia::flash('toast', ['type' => 'success', 'message' => __(match ($decision) {
            'approve' => $application['role'] === 'business' ? ':name is now the representative of an onboarded business.' : ':name is now an active Auditor.',
            'more' => 'Sent back to :name with your note.',
            default => 'Rejected. :name will see your note.',
        }, ['name' => $application['account']['name']])]);

        return redirect()->route($application['role'] === 'business' ? 'staff.businesses.index' : 'staff.auditors.index',
            ['application' => $applicationId, 'kyb' => $decision === 'approve' ? 'approved' : ($decision === 'more' ? 'changes_requested' : 'rejected')]);
    }
}
