<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\BrowseInvestorDirectory;
use App\Application\Identity\GetStaffAccess;
use App\Application\Identity\ReviewInvestorVerifications;
use App\Http\Requests\Staff\ListInvestorDirectoryRequest;
use App\Http\Resources\StaffInvestorDirectoryResource;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The designed Investor directory (`investors.verify`): every Investor with their KYC state and
 * money, and one person's 360 with their identity submission, where Compliance views the documents
 * and approves or rejects. A verification case link opens the person it belongs to.
 */
class StaffInvestorDirectoryController extends Controller
{
    /** The design's directory shows the first sixty matches and says how many more there are. */
    private const LIMIT = 60;

    public function __construct(private BrowseInvestorDirectory $directory, private ReviewInvestorVerifications $review) {}

    public function index(ListInvestorDirectoryRequest $request, GetStaffAccess $access): Response|StaffInvestorDirectoryResource
    {
        $actorId = (int) $request->user()?->getAuthIdentifier();
        /** @var 'all'|'verified'|'pending'|'kyc_overdue'|'frozen'|'restricted' $chip */
        $chip = (string) $request->validated('chip', 'all');
        /** @var 'portfolio'|'name' $sort */
        $sort = (string) $request->validated('sort', 'portfolio');
        $search = trim((string) $request->validated('q', ''));
        $verification = $request->validated('verification');
        $selected = $verification === null ? $request->validated('investor') : $this->directory->partyForVerification($actorId, (string) $verification);
        $party = $selected === null ? null : $this->directory->party($actorId, (string) $selected);
        $caseId = $party['row']['verification_id'] ?? null;
        $staff = $access->handle($actorId);

        $resource = new StaffInvestorDirectoryResource([...$this->directory->directory($actorId, $chip, $sort, $search, self::LIMIT),
            'chip' => $chip, 'sort' => $sort, 'search' => $search, 'party' => $party,
            'review' => $caseId === null ? null : $this->review->show($actorId, $caseId),
            'roles' => $staff['roles'], 'permissions' => $staff['allowed_actions']]);

        return $request->routeIs('api.*') ? $resource : Inertia::render('admin/parties', $resource->resolve($request));
    }
}
