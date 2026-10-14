<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `C3InvestorHoldingProps` (investor-primary-v1) from `GetInvestorHolding`. The issue block is
 * the Holding as the primary issue recorded it, with its schedule's Kigali due dates and the
 * `HOLDING_ISSUED` receipt, which names the disbursement operation that issued it. Its note is
 * the campaign the Holding was issued from. Servicing facts keep their C3 empty states. Portfolio,
 * Market, Cart and Profile are web pages only, so the API carries their links as null. `links.changes`
 * is the change beacon for the Investor's purchases.
 *
 * @phpstan-import-type IssuedHoldingRecord from \App\Application\Primary\Contracts\InvestorHoldings
 */
class InvestorHoldingResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array{identity_context_revision: int, verified: bool, holding: array<string, mixed>, issue: IssuedHoldingRecord, changes_cursor?: string} $data */
        $data = $this->resource;
        $issue = $data['issue'];
        $prefix = $request->routeIs('api.*') ? 'api.v1.' : '';
        $page = fn (string $name): ?array => $prefix === '' ? self::link($name) : null;
        $self = self::link($prefix.'investor.holdings.show', ['holding' => $issue['id']]);
        $terms = $issue['terms'];

        return ['contract_version' => 'investor-primary-v1', 'identity_context_revision' => $data['identity_context_revision'], 'server_time' => now()->toIso8601String(),
            'allowed_actions' => [],
            'holding' => [...$data['holding'], 'link' => $self, 'on_time' => ['made' => 0, 'on_time' => 0, 'late' => 0], 'rating_change' => null,
                'recovery_plan' => null, 'arrears' => null, 'photos' => [],
                'issue' => ['id' => $issue['id'], 'revision' => 1, 'note_id' => $issue['campaign_id'], 'campaign_id' => $issue['campaign_id'],
                    'units' => $issue['units'], 'ordinals' => $issue['ordinals'], 'principal' => $issue['principal'], 'rights' => $issue['rights'],
                    'issued_at' => $issue['issued_at'], 'disbursement_effective_at' => $issue['disbursement_effective_at'], 'effective_date' => $issue['effective_date'],
                    'terms' => ['rate_pct' => $terms['rate_pct'], 'term_months' => $terms['term_months'], 'total_return' => $issue['rights']['total_return'],
                        'earnings_fee' => $terms['earnings_fee'], 'policy_version' => $terms['policy_version'], 'disclosure_version' => $terms['disclosure_version']],
                    'schedule' => $issue['schedule'],
                    'issue_receipt' => ['receipt_id' => $issue['receipt']['receipt_id'], 'operation_id' => $issue['receipt']['operation_id'],
                        'request_id' => $issue['receipt']['request_id'], 'code' => 'HOLDING_ISSUED', 'recorded_at' => $issue['issued_at'],
                        'amount' => $issue['principal'], 'units' => $issue['units'], 'reference' => 'RZH-'.strtoupper(substr($issue['receipt']['receipt_id'], -10)),
                        'revision' => 1, 'policy_version' => $terms['policy_version'], 'disclosure_version' => $terms['disclosure_version'], 'link' => $self],
                    'source' => ['commitment_id' => $issue['commitment_id'], 'disbursement_operation_id' => $issue['receipt']['operation_id']]]],
            'links' => ['deals' => self::link($prefix.'investor.deals'), 'portfolio' => $page('investor.portfolio'), 'market' => $page('investor.market'),
                'cart' => $page('investor.cart'), 'profile' => $page('investor.profile'), 'wallet' => self::link($prefix.'investor.wallet'), 'notifications' => null,
                'launcher' => self::link($prefix === '' ? 'dashboard' : 'api.v1.identity.show'), 'back' => $page('investor.portfolio'),
                'changes' => isset($data['changes_cursor']) ? self::link($prefix.'changes.index', ['topics' => 'purchase', 'after' => $data['changes_cursor']]) : null]];
    }

    /**
     * @param  array<string, string>  $query
     * @return array{url: string, method: 'get'}
     */
    private static function link(string $name, array $query = []): array
    {
        return ['url' => route($name, $query, false), 'method' => 'get'];
    }
}
