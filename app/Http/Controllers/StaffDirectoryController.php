<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\ApproveStaffAccess;
use App\Application\Identity\BrowseStaffDirectory;
use App\Application\Identity\GetStaffAccess;
use App\Application\Identity\GrantStaffAccess;
use App\Application\Identity\SetStaffAccess;
use App\Application\Identity\StaffAccessRefused;
use App\Http\Requests\Staff\GrantStaffAccessRequest;
use App\Http\Requests\Staff\ListStaffDirectoryRequest;
use App\Http\Requests\Staff\SetStaffAccessRequest;
use App\Http\Resources\StaffDirectoryResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The designed Staff & Roles directory (`admin.open`): every operator with their role and account
 * state, and one operator's 360 with their access history. A superadmin adds an operator by email
 * with one role, and suspends or reactivates another operator from the 360, each with a reason; a
 * refusal returns to the form in plain words.
 */
class StaffDirectoryController extends Controller
{
    /** Refusals in the words the reason stage shows. */
    private const MESSAGES = [
        'STAFF_SELF_APPROVAL_FORBIDDEN' => 'A different super-admin must approve this request. The requester and recipient cannot approve it.',
        'STAFF_APPROVAL_COMPLETED' => 'This request has already been approved.',
        'STAFF_APPROVAL_STALE' => 'This account changed after the request. Review it and create a new request.',
        'STAFF_SELF_CHANGE_FORBIDDEN' => 'You cannot change your own access. Ask another super-admin.',
        'STAFF_STATE_UNCHANGED' => "This operator's access already changed. Review it again.",
        'STAFF_VERIFIED_EMAIL_AND_MFA_REQUIRED' => 'This operator needs a verified email and two-factor sign-in before access can return.',
        'IDEMPOTENCY_KEY_REUSED' => 'That request was already used for different details. Try again.',
        'STAFF_EMAIL_NOT_FOUND' => 'No account uses that email. The operator signs up first, then you add them.',
        'STAFF_ALREADY_OPERATOR' => 'That account is already an operator. Open it to suspend or reactivate it.',
        'DEDICATED_STAFF_ACCOUNT_REQUIRED' => 'That account invests or runs a business. Operators need a separate, dedicated account.',
    ];

    public function index(ListStaffDirectoryRequest $request, BrowseStaffDirectory $directory, GetStaffAccess $access): Response|StaffDirectoryResource
    {
        $actorId = (int) $request->user()?->getAuthIdentifier();
        /** @var 'all'|'active'|'frozen' $chip */
        $chip = (string) $request->validated('chip', 'all');
        $search = trim((string) $request->validated('q', ''));
        $operator = $request->validated('operator');
        $staff = $access->handle($actorId);

        $resource = new StaffDirectoryResource([...$directory->directory($actorId, $chip, $search), 'chip' => $chip, 'search' => $search,
            'member' => $operator === null ? null : $directory->member($actorId, (int) $operator), 'matrix' => $directory->permissions($actorId), 'log' => $directory->log($actorId),
            'roles' => $staff['roles'], 'permissions' => $staff['allowed_actions'],
            'pending' => $directory->pending($actorId)]);

        return $request->routeIs('api.*') ? $resource : Inertia::render('admin/parties', $resource->resolve($request));
    }

    /** Add an operator, then open their 360. */
    public function grant(GrantStaffAccessRequest $request, GrantStaffAccess $action): RedirectResponse
    {
        $result = $this->refusing('email', fn (): array => $action->handle((int) $request->user()?->getAuthIdentifier(), (string) $request->validated('email'),
            (string) $request->validated('role'), (string) $request->validated('reason'), (string) $request->validated('request_id')));

        return redirect()->route('staff.staff.index', $result['code'] === 'STAFF_SUPERADMIN_APPROVAL_REQUIRED' ? [] : ['operator' => $result['user_id']]);
    }

    public function approve(SetStaffAccessRequest $request, ApproveStaffAccess $action): RedirectResponse
    {
        $result = $this->refusing('reason', fn (): array => $action->handle((int) $request->user()?->getAuthIdentifier(),
            (string) $request->route('proposal'), (string) $request->validated('reason'), (string) $request->validated('request_id')));

        return redirect()->route('staff.staff.index', ['operator' => $result['user_id']]);
    }

    /** Suspend (`enabled` false) or reactivate another operator, then reopen their 360. */
    public function access(SetStaffAccessRequest $request, SetStaffAccess $action): RedirectResponse
    {
        $userId = (int) $request->route('operator');
        $this->refusing('reason', fn (): array => $action->handle((int) $request->user()?->getAuthIdentifier(), $userId, (bool) $request->route('enabled'),
            (string) $request->validated('reason'), (string) $request->validated('request_id')));

        return redirect()->route('staff.staff.index', ['operator' => $userId]);
    }

    /**
     * Runs a staff change, turning a refusal the form can explain into a message on `$field`.
     *
     * @param  callable(): array<string, mixed>  $change
     * @return array<string, mixed>
     */
    private function refusing(string $field, callable $change): array
    {
        try {
            return $change();
        } catch (StaffAccessRefused $refusal) {
            if (! array_key_exists($refusal->reason, self::MESSAGES)) {
                throw $refusal->violation();
            }
            throw ValidationException::withMessages([$field => [self::MESSAGES[$refusal->reason]]]);
        }
    }
}
