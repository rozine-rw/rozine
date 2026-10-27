<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Auditor\GetAuditLocation;
use App\Application\Auditor\VerifyAuditLocation;
use App\Application\Business\BrowseBusinessDirectory;
use App\Application\Business\OnboardBusiness;
use App\Application\Identity\GetStaffAccess;
use App\Application\Operations\PlatformPolicies;
use App\Http\Requests\Staff\ListBusinessDirectoryRequest;
use App\Http\Requests\Staff\OnboardBusinessRequest;
use App\Http\Requests\Staff\VerifyAuditLocationRequest;
use App\Http\Resources\StaffBusinessDirectoryResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The designed Business directory (`businesses.view`): every Business with its notes, Investors and
 * capital, and one Business's 360 with its notes and history. A viewer who verifies Businesses also
 * sees, and may record, the premises location audit dispatch measures the 30 km to (BR-51), and may
 * onboard a business for its owner (#375).
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

    /** Onboarding refusals: the field the form shows each under and the words it uses. */
    private const ONBOARD_MESSAGES = [
        'REGISTRY_REFERENCE_INVALID' => ['rdb_code', 'The RDB company code is 9 digits.'],
        'ORGANIZATION_VERIFICATION_REQUIRED' => ['rdb_code', "This RDB code belongs to a company whose verification isn't current. Ask compliance to review it."],
        'ORGANIZATION_REFERENCE_MISMATCH' => ['rdb_code', 'This RDB code does not match the company on record.'],
        'VERSION_CONFLICT' => ['name', 'This business is already on Rozine. Open it from the directory instead.'],
        'BUSINESS_PROFILE_INVALID' => ['name', 'Check the name, sector, district and year.'],
        'REPRESENTATIVE_NOT_VERIFIED' => ['representative_email', 'No verified person on Rozine uses this email. They sign up and complete their identity check first.'],
        'PARTY_AUTHORITY_REQUIRED' => ['representative_email', "This person's identity isn't verified yet, so they can't represent a business."],
        'IDENTITY_EVIDENCE_REQUIRED' => ['evidence_reference', 'Say which documents you checked.'],
        'MANDATE_EVIDENCE_REQUIRED' => ['evidence_reference', 'Say which documents you checked, and why.'],
        'IDEMPOTENCY_CONFLICT' => ['reason', 'That request was already used for different details. Try again.'],
        'IDEMPOTENCY_KEY_REUSED' => ['reason', 'That request was already used for different details. Try again.'],
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
            'roles' => $staff['roles'], 'permissions' => $staff['allowed_actions'], 'onboarded' => session('business_onboarded'),
            'onboarding_sectors' => OnboardBusiness::sectors()]);

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

    /**
     * Onboard a business for its owner (`businesses.verify`) and open it. A refusal returns to the form
     * under the field to change; the notice after it says whether the representative can open the
     * Business app yet, since only an identity operator grants that access.
     */
    public function onboard(OnboardBusinessRequest $request, OnboardBusiness $onboarding): RedirectResponse
    {
        $kind = (string) $request->validated('kind');
        $year = $request->validated('established_year');
        $result = $onboarding->handle((int) $request->user()?->getAuthIdentifier(), ['kind' => $kind === 'person' ? 'person' : 'organization',
            'rdb_code' => $kind === 'person' ? null : (string) $request->validated('rdb_code'), 'name' => (string) $request->validated('name'),
            'industry' => (string) $request->validated('industry'), 'district' => (string) $request->validated('district'),
            'established_year' => $year === null ? null : (int) $year, 'representative_email' => (string) $request->validated('representative_email'),
            'evidence_reference' => (string) $request->validated('evidence_reference'), 'reason' => (string) $request->validated('reason'),
            'effective_at' => (string) $request->validated('effective_at'),
            'request_id' => (string) $request->validated('request_id'), 'mandate_request_id' => (string) $request->validated('mandate_request_id')]);
        abort_if($result['code'] === 'STAFF_PERMISSION_REQUIRED', 403);
        if ($result['status'] !== 'completed') {
            [$field, $message] = self::ONBOARD_MESSAGES[$result['code']] ?? ['reason', 'We could not onboard this business. Try again.'];
            throw ValidationException::withMessages([$field => [$message]]);
        }

        return redirect()->route('staff.businesses.index', ['business' => $result['business_id']])->with('business_onboarded',
            ['business_id' => $result['business_id'], 'representative' => $result['representative'], 'business_access' => $result['business_access']]);
    }
}
