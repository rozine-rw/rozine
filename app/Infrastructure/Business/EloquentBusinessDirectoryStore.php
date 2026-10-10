<?php

declare(strict_types=1);

namespace App\Infrastructure\Business;

use App\Application\Business\Contracts\BusinessDirectoryStore;
use App\Models\BusinessCampaign;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * @phpstan-import-type Directory from BusinessDirectoryStore
 * @phpstan-import-type DirectoryRow from BusinessDirectoryStore
 * @phpstan-import-type Detail from BusinessDirectoryStore
 * @phpstan-import-type Rating from BusinessDirectoryStore
 * @phpstan-import-type HistoryEntry from BusinessDirectoryStore
 */
final class EloquentBusinessDirectoryStore implements BusinessDirectoryStore
{
    /** @return Directory */
    public function directory(string $chip, string $sort, string $sector, string $search, int $limit): array
    {
        $businesses = DB::query()->fromSub($this->businesses(), 'businesses');
        if ($search !== '') {
            $businesses->whereLike('name', '%'.addcslashes($search, '%_\\').'%');
        }
        if ($sector !== '') {
            $businesses->where('sector', $sector);
        }
        $totals = (clone $businesses)->selectRaw('count(*) AS everyone, coalesce(sum(active_notes), 0) AS notes, coalesce(sum(raised), 0)::text AS raised')->first();
        $ratings = $this->ratings(array_values((clone $businesses)->pluck('business_id')->map(fn (mixed $id): string => (string) $id)->all()));
        $matching = (clone $businesses)->count();
        $sort === 'name' ? $businesses->orderBy('name') : $businesses->orderByDesc('raised')->orderBy('name');
        $everyone = (int) ($totals->everyone ?? 0);
        $scores = array_map(fn (array $rating): string => $rating['score'], array_values($ratings));

        return ['rows' => array_values(array_map(fn (object $row): array => self::row($row, $ratings),
            $businesses->orderBy('business_id')->limit($limit)->get()->all())),
            'matching' => $matching,
            'counts' => ['all' => $everyone],
            'active_notes' => (int) ($totals->notes ?? 0), 'raised' => (string) ($totals->raised ?? '0'),
            'average_score' => $scores === [] ? null : (string) array_reduce($scores, fn (BigDecimal $sum, string $score): BigDecimal => $sum->plus($score), BigDecimal::zero())
                ->dividedBy(count($scores), 1, RoundingMode::HalfUp),
            'sectors' => array_values(array_map(fn (mixed $sector): string => (string) $sector,
                DB::table('business_profiles')->selectRaw("DISTINCT profile->>'industry' AS sector")->orderBy('sector')->pluck('sector')->all()))];
    }

    /** @return Detail|null */
    public function business(string $businessId): ?array
    {
        $record = DB::query()->fromSub($this->businesses(), 'businesses')->where('business_id', $businessId)->first();
        if ($record === null) {
            return null;
        }
        $campaigns = BusinessCampaign::query()->where('business_id', $businessId)->orderByDesc('live_at')->orderByDesc('id')->get();
        $funded = DB::table('primary_campaign_fundings')->where('business_id', $businessId)->pluck('business_campaign_id')->all();
        $closures = DB::table('business_campaign_closures')->leftJoin('users', 'users.id', '=', 'business_campaign_closures.actor_user_id')
            ->where('business_id', $businessId)->get(['business_campaign_closures.id', 'business_campaign_id', 'phase', 'closed_at', 'users.name'])
            ->keyBy('business_campaign_id');
        $actors = DB::table('users')->whereIn('id', $campaigns->pluck('actor_user_id')->all())->pluck('name', 'id');
        $title = fn (BusinessCampaign $campaign): string => (string) ($campaign->payload['title'] ?? '');

        $mandates = array_map(self::mandate(...), DB::table('business_mandates')->leftJoin('users', 'users.id', '=', 'business_mandates.actor_user_id')
            ->where('business_id', $businessId)->get(['business_mandates.id', 'business_mandates.created_at', 'users.name', 'terms', 'reason'])->all());
        $published = $campaigns->map(fn (BusinessCampaign $campaign): array => self::entry($campaign->id, $campaign->getRawOriginal('live_at'),
            (string) ($actors[$campaign->actor_user_id] ?? ''), 'campaign.publish', $title($campaign), null));
        $closed = $campaigns->filter(fn (BusinessCampaign $campaign): bool => $closures->has($campaign->id))->map(function (BusinessCampaign $campaign) use ($closures, $title): array {
            $closure = (array) $closures->get($campaign->id);

            return self::entry((string) $closure['id'], $closure['closed_at'], (string) ($closure['name'] ?? 'System'),
                $closure['phase'] === 'cancelled' ? 'campaign.cancel' : 'campaign.expire', $title($campaign), null);
        });
        $history = [...array_values($mandates), ...$published->values()->all(), ...$closed->values()->all()];
        usort($history, fn (array $first, array $second): int => [$second['at'], $second['id']] <=> [$first['at'], $first['id']]);

        return ['row' => self::row($record, $this->ratings([$businessId])),
            'notes' => array_values($campaigns->map(fn (BusinessCampaign $campaign): array => ['id' => $campaign->id, 'title' => $title($campaign),
                'status' => $closures->has($campaign->id) ? 'failed' : (in_array($campaign->id, $funded, true) ? 'funded' : 'active')])->all()),
            'history' => $history];
    }

