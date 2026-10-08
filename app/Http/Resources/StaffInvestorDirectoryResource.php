<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The designed Investor directory (`admin/parties`, kind investor) from live records: the four
 * figures, the chips with their counts, the first sixty matches and one person's 360. Fields the
 * platform does not record yet are not invented: no country is captured at sign-up, and no
 * Investor can be frozen or fall KYC-overdue, so those read empty or zero. The person's identity
 * submission rides on the 360 with the same documents and decisions as the review queue. The 360
 * counts holdings but lists none until the S3-C adapter exposes them.
 *
 * @phpstan-import-type DirectoryRow from \App\Application\Identity\Contracts\InvestorDirectoryStore
 * @phpstan-import-type Counts from \App\Application\Identity\Contracts\InvestorDirectoryStore
 * @phpstan-import-type Detail from \App\Application\Identity\Contracts\InvestorDirectoryStore
 * @phpstan-import-type Review from \App\Application\Identity\Contracts\InvestorVerificationReviewStore
 *
 * @phpstan-type Page array{
 *     rows: list<DirectoryRow>, matching: int, counts: Counts, awaiting_review: int, aum: string, chip: string, sort: string,
 *     search: string, party: Detail|null, review: Review|null, roles: list<string>, permissions: list<string>
 * }
 */
class StaffInvestorDirectoryResource extends JsonResource
{
    private const CHIPS = ['all', 'verified', 'pending', 'kyc_overdue', 'frozen', 'restricted'];

    /** How each identity command reads in the 360's activity. */
    private const COMMANDS = [
        'verification.save' => ['Saved a step', 'grey'], 'verification.upload' => ['Uploaded a document', 'grey'],
        'verification.submit' => ['Submitted for review', 'blue'], 'verification.approve' => ['Approved by Compliance', 'green'],
        'verification.reject' => ['Rejected by Compliance', 'red'],
    ];

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Page $page */
        $page = $this->resource;
        $prefix = $request->routeIs('api.*') ? 'api.v1.' : '';
        $link = fn (array $query): array => ['url' => route($prefix.'staff.investors.index', $query, false), 'method' => 'get'];
        $money = fn (string $amount): array => ['currency' => 'RWF', 'amount' => $amount];
        $filters = array_filter(['q' => $page['search'], 'sort' => $page['sort'] === 'portfolio' ? '' : $page['sort']], fn (string $value): bool => $value !== '');
        $position = array_filter([...$filters, 'chip' => $page['chip'] === 'all' ? '' : $page['chip']], fn (string $value): bool => $value !== '');
        $counts = $page['counts'];
        $name = (string) $request->user()?->name;
        // Only compliance and superadmin hold investors.verify, so the directory is never shown to another role.
        $role = in_array('superadmin', $page['roles'], true) ? 'superadmin' : 'compliance';

        return ['contract_version' => 'staff-investor-directory-v1', 'server_time' => now()->toIso8601String(),
            'viewer' => ['id' => (string) $request->user()?->getAuthIdentifier(), 'name' => $name, 'email' => (string) $request->user()?->email,
                'initials' => mb_strtoupper(mb_substr($name, 0, 1)), 'role' => $role],
            'nav' => (new StaffNavigationResource($page['permissions']))->resolve($request),
            'badges' => ['applications' => null, 'disbursements' => null], 'search' => $page['search'], 'kind' => 'investor', 'policy' => [],
            'stats' => [['key' => 'total_investors', 'value' => ['kind' => 'count', 'value' => $counts['all']]],
                ['key' => 'kyc_verified', 'value' => ['kind' => 'percent', 'value' => (string) ($counts['all'] === 0 ? 0 : intdiv($counts['verified'] * 100, $counts['all']))]],
                ['key' => 'awaiting_kyc', 'value' => ['kind' => 'count', 'value' => $page['awaiting_review']]],
                ['key' => 'total_aum', 'value' => ['kind' => 'money', 'value' => $money($page['aum'])]]],
            'chips' => array_map(fn (string $chip): array => ['key' => $chip, 'count' => $counts[$chip], 'link' => $link([...$filters, ...($chip === 'all' ? [] : ['chip' => $chip])]),
                'active' => $chip === $page['chip']], self::CHIPS),
            'filters' => [['key' => 'sort', 'value' => $page['sort'], 'options' => [['value' => 'portfolio', 'label' => 'Sort: Portfolio value'], ['value' => 'name', 'label' => 'Sort: Name']]]],
            'shown' => count($page['rows']), 'total' => $page['matching'],
            'directory' => ['kind' => 'investor', 'rows' => array_map(fn (array $row): array => ['id' => $row['party_id'], 'name' => $row['name'], 'country' => '',
                'kyc' => $row['kyc'], 'portfolio' => $money($row['portfolio']), 'wallet' => $money($row['wallet']), 'holdings' => $row['holdings'],
                'businesses' => $row['businesses'], 'frozen' => false, 'restricted' => $row['restricted'], 'link' => $link([...$position, 'investor' => $row['party_id']])], $page['rows'])],
            'party' => $page['party'] === null ? null : self::party($page['party'], $page['review'], $link($position), $prefix)];
    }

    /**
     * @param  Detail  $party
     * @param  Review|null  $review
     * @param  array{url: string, method: string}  $close
     * @return array<string, mixed>
     */
    private static function party(array $party, ?array $review, array $close, string $prefix): array
    {
        $row = $party['row'];
        $money = fn (string $amount): array => ['kind' => 'money', 'value' => ['currency' => 'RWF', 'amount' => $amount]];

        return ['id' => $row['party_id'], 'kind' => 'investor', 'name' => $row['name'], 'subtitle' => $row['email'],
            'health' => $row['kyc'] === 'verified' ? 'active' : 'kyc_pending',
            'stats' => [['key' => 'portfolio', 'value' => $money($row['portfolio'])], ['key' => 'wallet', 'value' => $money($row['wallet'])],
                ['key' => 'holdings', 'value' => ['kind' => 'count', 'value' => $row['holdings']]], ['key' => 'businesses', 'value' => ['kind' => 'count', 'value' => $row['businesses']]]],
            // Holdings are counted above but not listed until the S3-C adapter exposes them.
            'list' => null,
            'history' => array_map(fn (array $entry): array => ['id' => $entry['id'], 'at' => $entry['at'], 'actor' => $entry['actor'],
                'action' => ['code' => $entry['command'], 'label' => self::COMMANDS[$entry['command']][0] ?? $entry['command'], 'tone' => self::COMMANDS[$entry['command']][1] ?? 'grey'],
                'reason' => $entry['reason']], $party['history']),
            'kyc' => ['state' => $row['kyc'], 'due_on' => null], 'licence' => null, 'freeze' => null, 'release_blocked' => null, 'restrictions' => [],
            'restricted_since' => $party['restricted_since'], 'links' => ['close' => $close], 'actions' => (object) [],
            'verification' => $review === null ? null : StaffInvestorVerificationsResource::review($review, $close, $prefix)];
    }
}
