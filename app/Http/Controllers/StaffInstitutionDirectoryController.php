<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\BrowseInstitutionDirectory;
use App\Application\Identity\Contracts\InstitutionDirectoryStore;
use App\Application\Identity\Contracts\RoleApplicationStore;
use App\Application\Identity\GetStaffAccess;
use App\Application\Identity\ReviewRoleApplications;
use App\Http\Requests\Staff\ListInstitutionDirectoryRequest;
use App\Http\Resources\StaffNavigationResource;
use App\Http\Resources\StaffViewerResource;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Institutions (Admin console, Accounts; Erastus, 27 Nov 2026): every pension fund, VC or other institution
 * that applied to invest through Rozine, with its review status, monthly commitment, representative and money.
 * A row opens the institution's 360: its KYB (company, people, documents), the representative's account, the
 * money it holds and has invested, its share of each raise against its cap, Auto-Deploy, and its history. The
 * application itself is decided in the same review drawer as on Businesses, opened from the 360.
 *
 * @phpstan-import-type DirectoryRow from InstitutionDirectoryStore
 * @phpstan-import-type Detail from InstitutionDirectoryStore
 * @phpstan-import-type Review from RoleApplicationStore
 */
class StaffInstitutionDirectoryController extends Controller
{
    /** Like the other directories, the page shows the first sixty matches and says how many more there are. */
    private const LIMIT = 60;

    private const CHIPS = ['all', 'approved', 'submitted', 'changes_requested', 'draft', 'rejected'];

    public function __construct(private BrowseInstitutionDirectory $directory, private ReviewRoleApplications $reviews) {}

    public function index(ListInstitutionDirectoryRequest $request, GetStaffAccess $access): Response
    {
        $actorId = (int) $request->user()?->getAuthIdentifier();
        /** @var 'all'|'approved'|'submitted'|'changes_requested'|'draft'|'rejected' $chip */
        $chip = (string) $request->validated('status', 'all');
        $search = trim((string) $request->validated('q', ''));
        $page = $this->directory->directory($actorId, $chip, $search, self::LIMIT);
        $staff = $access->handle($actorId);
        $nav = (new StaffNavigationResource($staff['allowed_actions']))->resolve($request);

        $link = fn (array $query): array => ['url' => route('staff.institutions.index', $query, false), 'method' => 'get'];
        $filters = array_filter(['q' => $search, 'status' => $chip === 'all' ? '' : $chip], fn (string $value): bool => $value !== '');
        $selected = (string) $request->validated('institution', '');
        $reviewing = (string) $request->validated('application', '');
        $institution = $selected === '' ? null : $this->directory->institution($actorId, $selected);
        $review = $institution === null ? null : $this->reviews->show($actorId, $selected);
        $counts = $page['counts'];

        return Inertia::render('admin/institutions', ['contract_version' => 'staff-institution-directory-v1', 'server_time' => now()->toIso8601String(),
            'viewer' => (new StaffViewerResource($staff['roles']))->resolve($request), 'nav' => $nav,
            'badges' => ['applications' => null, 'disbursements' => null], 'search' => $search,
            'stats' => [['key' => 'institutions', 'value' => ['kind' => 'count', 'value' => $counts['all']]],
                ['key' => 'awaiting_review', 'value' => ['kind' => 'count', 'value' => $counts['submitted']]],
                ['key' => 'committed_monthly', 'value' => ['kind' => 'money', 'value' => ['currency' => 'RWF', 'amount' => $page['committed_monthly']]]],
                ['key' => 'invested', 'value' => ['kind' => 'money', 'value' => ['currency' => 'RWF', 'amount' => $page['invested']]]]],
            'chips' => array_map(fn (string $key): array => ['key' => $key, 'count' => $counts[$key],
                'link' => $link(array_filter(['q' => $search, 'status' => $key === 'all' ? '' : $key], fn (string $value): bool => $value !== '')),
                'active' => $key === $chip], self::CHIPS),
            'shown' => count($page['rows']), 'total' => $page['matching'],
            'rows' => array_map(fn (array $row): array => [...self::row($row), 'link' => $link([...$filters, 'institution' => $row['application_id']])], $page['rows']),
            'institution' => $institution === null || $review === null ? null
                : self::detail($institution, $review, $link($filters), $link([...$filters, 'institution' => $selected, 'application' => $selected]), $nav),
            // The review drawer, opened from the 360, closes back to it.
            'application' => $review !== null && $reviewing === $selected ? $review : null,
            'applications_close' => $link([...$filters, 'institution' => $selected])['url']]);
    }

