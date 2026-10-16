<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Brick\Math\BigInteger;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `AdminReconciliationProps` (`staff-reconciliation-v1`) from the live reconciliation read. Both
 * provider clearing accounts carry the ledger balance, what the provider reported and their
 * difference (ledger minus statement). The provider's signed outcomes are the statement feed, so
 * it is always available. A break names what the provider reported and links to the Investor or
 * Business it belongs to when the viewer may open that directory; otherwise it links nowhere.
 * Nothing can be assigned or escalated yet, so no break has an owner.
 *
 * @phpstan-import-type Reconciliation from \App\Application\Operations\BrowseReconciliation
 */
class StaffReconciliationResource extends JsonResource
{
    private const array ACCOUNTS = ['deposits' => 'Deposit collections (provider clearing)', 'withdrawals' => 'Withdrawal payouts (provider clearing)'];

    private const array FLOWS = ['investor' => ['deposits' => 'Investor deposit', 'withdrawals' => 'Investor withdrawal'],
        'business' => ['deposits' => 'Business deposit', 'withdrawals' => 'Business withdrawal']];

    private const array KINDS = [
        'conflict' => ':flow reported successful after it had failed, so the ledger never posted it',
        'reversed' => ':flow reported failed after its success was posted to the ledger',
        'mismatch' => ':flow reported successful for another amount, currency or environment, so the ledger never posted it',
        'key_conflict' => ':flow event id reused by the provider with different content',
    ];

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array{reconciliation: Reconciliation, roles: list<string>, permissions: list<string>} $resource */
        $resource = $this->resource;
        $page = $resource['reconciliation'];
        $prefix = $request->routeIs('api.*') ? 'api.v1.' : '';
        $money = fn (string $amount): array => ['currency' => 'RWF', 'amount' => $amount];
        $asOf = $page['as_of']->toIso8601String();
        $investors = in_array('investors.verify', $resource['permissions'], true);
        $businesses = in_array('businesses.view', $resource['permissions'], true);

        return ['contract_version' => 'staff-reconciliation-v1', 'staff_access_version' => 'staff-access-v1', 'server_time' => now()->toIso8601String(),
            'allowed_actions' => [], 'viewer' => (new StaffViewerResource($resource['roles']))->resolve($request),
            'nav' => (new StaffNavigationResource($resource['permissions']))->resolve($request),
            'badges' => ['applications' => null, 'disbursements' => null], 'search' => '',
            'day_close' => $page['closed'] ? ['business_date' => $page['business_date'], 'state' => 'reconciled', 'reconciled_at' => $asOf]
                : ['business_date' => $page['business_date'], 'state' => 'open_break'],
            'accounts' => array_map(fn (array $account): array => ['id' => $account['id'], 'label' => self::ACCOUNTS[$account['id']], 'as_of' => $asOf,
                'ledger' => $money($account['ledger']), 'statement' => $money($account['statement']),
                'difference' => $money((string) BigInteger::of($account['ledger'])->minus($account['statement'])), 'feed' => ['state' => 'available']], $page['accounts']),
            'breaks' => array_map(fn (array $break): array => ['id' => $break['id'], 'reference' => $break['reference'], 'account_id' => $break['account_id'],
                'description' => self::describe($break),
                'gap' => $money($break['amount']), 'opened_at' => $break['opened_at'], 'age_days' => $break['age_days'], 'owner' => null,
                'link' => match (true) {
                    $break['party_id'] !== null && $investors => ['url' => route($prefix.'staff.investors.index', ['investor' => $break['party_id']], false), 'method' => 'get'],
                    $break['business_id'] !== null && $businesses => ['url' => route($prefix.'staff.businesses.index', ['business' => $break['business_id']], false), 'method' => 'get'],
                    default => null,
                }], $page['breaks'])];
    }

    /**
     * What the provider reported for a break, in the words the console shows.
     *
     * @param  array{account_id: 'deposits'|'withdrawals', flow: 'investor'|'business', kind: 'conflict'|'reversed'|'mismatch'|'key_conflict'}  $break
     */
    public static function describe(array $break): string
    {
        return strtr(self::KINDS[$break['kind']], [':flow' => self::FLOWS[$break['flow']][$break['account_id']]]);
    }
}
