<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\BrowseRoleAccessRequests;
use App\Application\Identity\GetStaffAccess;
use App\Application\Identity\GrantRequestedAuditorAccess;
use App\Application\Identity\RequestRoleAccess;
use App\Domain\Identity\IdentityViolation;
use App\Http\Requests\Identity\RequestRoleAccessRequest;
use App\Http\Resources\StaffNavigationResource;
use App\Http\Resources\StaffViewerResource;
use App\Models\RoleAccessRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Business and Auditor access requests (#616). A verified person sends one from the launcher and
 * returns to it, where the card says the request was sent; a refusal returns as a form error. Staff
 * who may grant the role read the requests in the console.
 */
class RoleAccessRequestController extends Controller
{
    /** Refusals in the words the launcher's form shows. */
    private const MESSAGES = [
        'ACCESS_REQUEST_ALREADY_SENT' => 'You already asked for this access. Rozine will contact you.',
        'ACCESS_REQUEST_UNAVAILABLE' => "This access can't be requested from your account. Refresh the page to see your apps.",
        'IDEMPOTENCY_CONFLICT' => 'That request was already sent with different details. Try again.',
    ];

    public function store(RequestRoleAccessRequest $request, RequestRoleAccess $action): RedirectResponse
    {
        /** @var 'business'|'auditor' $role */
        $role = (string) $request->validated('role');
        $optional = fn (string $key): ?string => ($value = trim((string) $request->validated($key, ''))) === '' ? null : $value;
        $result = $action->handle((int) $request->user()?->getAuthIdentifier(), (string) $request->validated('request_id'), $role,
            ['organization' => $optional('organization') ?? '', 'reference' => $optional('reference'), 'phone' => $optional('phone'), 'note' => $optional('note')]);
        if ($result['status'] !== 'completed') {
            throw ValidationException::withMessages(['role' => [self::MESSAGES[$result['code']] ?? 'We could not send this request. Try again.']]);
        }

        return redirect()->route('dashboard');
    }

    public function index(Request $request, BrowseRoleAccessRequests $requests, GetStaffAccess $access): Response
    {
        $staffId = (int) $request->user()?->getAuthIdentifier();
        $page = $requests->handle($staffId);
        $staff = $access->handle($staffId);
        $link = fn (string $route): array => ['url' => route($route, [], false), 'method' => 'get'];

        return Inertia::render('admin/access-requests', ['server_time' => now()->toIso8601String(),
            'viewer' => (new StaffViewerResource($staff['roles']))->resolve($request),
            'nav' => (new StaffNavigationResource($staff['allowed_actions']))->resolve($request),
            'badges' => ['applications' => null, 'disbursements' => null], 'search' => '', 'roles' => $page['roles'],
            // An open Business request opens "+ Onboard a business" filled from the request (#627).
            'requests' => array_map(fn (array $row): array => [...$row, 'grant' => $row['role'] === 'business'
                ? ['url' => route('staff.businesses.index', ['onboard_request' => $row['id']], false), 'method' => 'get']
                // An open Auditor request is granted here; the ICPAR certificate is then verified in the Auditor Network.
                : ['url' => route('staff.access-requests.grant', $row['id'], false), 'method' => 'post']], $page['requests']),
            'links' => ['business' => $link('staff.businesses.index'), 'auditor' => $link('staff.auditors.index')]]);
    }

    /**
     * Grants the Auditor access an open request asked for. The person then uploads their ICPAR
     * certificate in the Auditor app, and it lands on the Auditor Network's verification desk.
     */
    public function grant(Request $request, RoleAccessRequest $accessRequest, GrantRequestedAuditorAccess $action): RedirectResponse
    {
        try {
            $result = $action->handle((int) $request->user()?->getAuthIdentifier(), $accessRequest->id, (string) Str::uuid());
        } catch (IdentityViolation $violation) {
            abort_if($violation->reason === 'STAFF_PERMISSION_REQUIRED', 403);

            return back()->with('toast', ['type' => 'error', 'message' => match ($violation->reason) {
                'IDENTITY_VERIFICATION_REQUIRED' => 'Verify this person\'s identity before granting Auditor access.',
                'ROLE_MEMBERSHIP_CONFLICT', 'ROLE_TRANSITION_INVALID' => 'This person holds another role. An Auditor keeps no other role.',
                default => 'We could not grant Auditor access. Refresh and try again.',
            }]);
        }

        return back()->with('toast', ['type' => 'success', 'message' => $result['code'] === 'MEMBERSHIP_UPDATED'
            ? 'Auditor access granted. They upload their ICPAR certificate next, and it lands on the Auditor Network desk.'
            : 'This person already has an Auditor membership.']);
    }
}
