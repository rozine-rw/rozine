<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\BrowseUserDirectory;
use App\Application\Identity\Contracts\UserDirectoryStore;
use App\Application\Identity\GetStaffAccess;
use App\Application\Identity\ReviewInvestorVerifications;
use App\Http\Requests\Staff\ListUserDirectoryRequest;
use App\Http\Resources\StaffInvestorVerificationsResource;
use App\Http\Resources\StaffNavigationResource;
use App\Http\Resources\StaffViewerResource;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Users (Admin console, Accounts): every sign-in account on the platform — Investors, Businesses,
 * Audit Partners, staff, and people who have not taken a role yet — with the roles each holds, their
 * KYC state, sign-up date and last activity. A row opens the account's detail in the console drawer,
 * with its roles, identity, sign-in security, access requests and identity history, and links to
 * the role directories that act on it. Read-only: every change still happens in those directories.
 * The Deleted accounts tab lists accounts their holders closed (soft deleted), with the closing date
 * and the reason they gave; their records stay, and they can no longer sign in.
 *
 * @phpstan-import-type DirectoryRow from UserDirectoryStore
 * @phpstan-import-type Detail from UserDirectoryStore
 */
class StaffUserDirectoryController extends Controller
{
    /** Like the other directories, the page shows the first sixty matches and says how many more there are. */
    private const LIMIT = 60;

    /** Institutions are accounts that applied to invest for an institution, whatever its status (27 Nov 2026). */
    private const ROLES = ['all', 'investor', 'institution', 'business', 'auditor', 'staff', 'no_role'];

    private const KYC = ['any', 'verified', 'in_review', 'in_progress', 'rejected', 'not_started'];

    private const SORTS = ['newest', 'oldest', 'name'];

    public function __construct(private BrowseUserDirectory $directory) {}

    public function index(ListUserDirectoryRequest $request, GetStaffAccess $access, ReviewInvestorVerifications $reviews): Response
    {
        $actorId = (int) $request->user()?->getAuthIdentifier();
        /** @var 'all'|'investor'|'institution'|'business'|'auditor'|'staff'|'no_role' $role */
        $role = (string) $request->validated('role', 'all');
        /** @var 'any'|'verified'|'in_review'|'in_progress'|'rejected'|'not_started' $kyc */
        $kyc = (string) $request->validated('kyc', 'any');
        /** @var 'newest'|'oldest'|'name' $sort */
        $sort = (string) $request->validated('sort', 'newest');
        $search = trim((string) $request->validated('q', ''));
        $deleted = $request->validated('status') === 'deleted';
        $selected = $request->validated('user');
        $page = $this->directory->directory($actorId, $role, $kyc, $sort, $search, self::LIMIT, $deleted);
        $user = $selected === null ? null : $this->directory->user($actorId, (int) $selected);
        $staff = $access->handle($actorId);

        $link = fn (array $query): array => ['url' => route('staff.users.index', $query, false), 'method' => 'get'];
        $filters = array_filter(['status' => $deleted ? 'deleted' : '', 'q' => $search, 'kyc' => $kyc === 'any' ? '' : $kyc, 'sort' => $sort === 'newest' ? '' : $sort],
            fn (string $value): bool => $value !== '');
        $position = array_filter([...$filters, 'role' => $role === 'all' ? '' : $role], fn (string $value): bool => $value !== '');
        $counts = $page['counts'];
        $nav = (new StaffNavigationResource($staff['allowed_actions']))->resolve($request);

        return Inertia::render('admin/users', ['contract_version' => 'staff-user-directory-v1', 'server_time' => now()->toIso8601String(),
            'viewer' => (new StaffViewerResource($staff['roles']))->resolve($request), 'nav' => $nav,
            'badges' => ['applications' => null, 'disbursements' => null], 'search' => $search,
            'tabs' => [['key' => 'active', 'count' => $page['everyone'], 'link' => $link([]), 'active' => ! $deleted],
                ['key' => 'deleted', 'count' => $page['closed'], 'link' => $link(['status' => 'deleted']), 'active' => $deleted]],
            'stats' => [['key' => 'total_users', 'value' => ['kind' => 'count', 'value' => $page['everyone']]],
                ['key' => 'new_this_week', 'value' => ['kind' => 'count', 'value' => $page['new_this_week']]],
                ['key' => 'kyc_verified', 'value' => ['kind' => 'percent', 'value' => (string) ($page['everyone'] === 0 ? 0 : intdiv($page['kyc_verified'] * 100, $page['everyone']))]],
                ['key' => 'awaiting_kyc', 'value' => ['kind' => 'count', 'value' => $page['awaiting_review']]]],
            'chips' => array_map(fn (string $chip): array => ['key' => $chip, 'count' => $counts[$chip],
                'link' => $link([...$filters, ...($chip === 'all' ? [] : ['role' => $chip])]), 'active' => $chip === $role], self::ROLES),
            'filters' => [['key' => 'kyc', 'value' => $kyc, 'options' => self::KYC], ['key' => 'sort', 'value' => $sort, 'options' => self::SORTS]],
            'shown' => count($page['rows']), 'total' => $page['matching'],
            'rows' => array_map(fn (array $row): array => [...self::row($row), 'link' => $link([...$position, 'user' => $row['id']])], $page['rows']),
            'user' => $user === null ? null : self::detail($user, $link($position), $nav),
            // Everyone's own KYC is approved here, in the account's drawer (Erastus, 23 Nov 2026).
            'kyc_review' => $user !== null && $user['verification_id'] !== null && in_array('investors.verify', $staff['allowed_actions'], true)
                ? StaffInvestorVerificationsResource::review($reviews->show($actorId, $user['verification_id']), $link([...$position, 'user' => $user['row']['id']]), '') : null]);
    }

