<?php

declare(strict_types=1);

namespace App\Infrastructure\Primary;

use App\Application\Primary\Contracts\InvestorCheckoutFacts;
use App\Models\User;
use Brick\Math\BigInteger;
use Illuminate\Support\Facades\DB;

/**
 * Active capital from the sealed ledger's committed bucket plus issued Holdings, as the Investor
 * directory reads it, and a Party's campaign units from the latest version of each reservation.
 */
final readonly class RetainedInvestorCheckoutFacts implements InvestorCheckoutFacts
{
    public function activeCapital(string $partyId): string
    {
        $row = (array) DB::selectOne("SELECT
            coalesce((SELECT sum(CASE WHEN ll.direction = 'credit' THEN ll.amount ELSE -ll.amount END)
                FROM ledger_lines ll JOIN ledger_accounts la ON la.id = ll.account_id JOIN investor_wallets w ON w.id = la.wallet_id
                WHERE w.party_id = ? AND la.kind = 'investor_committed'), 0)::text AS committed,
            coalesce((SELECT sum(h.principal) FROM primary_holdings h WHERE h.party_id = ?), 0)::text AS issued", [$partyId, $partyId]);

        return (string) BigInteger::of((string) $row['committed'])->plus((string) $row['issued']);
    }

    public function forInvestor(int $userId, string $campaignId): array
    {
        $partyId = (string) User::query()->whereKey($userId)->value('party_id');
        $row = (array) DB::selectOne("SELECT coalesce(sum(r.units), 0)::text AS units FROM primary_reservations r
            WHERE r.business_campaign_id = ? AND r.party_id = ? AND (SELECT v.state FROM primary_reservation_versions v
                WHERE v.primary_reservation_id = r.id ORDER BY v.revision DESC LIMIT 1) IN ('held', 'confirmed')", [$campaignId, $partyId]);

        $held = DB::selectOne("SELECT r.id FROM primary_reservations r WHERE r.business_campaign_id = ? AND r.party_id = ? AND r.expires_at > now()
            AND (SELECT v.state FROM primary_reservation_versions v WHERE v.primary_reservation_id = r.id ORDER BY v.revision DESC LIMIT 1) = 'held'
            ORDER BY r.created_at DESC, r.id DESC LIMIT 1", [$campaignId, $partyId]);

        return ['party_id' => $partyId, 'active_capital' => $this->activeCapital($partyId), 'campaign_units' => (string) $row['units'],
            'held_reservation_id' => $held === null ? null : (string) ((array) $held)['id']];
    }
}
