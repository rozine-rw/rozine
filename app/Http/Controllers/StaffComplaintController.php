<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\AnswerComplaint;
use App\Application\Identity\BrowseComplaints;
use App\Application\Identity\GetStaffAccess;
use App\Http\Requests\Staff\AnswerComplaintRequest;
use App\Http\Requests\Staff\ListComplaintsRequest;
use App\Http\Resources\StaffComplaintsResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Complaints (`complaints.answer`, BRS v2.0 CMP-R03): every complaint with its clocks, searched,
 * one open in a drawer with its conversation. Staff answer it there, and the answer marked as the
 * resolution closes it. A recorded refusal returns as a form error on the answer.
 */
class StaffComplaintController extends Controller
{
    /** Refusals that are not about the text, in the words the drawer shows. */
    private const MESSAGES = [
        'COMPLAINT_NOT_FOUND' => 'This complaint no longer exists.',
        'COMPLAINT_RESOLVED' => 'This complaint is already resolved.',
        'IDEMPOTENCY_CONFLICT' => 'That answer was already sent with different text. Try again.',
    ];

    public function index(ListComplaintsRequest $request, BrowseComplaints $complaints, GetStaffAccess $access): Response|StaffComplaintsResource
    {
        $actorId = (int) $request->user()?->getAuthIdentifier();
        $threads = $complaints->handle($actorId);
        $staff = $access->handle($actorId);

        $resource = new StaffComplaintsResource(['complaints' => $threads, 'search' => trim((string) $request->validated('q', '')),
            'open' => $request->validated('complaint'), 'roles' => $staff['roles'], 'permissions' => $staff['allowed_actions']]);

        return $request->routeIs('api.*') ? $resource : Inertia::render('admin/complaints', $resource->resolve($request));
    }

    public function answer(AnswerComplaintRequest $request, string $complaint, AnswerComplaint $action): RedirectResponse
    {
        $result = $action->handle((int) $request->user()?->getAuthIdentifier(), (string) $request->validated('request_id'), $complaint,
            $request->validated('body'), $request->boolean('resolves'));
        if ($result['status'] !== 'completed') {
            /** @var array<string, list<string>> $fields */
            $fields = $result['field_errors'] ?? [];
            throw ValidationException::withMessages($fields === []
                ? ['body' => [self::MESSAGES[$result['code']] ?? 'We could not send this answer. Try again.']]
                : $fields);
        }

        return redirect()->route('staff.complaints.index', ['complaint' => $complaint]);
    }
}
