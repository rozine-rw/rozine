<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity;

use App\Application\Identity\Contracts\InvestorDirectoryStore;
use App\Models\InvestorVerification;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * @phpstan-import-type Directory from InvestorDirectoryStore
 * @phpstan-import-type DirectoryRow from InvestorDirectoryStore
 * @phpstan-import-type Detail from InvestorDirectoryStore
 */
final class EloquentInvestorDirectoryStore implements InvestorDirectoryStore
{
    /** The signed ledger total of one Party's wallet accounts of the kinds that follow. */
    private const CASH = "coalesce((SELECT sum(CASE WHEN ll.direction = 'credit' THEN ll.amount ELSE -ll.amount END)
        FROM ledger_lines ll JOIN ledger_accounts la ON la.id = ll.account_id JOIN investor_wallets w ON w.id = la.wallet_id
        WHERE w.party_id = parties.id AND la.kind IN ";

    /** @return Directory */
    public function directory(string $chip, string $sort, string $search, int $limit): array
    {
        $investors = DB::query()->fromSub($this->investors(), 'investors');
        if ($search !== '') {
            $term = '%'.addcslashes($search, '%_\\').'%';
            $investors->where(fn (Builder $match) => $match->whereLike('name', $term)->orWhereLike('email', $term));
        }
        $totals = (clone $investors)->selectRaw("count(*) AS everyone, count(*) FILTER (WHERE kyc = 'verified') AS verified, count(*) FILTER (WHERE kyc = 'pending') AS pending,
            count(*) FILTER (WHERE restricted) AS restricted, count(*) FILTER (WHERE verification_status = 'submitted' AND kyc <> 'verified') AS awaiting,
            coalesce(sum(portfolio), 0)::text AS aum")->first();
        match ($chip) {
            'verified', 'pending' => $investors->where('kyc', $chip),
            'restricted' => $investors->where('restricted', true),
            // No KYC expiry or account freeze exists for Investors yet, so neither chip can match anyone.
            'kyc_overdue', 'frozen' => $investors->whereRaw('false'),
            default => null,
        };
        $matching = (clone $investors)->count();
        $sort === 'name' ? $investors->orderBy('name') : $investors->orderByDesc('portfolio')->orderBy('name');

        return ['rows' => array_values(array_map(self::row(...), $investors->orderBy('party_id')->limit($limit)->get()->all())),
            'matching' => $matching,
            'counts' => ['all' => (int) ($totals->everyone ?? 0), 'verified' => (int) ($totals->verified ?? 0), 'pending' => (int) ($totals->pending ?? 0),
                'kyc_overdue' => 0, 'frozen' => 0, 'restricted' => (int) ($totals->restricted ?? 0)],
            'awaiting_review' => (int) ($totals->awaiting ?? 0), 'aum' => (string) ($totals->aum ?? '0')];
    }

    /** @return Detail|null */
    public function party(string $partyId): ?array
    {
        $row = DB::query()->fromSub($this->investors(), 'investors')->where('party_id', $partyId)->first();
        if ($row === null) {
            return null;
        }
        $row = self::row($row);
        $restriction = DB::table('investor_account_restrictions')->where('party_id', $partyId)->where('effective_at', '<=', now())
            ->where(fn (Builder $open) => $open->whereNull('expires_at')->orWhere('expires_at', '>', now()))->min('effective_at');
        $history = $row['verification_id'] === null ? collect() : DB::table('investor_verification_versions')
            ->leftJoin('users', 'users.id', '=', 'investor_verification_versions.actor_user_id')
            ->where('investor_verification_id', $row['verification_id'])->orderByDesc('revision')
            ->get(['investor_verification_versions.id', 'investor_verification_versions.created_at', 'users.name', 'command', 'reason']);

        return ['row' => $row,
            'restricted_since' => $restriction === null ? null : (string) $restriction,
            'history' => array_values($history->map(function (object $version): array {
                $version = (array) $version;

                return ['id' => (string) $version['id'], 'at' => (string) $version['created_at'], 'actor' => (string) ($version['name'] ?? ''),
                    'command' => (string) $version['command'], 'reason' => $version['reason'] === null ? null : (string) $version['reason']];
            })->all())];
    }

    public function partyForVerification(string $verificationId): ?string
    {
        $partyId = InvestorVerification::query()->whereKey($verificationId)->value('party_id');

        return $partyId === null ? null : (string) $partyId;
    }

    /**
     * Every Party with an Investor membership or a verification case, one row each. KYC is verified
     * once the verified-person writer has set the Party verified, rejected while the case's last
     * decision stands, and pending otherwise.
     */
    private function investors(): Builder
    {
        return DB::table('parties')->leftJoin('investor_verifications AS iv', 'iv.party_id', '=', 'parties.id')
            ->where(fn (Builder $investor) => $investor->whereNotNull('iv.id')->orWhereExists(fn (Builder $membership) => $membership->selectRaw('1')
                ->from('role_memberships')->whereColumn('role_memberships.party_id', 'parties.id')->where('role_memberships.role', 'investor')))
            ->selectRaw("parties.id AS party_id, iv.id AS verification_id, iv.status AS verification_status,
                coalesce((SELECT u.name FROM users u WHERE u.party_id = parties.id ORDER BY u.id LIMIT 1), '') AS name,
                coalesce((SELECT u.email FROM users u WHERE u.party_id = parties.id ORDER BY u.id LIMIT 1), '') AS email,
                CASE WHEN parties.verified_at IS NOT NULL THEN 'verified' WHEN iv.status = 'rejected' THEN 'rejected' ELSE 'pending' END AS kyc,
                ".self::CASH."('investor_available', 'investor_held')), 0) AS wallet,
                ".self::CASH."('investor_committed')), 0) + coalesce((SELECT sum(h.principal) FROM primary_holdings h WHERE h.party_id = parties.id), 0) AS portfolio,
                (SELECT count(*) FROM primary_holdings h WHERE h.party_id = parties.id) AS holdings,
                (SELECT count(DISTINCT c.business_id) FROM primary_holdings h JOIN business_campaigns c ON c.id = h.business_campaign_id WHERE h.party_id = parties.id) AS businesses,
                EXISTS (SELECT 1 FROM investor_account_restrictions r WHERE r.party_id = parties.id AND r.effective_at <= now()
                    AND (r.expires_at IS NULL OR r.expires_at > now())) AS restricted");
    }

    /** @return DirectoryRow */
    private static function row(object $record): array
    {
        $row = (array) $record;
        $kyc = match ((string) $row['kyc']) {
            'verified' => 'verified',
            'rejected' => 'rejected',
            default => 'pending',
        };

        return ['party_id' => (string) $row['party_id'], 'name' => (string) $row['name'], 'email' => (string) $row['email'], 'kyc' => $kyc,
            'verification_id' => $row['verification_id'] === null ? null : (string) $row['verification_id'],
            'wallet' => (string) $row['wallet'], 'portfolio' => (string) $row['portfolio'], 'holdings' => (int) $row['holdings'],
            'businesses' => (int) $row['businesses'], 'restricted' => (bool) $row['restricted']];
    }
}
