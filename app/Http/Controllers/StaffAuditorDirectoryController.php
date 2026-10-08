<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Auditor\BrowseAuditorDirectory;
use App\Application\Auditor\RecordAuditorStanding;
use App\Application\Identity\GetStaffAccess;
use App\Http\Requests\Staff\DecideAuditorLicenceRequest;
use App\Http\Requests\Staff\ListAuditorDirectoryRequest;
use App\Http\Resources\OperationResource;
use App\Http\Resources\StaffAuditorDirectoryResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The designed Audit Partner network (`audit.partners.verify`): every partner with their standing
 * and engagements, and one partner's 360, where staff verify or reject a licence submission with a
 * reason through the existing accreditation review. A recorded refusal returns to the reason stage.
 */
class StaffAuditorDirectoryController extends Controller
{
    /** The design's directory shows the first sixty matches and says how many more there are. */
    private const LIMIT = 60;

    /** The accreditation rules' version, as the review records it. */
    private const POLICY_VERSION = 'engineering-2026-09-23.4';

    /** Refusals in the words the reason stage shows. */
    private const MESSAGES = [
        'VERSION_CONFLICT' => "This partner's record changed since you opened it. Review it again.",
        'ACCREDITATION_SUBMISSION_STALE' => 'This licence submission is no longer waiting for review.',
        'ACCREDITATION_SUBMISSION_EXPIRED' => 'This certificate has already expired. Reject it so the partner can submit a current one.',
        'IDEMPOTENCY_CONFLICT' => 'That request was already used for different details. Try again.',
    ];

    public function __construct(private BrowseAuditorDirectory $directory) {}

    public function index(ListAuditorDirectoryRequest $request, GetStaffAccess $access): Response|StaffAuditorDirectoryResource
    {
        $actorId = (int) $request->user()?->getAuthIdentifier();
        /** @var 'all'|'active'|'pending'|'licence_expired' $chip */
        $chip = (string) $request->validated('chip', 'all');
        $search = trim((string) $request->validated('q', ''));
        $selected = $request->validated('auditor');
        $staff = $access->handle($actorId);

        $resource = new StaffAuditorDirectoryResource([...$this->directory->directory($actorId, $chip, $search, self::LIMIT),
            'chip' => $chip, 'search' => $search, 'partner' => $selected === null ? null : $this->directory->partner($actorId, (string) $selected),
            'roles' => $staff['roles'], 'permissions' => $staff['allowed_actions']]);

        return $request->routeIs('api.*') ? $resource : Inertia::render('admin/parties', $resource->resolve($request));
    }

    /**
     * Verify or reject the waiting licence. The reviewer states the register check in their reason as
     * they decide, so the check is recorded at this moment and its reference is this decision.
     */
    public function decide(DecideAuditorLicenceRequest $request, RecordAuditorStanding $standing): RedirectResponse|OperationResource
    {
        $partyId = (string) $request->route('party');
        $requestId = (string) $request->validated('request_id');
        $result = $standing->handle((int) $request->user()?->getAuthIdentifier(), $partyId, (int) $request->validated('expected_revision'),
            (string) $request->route('decision'), (string) $request->validated('submission_id'), now('UTC')->format('Y-m-d\TH:i:s\Z'),
            'admin-console:'.$requestId, (string) $request->validated('reason'), $requestId);
        if ($request->routeIs('api.*')) {
            return OperationResource::fromResult($result, self::POLICY_VERSION);
        }
        if ($result['status'] !== 'completed') {
            throw ValidationException::withMessages(['reason' => [self::MESSAGES[$result['code']] ?? 'We could not record this decision. Try again.']]);
        }

        return redirect()->route('staff.auditors.index', ['auditor' => $partyId]);
    }
}
