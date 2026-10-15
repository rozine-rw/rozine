<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * `AdminExceptionsProps` (`staff-exceptions-v1`) from the live exceptions read. A variance is an
 * open reconciliation break on a provider clearing account, worded as Reconciliation words it and
 * opening that page; a halt is an Investor account case in force, opening the Investor where the
 * viewer may open that directory; an audit is a Flash Audit unfinished after its 24-hour clock (BRS
 * AUD-R07), opening the Business where the viewer may open that directory; a complaint is one past its
 * 4-hour acknowledgement or 48-hour resolution clock (BRS CMP-R03), opening it on Complaints where the
 * viewer may answer it. Arrears wait on the servicing schedule, so their chip counts
 * none. Nothing can be assigned yet, so no exception has an owner.
 *
 * The search matches the reference, the subject and the description; the chips count what the
 * search matches, and the kind filter and the limit then cut the list, oldest first.
 *
 * @phpstan-import-type Variance from \App\Application\Operations\BrowseExceptions
 * @phpstan-import-type Halt from \App\Application\Operations\BrowseExceptions
 * @phpstan-import-type Audit from \App\Application\Operations\BrowseExceptions
 * @phpstan-import-type LateComplaint from \App\Application\Operations\BrowseExceptions
 *
 * @phpstan-type Item array{
 *     id: string, kind: 'arrears'|'halt'|'variance'|'audit'|'complaint', reference: string, subject: array{kind: 'account'|'investor'|'business', label: string},
 *     note_title: null, description: string, amount: array{currency: string, amount: string}|null, dpd: null, opened_at: string, age_days: int,
 *     owner: null, link: array{url: string, method: string}|null
 * }
 */
class StaffExceptionsResource extends JsonResource
{
    private const array KINDS = ['arrears', 'halt', 'variance', 'audit', 'complaint'];

    private const array ACCOUNTS = ['deposits' => 'Deposit collections (provider clearing)', 'withdrawals' => 'Withdrawal payouts (provider clearing)'];

    private const array SCOPES = ['deposits' => 'deposits', 'withdrawals' => 'withdrawals', 'primary_commitments' => 'new purchases',
        'secondary_trading' => 'secondary trading'];

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array{variances: list<Variance>, halts: list<Halt>, audits: list<Audit>, complaints: list<LateComplaint>, search: string, kind: string|null, limit: int, roles: list<string>, permissions: list<string>} $resource */
        $resource = $this->resource;
        $prefix = $request->routeIs('api.*') ? 'api.v1.' : '';
        $route = fn (string $name, array $query = []): array => ['url' => route($prefix.$name, $query, false), 'method' => 'get'];
        $reconciliation = in_array('reconciliation.view', $resource['permissions'], true);
        $investors = in_array('investors.verify', $resource['permissions'], true);
        $businesses = in_array('businesses.view', $resource['permissions'], true);
        $complaints = in_array('complaints.answer', $resource['permissions'], true);