    /**
     * @param  DirectoryRow  $row
     * @return array<string, mixed>
     */
    private static function row(array $row): array
    {
        return ['id' => (string) $row['id'], 'name' => $row['name'], 'email' => $row['email'], 'email_verified' => $row['email_verified'],
            'signed_up_at' => $row['signed_up_at'], 'last_seen_at' => $row['last_seen_at'], 'memberships' => $row['memberships'],
            'staff' => $row['staff'], 'requested' => $row['requested'], 'institution' => $row['institution'], 'kyc' => $row['kyc'], 'restricted' => $row['restricted'],
            'closed_at' => $row['closed_at'], 'closure_reason' => $row['closure_reason']];
    }

    /**
     * The account's drawer, with links to the directories that act on each of its roles, where the
     * viewer may open them.
     *
     * @param  Detail  $user
     * @param  array{url: string, method: string}  $close
     * @param  array<string, array{url: string, method: string}|null>  $nav
     * @return array<string, mixed>
     */
    private static function detail(array $user, array $close, array $nav): array
    {
        $row = $user['row'];
        $roles = array_column($row['memberships'], 'role');
        $directory = fn (string $route, array $query = []): array => ['url' => route($route, $query, false), 'method' => 'get'];

        return [...self::row($row), 'email_verified_at' => $user['email_verified_at'], 'two_factor' => $user['two_factor'], 'passkeys' => $user['passkeys'],
            'submitted_at' => $user['submitted_at'], 'requests' => $user['requests'], 'history' => $user['history'],
            'links' => ['close' => $close,
                'investor' => in_array('investor', $roles, true) || $user['verification_id'] !== null
                    ? $directory('staff.investors.index', $user['verification_id'] === null ? ['investor' => $row['party_id']] : ['verification' => $user['verification_id']]) : null,
                'business' => in_array('business', $roles, true) && $nav['businesses'] !== null ? $nav['businesses'] : null,
                'auditor' => in_array('auditor', $roles, true) && $nav['auditors'] !== null ? $nav['auditors'] : null,
                'staff' => $row['staff'] !== null ? $nav['staff'] : null,
                'access_requests' => $user['requests'] !== [] ? $nav['access_requests'] : null]];
    }
}
