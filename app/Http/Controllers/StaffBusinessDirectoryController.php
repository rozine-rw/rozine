<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Auditor\GetAuditLocation;
use App\Application\Auditor\VerifyAuditLocation;
use App\Application\Business\BrowseBusinessDirectory;
use App\Application\Identity\GetStaffAccess;
use App\Application\Operations\PlatformPolicies;
use App\Http\Requests\Staff\ListBusinessDirectoryRequest;
use App\Http\Requests\Staff\VerifyAuditLocationRequest;
use App\Http\Resources\StaffBusinessDirectoryResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The designed Business directory (`businesses.view`): every Business with its notes, Investors and
 * capital, and one Business's 360 with its notes and history. A viewer who verifies Businesses also
 * sees, and may record, the premises location audit dispatch measures the 30 km to (BR-51).
 */
class StaffBusinessDirectoryController extends Controller
{
    /** The design's directory shows the first sixty matches and says how many more there are. */
    private const LIMIT = 60;

    /** Premises verification refusals in the words the form shows. */
    private const PREMISES_MESSAGES = [
        'VERSION_CONFLICT' => "This business's premises record changed since you opened it. Review it again.",
        'AUDIT_COORDINATES_INVALID' => 'Enter the coordinates in decimal degrees.',
        'AUDIT_LOCATION_REVIEW_REQUIRED' => 'This location cannot be recorded as verified. Check the accuracy and try again.',
        'AUDIT_LOCATION_EVIDENCE_REQUIRED' => 'Say what evidence you checked and why, in plain text.',
        'MANDATE_REQUIRED' => "This business has no current mandate, so its premises can't be verified yet.",
        'IDEMPOTENCY_CONFLICT' => 'That request was already used for different details. Try again.',
    ];

    public function __construct(private BrowseBusinessDirectory $directory) {}

    public function index(ListBusinessDirectoryRequest $request, GetStaffAccess $access, GetAuditLocation $location): Response|StaffBusinessDirectoryResource
    {
        $actorId = (int) $request->user()?->getAuthIdentifier();
        /** @var 'all' $chip */
        $chip = (string) $request->validated('chip', 'all');
        /** @var 'raised'|'active_notes'|'investors'|'name' $sort */
        $sort = (string) $request->validated('sort', 'raised');
        $sector = trim((string) $request->validated('sector', ''));
        $sector = $sector === 'all' ? '' : $sector;
        $search = trim((string) $request->validated('q', ''));
        $selected = $request->validated('business');
        $staff = $access->handle($actorId);
        $business = $selected === null ? null : $this->directory->business($actorId, (string) $selected);

        $resource = new StaffBusinessDirectoryResource([...$this->directory->directory($actorId, $chip, $sort, $sector, $search, self::LIMIT), 'policy' => PlatformPolicies::businesses(),
            'chip' => $chip, 'sort' => $sort, 'sector' => $sector, 'search' => $search,
            'business' => $business,
            'premises' => $business !== null && in_array('businesses.verify', $staff['allowed_actions'], true)
                ? $location->review($actorId, 'premises', $business['row']['business_id'], now()->toDateTimeImmutable()) : null,
            'roles' => $staff['roles'], 'permissions' => $staff['allowed_actions']]);

        return $request->routeIs('api.*') ? $resource : Inertia::render('admin/parties', $resource->resolve($request));
    }

    /**
     * Record the Business's premises as verified now. The coordinates and evidence are encrypted
     * with their history; a refusal returns to the form with what to change.
     */
    public function premises(VerifyAuditLocationRequest $request, VerifyAuditLocation $locations): RedirectResponse
    {
        $businessId = (string) $request->route('business');
        $result = $locations->handle((int) $request->user()?->getAuthIdentifier(), 'premises', $businessId, (int) $request->validated('expected_revision'),
            (string) $request->validated('latitude'), (string) $request->validated('longitude'), (int) $request->validated('uncertainty_m'),
            (string) $request->validated('verified_at'), (string) $request->validated('reference'), (string) $request->validated('reason'),
            (string) $request->validated('request_id'), array_keys(self::PREMISES_MESSAGES));
        if ($result['status'] !== 'completed') {
            throw ValidationException::withMessages(['reason' => [self::PREMISES_MESSAGES[$result['code']] ?? 'We could not record this location. Try again.']]);
        }

        return redirect()->route('staff.businesses.index', ['business' => $businessId]);
    }
}