    /**
     * Every Business whose authority is recorded, one row each, with its figures. KYC is verified
     * once the identity writer has verified the Business's own Party, as of the application clock:
     * a future-dated verification is not yet verified. The clock is bound rather than read from
     * PostgreSQL's now(), which is the start of the surrounding transaction.
     */
    private function businesses(): Builder
    {
        return DB::table('business_profiles AS bp')->join('parties', 'parties.id', '=', 'bp.entity_party_id')
            ->selectRaw("bp.id AS business_id, bp.profile->>'name' AS name, bp.profile->>'industry' AS sector, bp.profile->>'district' AS district,
                bp.profile->>'company_code' AS company_code,
                CASE WHEN parties.verified_at IS NOT NULL AND parties.verified_at <= ? THEN 'verified' ELSE 'pending' END AS kyc,
                (SELECT count(*) FROM business_campaigns c WHERE c.business_id = bp.id
                    AND NOT EXISTS (SELECT 1 FROM business_campaign_closures x WHERE x.business_campaign_id = c.id)) AS active_notes,
                (SELECT count(DISTINCT r.party_id) FROM primary_campaign_fundings f JOIN primary_funding_commitments fc ON fc.funding_id = f.id
                    JOIN primary_commitments pc ON pc.id = fc.commitment_id JOIN primary_reservations r ON r.id = pc.primary_reservation_id
                    WHERE f.business_id = bp.id) AS investors,
                coalesce((SELECT sum(f.principal) FROM primary_campaign_fundings f WHERE f.business_id = bp.id), 0) AS raised", [now()]);
    }

    /**
     * The rating each Business's newest rated note published, as its own home reads it.
     *
     * @param  list<string>  $businessIds
     * @return array<string, Rating>
     */
    private function ratings(array $businessIds): array
    {
        $ratings = [];
        foreach (BusinessCampaign::query()->whereIn('business_id', $businessIds)->orderByDesc('live_at')->orderByDesc('id')->get() as $campaign) {
            $rating = $campaign->payload['public_evidence']['rating'] ?? null;
            if (is_array($rating) && ! isset($ratings[$campaign->business_id])) {
                $ratings[$campaign->business_id] = ['band' => (string) $rating['band'], 'score' => (string) $rating['score']];
            }
        }

        return $ratings;
    }

    /**
     * A recorded authority, or its revocation, as staff entered it.
     *
     * @return HistoryEntry
     */
    private static function mandate(object $record): array
    {
        $mandate = (array) $record;
        $terms = json_decode((string) $mandate['terms'], true);

        return self::entry((string) $mandate['id'], $mandate['created_at'], (string) ($mandate['name'] ?? ''),
            ($terms['status'] ?? null) === 'revoked' ? 'business.authority.revoke' : 'business.authority.configure', null, (string) $mandate['reason']);
    }

    /**
     * One history entry, its stored instant as UTC ISO 8601 so entries sort as text.
     *
     * @param  'business.authority.configure'|'business.authority.revoke'|'campaign.publish'|'campaign.cancel'|'campaign.expire'  $command
     * @return HistoryEntry
     */
    private static function entry(string $id, mixed $at, string $actor, string $command, ?string $subject, ?string $reason): array
    {
        return ['id' => $id, 'at' => CarbonImmutable::parse((string) $at)->utc()->toIso8601String(), 'actor' => $actor, 'command' => $command,
            'subject' => $subject, 'reason' => $reason];
    }

    /**
     * @param  array<string, Rating>  $ratings
     * @return DirectoryRow
     */
    private static function row(object $record, array $ratings): array
    {
        $row = (array) $record;
        $id = (string) $row['business_id'];

        return ['business_id' => $id, 'name' => (string) $row['name'], 'sector' => (string) $row['sector'], 'district' => (string) $row['district'],
            'company_code' => $row['company_code'] === null ? null : (string) $row['company_code'],
            'kyc' => $row['kyc'] === 'verified' ? 'verified' : 'pending', 'rating' => $ratings[$id] ?? null,
            'active_notes' => (int) $row['active_notes'], 'investors' => (int) $row['investors'], 'raised' => (string) $row['raised']];
    }
}
