<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity;

use App\Application\Identity\Contracts\InstitutionDirectoryStore;
use App\Application\Operations\Contracts\PolicySettings;
use App\Domain\Primary\UnitRights;
use App\Models\RoleApplication;
use Brick\Math\BigInteger;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * @phpstan-import-type Directory from InstitutionDirectoryStore
 * @phpstan-import-type DirectoryRow from InstitutionDirectoryStore
 * @phpstan-import-type Detail from InstitutionDirectoryStore
 */
final class EloquentInstitutionDirectoryStore implements InstitutionDirectoryStore
{
    /** The signed ledger total of one Party's wallet accounts of the kinds that follow. */
    private const CASH = "coalesce((SELECT sum(CASE WHEN ll.direction = 'credit' THEN ll.amount ELSE -ll.amount END)
        FROM ledger_lines ll JOIN ledger_accounts la ON la.id = ll.account_id JOIN investor_wallets w ON w.id = la.wallet_id
        WHERE w.party_id = ra.party_id AND la.kind IN ";

    private const STATUSES = ['approved', 'submitted', 'changes_requested', 'draft', 'rejected'];

    public function __construct(private PolicySettings $policies) {}

    /** @return Directory */
    public function directory(string $chip, string $search, int $limit): array
    {
        $institutions = DB::query()->fromSub($this->institutions(), 'institutions');
        if ($search !== '') {
            $term = '%'.addcslashes(mb_strtolower($search), '%_\\').'%';
            $institutions->where(fn (Builder $match) => $match->whereLike('search_name', $term)->orWhereLike('rdb_code', $term)
                ->orWhereRaw('lower(representative) LIKE ?', [$term])->orWhereRaw('lower(email) LIKE ?', [$term]));
        }
        $totals = (clone $institutions)->selectRaw("count(*) AS everyone, count(*) FILTER (WHERE status = 'approved') AS approved,
            count(*) FILTER (WHERE status = 'submitted') AS submitted, count(*) FILTER (WHERE status = 'changes_requested') AS changes_requested,
            count(*) FILTER (WHERE status = 'draft') AS draft, count(*) FILTER (WHERE status = 'rejected') AS rejected,
            coalesce(sum(monthly_commitment) FILTER (WHERE status = 'approved'), 0)::text AS committed_monthly,
            coalesce(sum(invested), 0)::text AS invested")->first();
        if (in_array($chip, self::STATUSES, true)) {
            $institutions->where('status', $chip);
        }
        $matching = (clone $institutions)->count();
        $rows = $institutions->orderByRaw('coalesce(approved_at, submitted_at, created_at) DESC')->orderBy('application_id')->limit($limit)->get()->all();

        return ['rows' => $this->rows($rows), 'matching' => $matching,
            'counts' => ['all' => (int) ($totals->everyone ?? 0), 'approved' => (int) ($totals->approved ?? 0), 'submitted' => (int) ($totals->submitted ?? 0),
                'changes_requested' => (int) ($totals->changes_requested ?? 0), 'draft' => (int) ($totals->draft ?? 0), 'rejected' => (int) ($totals->rejected ?? 0)],
            'committed_monthly' => (string) ($totals->committed_monthly ?? '0'), 'invested' => (string) ($totals->invested ?? '0')];
    }

    /** @return Detail|null */
    public function institution(string $applicationId): ?array
    {
        $record = DB::query()->fromSub($this->institutions(), 'institutions')->where('application_id', $applicationId)->first();
        if ($record === null) {
            return null;
        }
        $row = $this->rows([$record])[0];
        $partyId = $row['party_id'];
        $holdings = DB::table('primary_holdings AS h')->join('business_campaigns AS c', 'c.id', '=', 'h.business_campaign_id')
            ->join('business_profiles AS bp', 'bp.id', '=', 'c.business_id')->where('h.party_id', $partyId)
            ->groupBy('bp.id')->orderByRaw('sum(h.principal) DESC')->orderByRaw("bp.profile->>'name'")
            ->selectRaw("bp.id AS business_id, bp.profile->>'name' AS name, sum(h.principal) AS principal, count(DISTINCT h.business_campaign_id) AS notes")->get();
        // The units each raise counts against the Party's cap: its held and confirmed reservations, as checkout counts them.
        $raises = DB::select("SELECT c.id AS campaign_id, coalesce(bp.profile->>'name', '') AS business, c.principal::text AS raise_amount, sum(r.units)::text AS units
            FROM primary_reservations r JOIN business_campaigns c ON c.id = r.business_campaign_id JOIN business_profiles bp ON bp.id = c.business_id
            WHERE r.party_id = ? AND (SELECT v.state FROM primary_reservation_versions v WHERE v.primary_reservation_id = r.id ORDER BY v.revision DESC LIMIT 1) IN ('held', 'confirmed')
            GROUP BY c.id, bp.profile->>'name', c.principal ORDER BY max(r.created_at) DESC, c.id", [$partyId]);
        $cap = $this->policies->holdingCapPercent($partyId);
        $committed = DB::table('role_applications AS ra')->where('ra.id', $applicationId)->selectRaw(self::CASH."('investor_committed')), 0)::text AS committed")->value('committed');
        $approver = DB::table('institutional_investors')->join('users', 'users.id', '=', 'institutional_investors.approved_by_user_id')
            ->where('institutional_investors.role_application_id', $applicationId)->value('users.name');

        return ['row' => $row, 'approved_by' => $approver === null ? null : (string) $approver, 'committed' => (string) $committed, 'cap_percent' => $cap,
            'holdings' => array_values($holdings->map(fn (object $holding): array => ['business_id' => (string) $holding->business_id,
                'name' => (string) $holding->name, 'principal' => (string) $holding->principal, 'notes' => (int) $holding->notes])->all()),
            'raises' => array_values(array_map(function (object $record) use ($cap): array {
                $raise = (array) $record;
                $amount = BigInteger::of((string) $raise['units'])->multipliedBy(UnitRights::UNIT_PRINCIPAL);
                $total = BigInteger::of((string) $raise['raise_amount']);

                return ['campaign_id' => (string) $raise['campaign_id'], 'business' => (string) $raise['business'], 'units' => (string) $raise['units'],
                    'amount' => (string) $amount, 'raise_amount' => (string) $total,
                    'share_bps' => $total->isZero() ? 0 : $amount->multipliedBy(10000)->quotient($total)->toInt(), 'cap_percent' => $cap];
            }, $raises))];
    }

    /**
     * Every institution application whose Party still has an open account, one row each, with the
     * institution its approval recorded and the Party's money.
     */
    private function institutions(): Builder
    {
        return DB::table('role_applications AS ra')->leftJoin('institutional_investors AS ii', 'ii.role_application_id', '=', 'ra.id')
            ->where('ra.role', 'institution')
            ->whereExists(fn (Builder $open) => $open->selectRaw('1')->from('users')->whereColumn('users.party_id', 'ra.party_id')->whereNull('users.closed_at'))
            ->selectRaw("ra.id AS application_id, ra.party_id, ra.status, ra.search_name, ra.submitted_at, ra.created_at, ii.approved_at,
                ii.legal_name, coalesce(ii.rdb_code, '') AS rdb_code, ii.institution_type, coalesce(ii.monthly_commitment, 0) AS monthly_commitment,
                (SELECT u.id FROM users u WHERE u.party_id = ra.party_id AND u.closed_at IS NULL ORDER BY u.id LIMIT 1) AS user_id,
                coalesce((SELECT u.name FROM users u WHERE u.party_id = ra.party_id AND u.closed_at IS NULL ORDER BY u.id LIMIT 1), '') AS representative,
                coalesce((SELECT u.email FROM users u WHERE u.party_id = ra.party_id AND u.closed_at IS NULL ORDER BY u.id LIMIT 1), '') AS email,
                ".self::CASH."('investor_available', 'investor_held')), 0) AS wallet,
                ".self::CASH."('investor_committed')), 0) + coalesce((SELECT sum(h.principal) FROM primary_holdings h WHERE h.party_id = ra.party_id), 0) AS invested,
                (SELECT count(*) FROM primary_holdings h WHERE h.party_id = ra.party_id) AS holdings");
    }

    /**
     * Rows as the directory shows them. Before approval records the institution, its name, RDB code,
     * type and commitment are read from the encrypted application answers.
     *
     * @param  array<int, object>  $records
     * @return list<DirectoryRow>
     */
    private function rows(array $records): array
    {
        $records = array_values(array_map(fn (object $record): array => (array) $record, $records));
        $pending = array_values(array_map(fn (array $row): string => (string) $row['application_id'],
            array_filter($records, fn (array $row): bool => $row['legal_name'] === null)));
        $answers = $pending === [] ? collect() : RoleApplication::query()->whereKey($pending)->get()
            ->mapWithKeys(fn (RoleApplication $application): array => [$application->id => $application->state['answers'] ?? []]);

        return array_map(function (array $row) use ($answers): array {
            /** @var array<string, mixed> $given */
            $given = $answers->get((string) $row['application_id'], []);
            $company = is_array($given['company'] ?? null) ? $given['company'] : [];
            $institution = is_array($given['institution'] ?? null) ? $given['institution'] : [];
            $approved = $row['legal_name'] !== null;
            /** @var 'draft'|'submitted'|'changes_requested'|'approved'|'rejected' $status */
            $status = in_array($row['status'], self::STATUSES, true) ? (string) $row['status'] : 'draft';

            return ['application_id' => (string) $row['application_id'], 'party_id' => (string) $row['party_id'], 'status' => $status,
                'legal_name' => $approved ? (string) $row['legal_name'] : (string) ($company['legal_name'] ?? ''),
                'rdb_code' => $approved ? (string) $row['rdb_code'] : (string) ($company['rdb_code'] ?? ''),
                'institution_type' => $approved ? (string) $row['institution_type'] : (string) ($institution['type'] ?? ''),
                'monthly_commitment' => $approved ? (string) $row['monthly_commitment'] : (string) ($institution['monthly_commitment'] ?? '0'),
                'representative' => (string) $row['representative'], 'email' => (string) $row['email'],
                'user_id' => $row['user_id'] === null ? null : (int) $row['user_id'],
                'wallet' => (string) $row['wallet'], 'invested' => (string) $row['invested'], 'holdings' => (int) $row['holdings'],
                'submitted_at' => $row['submitted_at'] === null ? null : (string) $row['submitted_at'],
                'approved_at' => $row['approved_at'] === null ? null : (string) $row['approved_at']];
        }, $records);
    }
}
