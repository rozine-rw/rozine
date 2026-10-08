<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The designed Operations Center (`admin/today`) from live records. Every figure is read, never
 * estimated:
 *
 * - Total capital raised and the treasury's capital invested: the principal of every issued Holding;
 *   the chart buckets the same principal by its issue time.
 * - Active businesses: Business profiles under an active mandate in force now.
 * - Verified Investors and KYC awaiting review: the Investor directory's verified count and its
 *   submitted, not yet verified, identity submissions.
 * - Outstanding notes and the lifecycle: published campaigns live, funded and repaying (issued);
 *   failed ones closed unfunded or failed to close their disbursement; submitted is the
 *   applications queue.
 * - Applications pending and the pending list: the staff applications queue's pending tab.
 * - Disbursed: disbursements that closed with issued notes. The Payments badge counts those
 *   awaiting a second approver, for whoever may view disbursements.
 *
 * The platform does not record a treasury position, default rate, secondary volume, reconciliation
 * breaks, note health, late or at-risk notes, frozen accounts, sector exposure, a ledger feed or
 * collections yet, so those are sent as null and the console shows them as not tracked.
 *
 * @phpstan-type Bar array{label: string, amount: string, height_pct: int}
 * @phpstan-type Dashboard array{
 *     figures: array{capital_raised: string, active_businesses: int, awaiting_second_approver: int, disbursed: string,
 *         notes: array{live: int, funded: int, repaying: int, failed: int}},
 *     verified_investors: int, kyc_awaiting: int, applications_pending: int, pending: list<array<string, mixed>>,
 *     capital: array{from: string|null, to: string|null, grain: string, bars: list<Bar>}
 * }
 * @phpstan-type Page array{dashboard: Dashboard, roles: list<string>, permissions: list<string>}
 */
class StaffDashboardResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Page $page */
        $page = $this->resource;
        $board = $page['dashboard'];
        $figures = $board['figures'];
        $permissions = $page['permissions'];
        $prefix = $request->routeIs('api.*') ? 'api.v1.' : '';
        $nav = (new StaffNavigationResource($permissions))->resolve($request);
        $money = fn (string $amount): array => ['currency' => 'RWF', 'amount' => $amount];
        $count = fn (int $value): array => ['kind' => 'count', 'value' => $value];
        $notes = $figures['notes'];
        $reviews = in_array('applications.review', $permissions, true);
        $funnel = ['submitted' => $board['applications_pending'], 'live' => $notes['live'], 'funded' => $notes['funded'],
            'repaying' => $notes['repaying'], 'matured' => null, 'failed' => $notes['failed']];
        $widest = max([0, ...array_filter($funnel, fn (?int $stage): bool => $stage !== null)]);

        return ['contract_version' => 'staff-dashboard-v1', 'server_time' => now()->toIso8601String(),
            'viewer' => (new StaffViewerResource($page['roles']))->resolve($request), 'nav' => $nav,
            'badges' => ['applications' => $reviews ? $board['applications_pending'] : null,
                'disbursements' => in_array('disbursements.view', $permissions, true) ? $figures['awaiting_second_approver'] : null],
            'search' => '',
            'kpis' => [['key' => 'capital_raised', 'value' => ['kind' => 'money', 'value' => $money($figures['capital_raised'])]],
                ['key' => 'active_businesses', 'value' => $count($figures['active_businesses'])],
                ['key' => 'verified_investors', 'value' => $count($board['verified_investors'])],
                ['key' => 'outstanding_notes', 'value' => $count($notes['live'] + $notes['funded'] + $notes['repaying'])],
                ['key' => 'treasury_position', 'value' => null], ['key' => 'default_rate', 'value' => null], ['key' => 'secondary_volume', 'value' => null]],
            'attention' => [['key' => 'applications_pending', 'count' => $board['applications_pending'], 'link' => $nav['applications']],
                ['key' => 'kyc_awaiting', 'count' => $board['kyc_awaiting'], 'link' => $nav['investors'] === null ? null
                    : ['url' => route($prefix.'staff.investors.index', ['chip' => 'pending'], false), 'method' => 'get']],
                ['key' => 'notes_late', 'count' => null, 'link' => null], ['key' => 'notes_default_risk', 'count' => null, 'link' => null],
                ['key' => 'frozen_accounts', 'count' => null, 'link' => null]],
            'breaks' => null,
            'capital_raised' => [...$board['capital'], 'bars' => array_map(fn (array $bar): array => ['label' => $bar['label'],
                'amount' => $money($bar['amount']), 'height_pct' => $bar['height_pct']], $board['capital']['bars'])],
            'portfolio_health' => null, 'activity' => null,
            'funnel' => array_map(fn (string $stage, ?int $value): array => ['stage' => $stage, 'count' => $value,
                'width_pct' => $value === null || $widest === 0 ? 0 : intdiv($value * 100, $widest)], array_keys($funnel), $funnel),
            'pending_applications' => array_map(fn (array $entry): array => ['id' => (string) $entry['id'], 'business' => (string) $entry['business'],
                'requested' => $entry['requested'], 'rating' => $entry['rating'],
                'link' => $reviews ? ['url' => route($prefix.'staff.applications.index', ['application' => $entry['id']], false), 'method' => 'get'] : null],
                $board['pending']),
            'sector_exposure' => null,
            'treasury' => ['invested' => $money($figures['capital_raised']), 'disbursed' => $money($figures['disbursed']),
                'platform_net' => null, 'paid_to_investors' => null],
            'collections' => null];
    }
}