    /**
     * @param  DirectoryRow  $row
     * @return array<string, mixed>
     */
    private static function row(array $row): array
    {
        return ['id' => $row['application_id'], 'party_id' => $row['party_id'], 'status' => $row['status'], 'name' => $row['legal_name'],
            'rdb_code' => $row['rdb_code'], 'type' => $row['institution_type'],
            'monthly_commitment' => ['currency' => 'RWF', 'amount' => $row['monthly_commitment']],
            'representative' => $row['representative'], 'email' => $row['email'],
            'wallet' => ['currency' => 'RWF', 'amount' => $row['wallet']], 'invested' => ['currency' => 'RWF', 'amount' => $row['invested']],
            'holdings' => $row['holdings'], 'submitted_at' => $row['submitted_at'], 'approved_at' => $row['approved_at']];
    }

    /**
     * The institution's 360: its directory row, the KYB answers and documents the review drawer reads,
     * its money and raises, and links to the review drawer, the account and the Investor 360.
     *
     * @param  Detail  $institution
     * @param  Review  $review
     * @param  array{url: string, method: string}  $close
     * @param  array{url: string, method: string}  $open
     * @param  array<string, array{url: string, method: string}|null>  $nav
     * @return array<string, mixed>
     */
    private static function detail(array $institution, array $review, array $close, array $open, array $nav): array
    {
        $row = $institution['row'];
        $answers = $review['state']['answers'];
        $money = fn (string $amount): array => ['currency' => 'RWF', 'amount' => $amount];
        $application = $row['application_id'];

        return [...self::row($row), 'approved_by' => $institution['approved_by'], 'decided_at' => $review['decided_at'],
            'cap_percent' => $institution['cap_percent'], 'committed' => $money($institution['committed']),
            'company' => $answers['company'] ?? null, 'representative_role' => $answers['representative'] ?? null,
            'account_details' => $answers['institution'] ?? null,
            'directors' => $answers['directors'] ?? [], 'owners' => $answers['owners'] ?? [],
            'no_major_owner' => (bool) ($answers['no_major_owner'] ?? false),
            'account' => ['user_id' => $row['user_id'], 'name' => $review['account']['name'], 'email' => $review['account']['email'],
                'two_factor' => $review['account']['two_factor'], 'kyc' => $review['kyc']],
            'documents' => array_values(array_map(fn (array $document): array => [...$document,
                'link' => ['url' => route('staff.role-applications.document', ['application' => $application, 'document' => $document['id'], 'disposition' => 'inline'], false), 'method' => 'get']],
                array_filter($review['documents'], fn (array $document): bool => $document['current']))),
            'holdings' => array_map(fn (array $holding): array => [...$holding, 'principal' => $money($holding['principal'])], $institution['holdings']),
            'raises' => array_map(fn (array $raise): array => [...$raise, 'amount' => $money($raise['amount']), 'raise_amount' => $money($raise['raise_amount'])], $institution['raises']),
            // Auto-Deploy is not built yet: the fund's rules, its cap per commitment and the automatic commitments come next.
            'auto_deploy' => ['enabled' => false],
            // Newest first, as the console's other histories read.
            'history' => array_reverse($review['history']),
            'links' => ['close' => $close, 'review' => $open,
                'user' => $row['user_id'] !== null && $nav['users'] !== null ? ['url' => route('staff.users.index', ['user' => $row['user_id']], false), 'method' => 'get'] : null,
                'investor' => $nav['investors'] !== null ? ['url' => route('staff.investors.index', ['investor' => $row['party_id']], false), 'method' => 'get'] : null]];
    }
}
