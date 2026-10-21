<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\BrowseStaffDirectory;
use App\Application\Identity\GetStaffAccess;
use App\Application\Identity\SetStaffAccess;
use App\Domain\Identity\IdentityViolation;
use App\Http\Requests\Staff\ListStaffDirectoryRequest;
use App\Http\Requests\Staff\SetStaffAccessRequest;
use App\Http\Resources\StaffDirectoryResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The designed Staff & Roles directory (`admin.open`): every operator with their role and account
 * state, and one operator's 360 with their access history. A superadmin suspends or reactivates
 * another operator from the 360 with a reason; a refusal returns to the reason stage in plain words.
 */
class StaffDirectoryController extends Controller
{
    /** Refusals in the words the reason stage shows. */
    private const MESSAGES = [
        'STAFF_SELF_CHANGE_FORBIDDEN' => 'You cannot change your own access. Ask another super-admin.',
        'STAFF_STATE_UNCHANGED' => "This operator's access already changed. Review it again.",
        'STAFF_VERIFIED_EMAIL_AND_MFA_REQUIRED' => 'This operator needs a verified email and two-factor sign-in before access can return.',
        'IDEMPOTENCY_KEY_REUSED' => 'That request was already used for different details. Try again.',
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
            'roles' => $staff['roles'], 'permissions' => $staff['allowed_actions']]);

        return $request->routeIs('api.*') ? $resource : Inertia::render('admin/parties', $resource->resolve($request));
    }

    /** Suspend (`enabled` false) or reactivate another operator, then reopen their 360. */
    public function access(SetStaffAccessRequest $request, SetStaffAccess $action): RedirectResponse
    {
        $userId = (int) $request->route('operator');
        try {
            $action->handle((int) $request->user()?->getAuthIdentifier(), $userId, (bool) $request->route('enabled'),
                (string) $request->validated('reason'), (string) $request->validated('request_id'));
        } catch (IdentityViolation $violation) {
            if (! array_key_exists($violation->reason, self::MESSAGES)) {
                throw $violation;
            }
            throw ValidationException::withMessages(['reason' => [self::MESSAGES[$violation->reason]]]);
        }

        return redirect()->route('staff.staff.index', ['operator' => $userId]);
    }
}
