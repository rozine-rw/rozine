<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Auditor\GetAuditLocation;
use App\Application\Auditor\GetBusinessAuditEngagement;
use App\Application\Auditor\ManageBusinessAudit;
use App\Application\Auditor\VerifyAuditLocation;
use App\Application\Business\BrowseBusinessDirectory;
use App\Application\Business\OnboardBusiness;
use App\Application\Identity\GetStaffAccess;
use App\Application\Operations\PlatformPolicies;
use App\Http\Requests\Auditor\ResolveAuditAssignmentRequest;
use App\Http\Requests\Staff\ListBusinessDirectoryRequest;
use App\Http\Requests\Staff\OnboardBusinessRequest;
use App\Http\Requests\Staff\RecordAuditorIndependenceRequest;
use App\Http\Requests\Staff\RequestFlashAuditRequest;
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
 * onboard a business for its owner (#375). A viewer who manages audit assignments sees the audit open
 * on the Business, may request a Flash audit when none is (BR-48), may redispatch or close one that
 * waits on Audit Operations, and may record the independence reviews dispatch needs (#446).
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

    /** Flash audit and redispatch refusals in the words the form shows. */
    private const AUDIT_MESSAGES = [
        'ASSIGNMENT_REASON_REQUIRED' => 'Say why, in plain text.',
        'AUDIT_ASSIGNMENT_RESUMED' => 'This business already has an audit in progress.',
        'AUDIT_ENGAGEMENT_CLOSED' => "Audit Operations closed this business's last audit, so a new one can't be requested here.",
        'VERSION_CONFLICT' => 'This audit changed since you opened it. Review it again.',
        'ASSIGNMENT_NOT_IN_OPERATIONS' => 'This audit is no longer with Audit Operations.',
        'AUDIT_DISPATCH_EXHAUSTED' => 'This audit has used every dispatch attempt or passed its deadline, so it cannot be redispatched.',
        'IDEMPOTENCY_CONFLICT' => 'That request was already used for different details. Try again.',
    ];

    /** Independence review refusals in the words the form shows. */
    private const INDEPENDENCE_MESSAGES = [
        'VERSION_CONFLICT' => "This partner's independence review changed since you opened it. Review it again.",
        'AUDITOR_INDEPENDENCE_EVIDENCE_REQUIRED' => 'Say what you checked and why, in plain text.',
        'AUDITOR_INDEPENDENCE_FACTS_INVALID' => "The date the role ended can't be in the future.",
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

    public function index(ListBusinessDirectoryRequest $request, GetStaffAccess $access, GetAuditLocation $location, GetBusinessAuditEngagement $audits): Response|StaffBusinessDirectoryResource
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
            'audit' => $business !== null && in_array('audit.assignments.manage', $staff['allowed_actions'], true)
                ? ['engagement' => $audits->handle($actorId, $business['row']['business_id']), 'partners' => $audits->partners($actorId, $business['row']['business_id'])] : null,
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
     * Request a Flash audit of the Business now (BR-48). It is dispatched like any other, with its 24-hour
     * deadline; a refusal returns to the form with what to change.
     */
    public function flashAudit(RequestFlashAuditRequest $request, ManageBusinessAudit $audits): RedirectResponse
    {
        $businessId = (string) $request->route('business');
        $this->audit($audits->requestFlash((int) $request->user()?->getAuthIdentifier(), $businessId,
            (string) $request->validated('reason', ''), (string) $request->validated('request_id')));

        return redirect()->route('staff.businesses.index', ['business' => $businessId]);
    }

    /**
     * Offer the Business's audit that waits on Audit Operations to the next eligible partner. With no
     * eligible partner yet it stays with Operations, its attempts unchanged.
     */
    public function redispatchAudit(ResolveAuditAssignmentRequest $request, ManageBusinessAudit $audits): RedirectResponse
    {
        return $this->resolveAudit($request, $audits, 'redispatch');
    }

    /**
     * Close the Business's audit that waits on Audit Operations. Closing ends the Business's audits
     * from here: a later Flash request is refused (AUDIT_ENGAGEMENT_CLOSED).
     */
    public function closeAudit(ResolveAuditAssignmentRequest $request, ManageBusinessAudit $audits): RedirectResponse
    {
        return $this->resolveAudit($request, $audits, 'close');
    }

    /**
     * Record an independence review of an Audit Partner within 30 km against the Business, now, so
     * dispatch may offer the partner the Business's audits when it finds no conflict.
     */
    public function auditorIndependence(RecordAuditorIndependenceRequest $request, ManageBusinessAudit $audits): RedirectResponse
    {
        $businessId = (string) $request->route('business');
        $ended = $request->validated('role_tie_ended_on');
        $this->audit($audits->recordIndependence((int) $request->user()?->getAuthIdentifier(), $businessId, (string) $request->route('party'),
            (int) $request->validated('expected_revision'), ['financial_interest' => $request->boolean('financial_interest'),
                'current_role_tie' => $request->boolean('current_role_tie'), 'role_tie_ended_at' => $ended === null ? null : $ended.'T00:00:00Z',
                'family_or_business_conflict' => $request->boolean('family_or_business_conflict'), 'unresolved_conflict' => $request->boolean('unresolved_conflict')],
            (string) $request->validated('reference', ''), (string) $request->validated('reason', ''), (string) $request->validated('request_id')), self::INDEPENDENCE_MESSAGES);

        return redirect()->route('staff.businesses.index', ['business' => $businessId]);
    }

    private function resolveAudit(ResolveAuditAssignmentRequest $request, ManageBusinessAudit $audits, string $decision): RedirectResponse
    {
        $this->audit($audits->resolve((int) $request->user()?->getAuthIdentifier(), (string) $request->route('assignment'),
            (int) $request->validated('expected_revision'), $decision, (string) $request->validated('reason', ''), (string) $request->validated('request_id')));

        return redirect()->route('staff.businesses.index', ['business' => (string) $request->route('business')]);
    }

    /**
     * @param  array<string, mixed>  $result
     * @param  array<string, string>  $messages
     */
    private function audit(array $result, array $messages = self::AUDIT_MESSAGES): void
    {
        if ($result['status'] !== 'completed' || $result['code'] === 'AUDIT_ASSIGNMENT_RESUMED') {
            throw ValidationException::withMessages(['reason' => [$messages[$result['code']] ?? 'We could not do that. Try again.']]);
        }
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