        $items = [];
        foreach ($resource['variances'] as $break) {
            $items[] = ['id' => $break['id'], 'kind' => 'variance', 'reference' => $break['reference'],
                'subject' => ['kind' => 'account', 'label' => self::ACCOUNTS[$break['account_id']]], 'note_title' => null,
                'description' => StaffReconciliationResource::describe($break), 'amount' => ['currency' => 'RWF', 'amount' => $break['amount']], 'dpd' => null,
                'opened_at' => $break['opened_at'], 'age_days' => $break['age_days'], 'owner' => null,
                'link' => $reconciliation ? $route('staff.reconciliation.index') : null];
        }
        foreach ($resource['halts'] as $hold) {
            $items[] = ['id' => $hold['id'], 'kind' => 'halt', 'reference' => 'HLD-'.Str::upper(Str::substr($hold['id'], -6)),
                'subject' => ['kind' => 'investor', 'label' => $hold['name'] ?? 'Investor without an account'], 'note_title' => null,
                'description' => self::hold($hold), 'amount' => null, 'dpd' => null, 'opened_at' => $hold['effective_at'], 'age_days' => $hold['age_days'],
                'owner' => null, 'link' => $investors ? $route('staff.investors.index', ['investor' => $hold['party_id']]) : null];
        }
        foreach ($resource['audits'] as $audit) {
            $items[] = ['id' => $audit['id'], 'kind' => 'audit', 'reference' => 'AUD-'.Str::upper(Str::substr($audit['id'], -6)),
                'subject' => ['kind' => 'business', 'label' => $audit['business']], 'note_title' => null,
                'description' => 'Flash audit unfinished after 24 hours: '.match ($audit['status']) {
                    'offered' => 'offered to a partner, not yet accepted',
                    'accepted' => 'with '.($audit['partner'] ?? 'its partner').', report not sealed',
                    'operations' => 'no partner found, with Audit Operations',
                }, 'amount' => null, 'dpd' => null, 'opened_at' => $audit['due_at'], 'age_days' => $audit['age_days'],
                'owner' => null, 'link' => $businesses ? $route('staff.businesses.index', ['business' => $audit['business_id']]) : null];
        }
        foreach ($resource['complaints'] as $complaint) {
            $items[] = ['id' => $complaint['id'], 'kind' => 'complaint', 'reference' => $complaint['reference'],
                'subject' => ['kind' => $complaint['role'], 'label' => $complaint['customer']], 'note_title' => null,
                'description' => $complaint['stage'] === 'acknowledge' ? 'Complaint not acknowledged within 4 hours'
                    : 'Complaint acknowledged but not resolved within 48 hours', 'amount' => null, 'dpd' => null, 'opened_at' => $complaint['due_at'],
                'age_days' => $complaint['age_days'], 'owner' => null,
                'link' => $complaints ? $route('staff.complaints.index', ['complaint' => $complaint['id']]) : null];
        }
        usort($items, fn (array $a, array $b): int => [$a['opened_at'], $a['id']] <=> [$b['opened_at'], $b['id']]);

        $search = $resource['search'];
        $matched = $search === '' ? $items : array_values(array_filter($items, fn (array $item): bool => Str::contains(
            $item['reference'].' '.$item['subject']['label'].' '.$item['description'], $search, true)));
        $kind = $resource['kind'];
        $listed = $kind === null ? $matched : array_values(array_filter($matched, fn (array $item): bool => $item['kind'] === $kind));
        $query = array_filter(['q' => $search === '' ? null : $search, 'kind' => $kind]);

        return ['contract_version' => 'staff-exceptions-v1', 'staff_access_version' => 'staff-access-v1', 'server_time' => now()->toIso8601String(),
            'allowed_actions' => [], 'viewer' => (new StaffViewerResource($resource['roles']))->resolve($request),
            'nav' => (new StaffNavigationResource($resource['permissions']))->resolve($request),
            'badges' => ['applications' => null, 'disbursements' => null], 'search' => $search,
            'counts' => ['open' => count($matched), 'unassigned' => count($matched)],
            'chips' => [['key' => 'all', 'count' => count($matched), 'link' => $route('staff.exceptions.index', array_diff_key($query, ['kind' => true])), 'active' => $kind === null],
                ...array_map(fn (string $key): array => ['key' => $key, 'count' => count(array_filter($matched, fn (array $item): bool => $item['kind'] === $key)),
                    'link' => $route('staff.exceptions.index', [...$query, 'kind' => $key]), 'active' => $kind === $key], self::KINDS)],
            'exceptions' => array_slice($listed, 0, $resource['limit']),
            'pagination' => ['next' => count($listed) > $resource['limit'] && $resource['limit'] < 500 ? $route('staff.exceptions.index', [...$query, 'limit' => 500]) : null]];
    }

    /**
     * What an account case holds, in the words the console shows.
     *
     * @param  Halt  $hold
     */
    private static function hold(array $hold): string
    {
        $paused = implode(', ', array_map(fn (string $scope): string => self::SCOPES[$scope] ?? Str::headline($scope), $hold['scope']));
        $until = $hold['expires_at'] === null ? '' : ' until '.CarbonImmutable::parse($hold['expires_at'])->format('j M Y');

        return match ($hold['cause']) {
            'high_risk_hold' => 'Account on high-risk hold',
            'external_order' => 'Account restricted by an external order',
            default => 'Account case '.Str::headline($hold['cause']),
        }.': '.$paused.' paused'.$until;
    }
}
