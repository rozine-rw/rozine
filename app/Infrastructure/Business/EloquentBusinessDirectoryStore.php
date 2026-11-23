<?php

declare(strict_types=1);

namespace App\Infrastructure\Business;

use App\Application\Business\Contracts\BusinessDirectoryStore;
use App\Application\Business\Contracts\ServicingRecord;
use App\Models\BusinessCampaign;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * @phpstan-import-type Directory from BusinessDirectoryStore
 * @phpstan-import-type DirectoryRow from BusinessDirectoryStore
 * @phpstan-import-type Detail from BusinessDirectoryStore
 * @phpstan-import-type Rating from BusinessDirectoryStore
 * @phpstan-import-type HistoryEntry from BusinessDirectoryStore
 */
final class EloquentBusinessDirectoryStore implements BusinessDirectoryStore
{
    public function __construct(private ServicingRecord $servicing) {}

    /** @return Directory */
    public function directory(string $chip, string $sort, string $sector, string $search, int $limit): array
    {
        $businesses = DB::query()->fromSub($this->businesses($this->repaidNotes()), 'businesses');
        if ($search !== '') {
            $businesses->whereLike('name', '%'.addcslashes($search, '%_\\').'%');
        }
        if ($sector !== '') {
            $businesses->where('sector', $sector);
        }
        $totals = (clone $businesses)->selectRaw('count(*) AS everyone, coalesce(sum(active_notes), 0) AS notes, coalesce(sum(raised), 0)::text AS raised')->first();
        $ratings = $this->ratings(array_values((clone $businesses)->pluck('business_id')->map(fn (mixed $id): string => (string) $id)->all()));
        $matching = (clone $businesses)->count();
        $sort === 'name' ? $businesses->orderBy('name') : $businesses->orderByDesc($sort)->orderBy('name');
        $everyone = (int) ($totals->everyone ?? 0);
        $scores = array_map(fn (array $rating): string => $rating['score'], array_values($ratings));

        return ['rows' => array_values(array_map(fn (object $row): array => $this->row($row, $ratings),
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
        $record = DB::query()->fromSub($this->businesses(array_column($this->servicing->forBusiness($businessId)['repaid'], 'campaign_id')), 'businesses')->where('business_id', $businessId)->first();
        if ($record === null) {
            return null;
        }
        $campaigns = BusinessCampaign::query()->where('business_id', $businessId)->orderByDesc('live_at')->orderByDesc('id')->get();
        $funded = DB::table('primary_campaign_fundings')->where('business_id', $businessId)->pluck('business_campaign_id')->all();
        $issued = DB::table('disbursements')->join('disbursement_closings', 'disbursement_closings.disbursement_id', '=', 'disbursements.id')
            ->where('disbursements.business_id', $businessId)->where('disbursement_closings.kind', 'issued')->pluck('disbursements.business_campaign_id')->all();
        $repaid = array_column($this->servicing->forBusiness($businessId)['repaid'], 'campaign_id');
        $closures = DB::table('business_campaign_closures')->leftJoin('users', 'users.id', '=', 'business_campaign_closures.actor_user_id')
            ->where('business_campaign_closures.business_id', $businessId)->get(['business_campaign_closures.id', 'business_campaign_closures.business_campaign_id', 'business_campaign_closures.phase', 'business_campaign_closures.closed_at', 'users.name'])
            ->keyBy('business_campaign_id');
        $actors = DB::table('users')->whereIn('id', $campaigns->pluck('actor_user_id')->all())->pluck('name', 'id');
        $title = fn (BusinessCampaign $campaign): string => (string) ($campaign->payload['title'] ?? '');

        $mandates = array_map(self::mandate(...), DB::table('business_mandates')->leftJoin('users', 'users.id', '=', 'business_mandates.actor_user_id')
            ->where('business_id', $businessId)->get(['business_mandates.id', 'business_mandates.created_at', 'users.name', 'terms', 'reason'])->all());
        $published = $campaigns->map(fn (BusinessCampaign $campaign): array => self::entry($campaign->id, $campaign->getRawOriginal('live_at'),
            (string) ($actors[$campaign->actor_user_id] ?? ''), 'campaign.publish', $title($campaign), null));
        $closed = $campaigns->filter(fn (BusinessCampaign $campaign): bool => $closures->has($campaign->id))->map(function (BusinessCampaign $campaign) use ($closures, $title): array {
            $closure = (array) $closures->get($campaign->id);
            $command = match ($closure['phase']) {
                'cancelled' => 'campaign.cancel',
                'expired' => 'campaign.expire',
                'failed_closing' => 'campaign.failed_closing',
                default => throw new RuntimeException('CAMPAIGN_CLOSURE_INTEGRITY_FAILED'),
            };

            return self::entry((string) $closure['id'], $closure['closed_at'], (string) ($closure['name'] ?? 'System'),
                $command, $title($campaign), null);
        });
        // Each published audit report re-verified the Business's newest months (REP-R04), dated when it was sealed.
        $reports = array_map(fn (object $report): array => self::entry((string) $report->id, $report->sealed_at !== null ? CarbonImmutable::parse((string) $report->sealed_at)->toIso8601String()
            : self::occurred((string) $report->published_at, (string) $report->updated_at), 'Audit Partner', 'report.publish',
            str_replace('_', ' ', (string) $report->kind).' audit sealed', 'Report RPT-'.strtoupper(substr((string) $report->id, -8)).' verified the newest months against the business\'s records.'),
            DB::table('audit_reports')->join('audit_report_publications', 'audit_report_publications.audit_report_id', '=', 'audit_reports.id')
                ->leftJoin('audit_report_seals', 'audit_report_seals.audit_report_id', '=', 'audit_reports.id')
                ->where('audit_reports.business_id', $businessId)->where('audit_report_publications.status', 'published')
                ->get(['audit_reports.id', 'audit_reports.kind', 'audit_report_publications.published_at', 'audit_report_publications.updated_at',
                    'audit_report_seals.created_at as sealed_at'])->all());
        // Each note's payout to the Business, and each repayment it made, dated when they were recorded (QA #642).
        $titles = $campaigns->mapWithKeys(fn (BusinessCampaign $campaign): array => [strtolower($campaign->id) => $title($campaign)]);
        $rwf = fn (string $amount): string => 'RWF '.number_format((int) $amount);
        $disbursed = array_map(fn (object $closing): array => self::entry((string) $closing->id, $closing->effective_at ?? $closing->created_at, 'System', 'campaign.disburse',
            ($titles[strtolower((string) $closing->business_campaign_id)] ?? '').' · '.$rwf((string) ((int) $closing->amount - (int) $closing->platform_fee)),
            'Raised '.$rwf((string) $closing->amount).', less the platform fee of '.$rwf((string) $closing->platform_fee).'.'),
            DB::table('disbursement_closings')->join('disbursements', 'disbursements.id', '=', 'disbursement_closings.disbursement_id')
                ->where('disbursements.business_id', $businessId)->where('disbursement_closings.kind', 'issued')
                ->get(['disbursement_closings.id', 'disbursement_closings.effective_at', 'disbursement_closings.created_at', 'disbursements.business_campaign_id',
                    'disbursements.amount', 'disbursements.platform_fee'])->all());
        $repayments = array_map(fn (object $repayment): array => self::entry((string) $repayment->id, $repayment->created_at, 'Business', 'note.repay',
            ($titles[strtolower((string) $repayment->note_id)] ?? '').' · '.$rwf((string) $repayment->amount), null),
            DB::table('business_repayments')->where('business_id', $businessId)->get(['id', 'note_id', 'amount', 'created_at'])->all());
        $history = [...array_values($mandates), ...$published->values()->all(), ...$closed->values()->all(), ...$reports, ...$disbursed, ...$repayments];
        usort($history, fn (array $first, array $second): int => [$second['at'], $second['id']] <=> [$first['at'], $first['id']]);

        return ['row' => $this->row($record, $this->ratings([$businessId])),
            'notes' => array_values($campaigns->map(fn (BusinessCampaign $campaign): array => ['id' => $campaign->id, 'title' => $title($campaign),
                'status' => match (true) {
                    $closures->has($campaign->id) => 'failed',
                    in_array($campaign->id, $repaid, true) => 'completed',
                    in_array($campaign->id, $issued, true) => 'repaying',
                    in_array($campaign->id, $funded, true) => 'funded',
                    default => 'active',
                }])->all()),
            'history' => $history];
    }

    /**
     * Every note repaid in full, across every Business, so no longer active. Excluding them in SQL keeps
     * the active-notes sort, each row and the totals in agreement.
     *
     * @return list<string>
     */
    private function repaidNotes(): array
    {
        $repaid = [];
        foreach (DB::table('business_profiles')->pluck('id') as $businessId) {
            array_push($repaid, ...array_column($this->servicing->forBusiness((string) $businessId)['repaid'], 'campaign_id'));
        }

        return $repaid;
    }

    /**
     * Every Business whose authority is recorded, one row each, with its figures. KYC is verified
     * once the identity writer has verified the Business's own Party, as of the application clock:
     * a future-dated verification is not yet verified. The clock is bound rather than read from
     * PostgreSQL's now(), which is the start of the surrounding transaction. Notes repaid in full are
     * not active. The stage is how far the Business has come towards its first listing: verification,
     * its application, the CPA baseline audit (AUD-R03), then a note on the market.
     *
     * @param  list<string>  $repaid
     */
    private function businesses(array $repaid): Builder
    {
        $notRepaid = $repaid === [] ? '' : 'AND c.id NOT IN ('.implode(', ', array_fill(0, count($repaid), '?')).')';

        return DB::table('business_profiles AS bp')->join('parties', 'parties.id', '=', 'bp.entity_party_id')
            ->selectRaw("bp.id AS business_id, bp.profile->>'name' AS name, bp.profile->>'industry' AS sector, bp.profile->>'district' AS district,
                bp.profile->>'company_code' AS company_code,
                CASE WHEN parties.verified_at IS NOT NULL AND parties.verified_at <= ? THEN 'verified' ELSE 'pending' END AS kyc,
                (SELECT count(*) FROM business_campaigns c WHERE c.business_id = bp.id {$notRepaid}
                    AND NOT EXISTS (SELECT 1 FROM business_campaign_closures x WHERE x.business_campaign_id = c.id)) AS active_notes,
                (SELECT count(DISTINCT backer.party_id) FROM (
                    SELECT r.party_id FROM primary_campaign_fundings f JOIN primary_funding_commitments fc ON fc.funding_id = f.id
                        JOIN primary_commitments pc ON pc.id = fc.commitment_id JOIN primary_reservations r ON r.id = pc.primary_reservation_id
                        WHERE f.business_id = bp.id
                    UNION SELECT r.party_id FROM primary_commitments pc JOIN primary_reservations r ON r.id = pc.primary_reservation_id
                        JOIN business_campaigns c ON c.id = r.business_campaign_id
                        WHERE c.business_id = bp.id AND NOT EXISTS (SELECT 1 FROM business_campaign_closures x WHERE x.business_campaign_id = c.id)) AS backer) AS investors,
                coalesce((SELECT sum(f.principal) FROM primary_campaign_fundings f WHERE f.business_id = bp.id), 0) AS raised,
                CASE WHEN EXISTS (SELECT 1 FROM business_campaigns c WHERE c.business_id = bp.id) THEN 'listed'
                    WHEN EXISTS (SELECT 1 FROM audit_assignments a WHERE a.business_id = bp.id AND a.status IN ('offered', 'accepted', 'operations')
                        AND NOT EXISTS (SELECT 1 FROM audit_reports r JOIN audit_report_publications p ON p.audit_report_id = r.id
                            WHERE r.assignment_id = a.id AND p.status = 'published')) THEN 'auditing'
                    WHEN EXISTS (SELECT 1 FROM business_applications ba WHERE ba.business_id = bp.id AND ba.status = 'submitted') THEN 'applied'
                    WHEN EXISTS (SELECT 1 FROM business_applications ba WHERE ba.business_id = bp.id AND ba.status = 'draft') THEN 'applying'
                    WHEN parties.verified_at IS NOT NULL AND parties.verified_at <= ? THEN 'not_applied'
                    ELSE 'kyb_pending' END AS stage", [now(), ...$repaid, now()]);
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
     * When a report's publication happened. On UAT the simulated review closes at once but records its
     * window's end, a later instant the database requires, so the publication row's own update time
     * (when it was published) is used whenever it is earlier, and never a time still to come.
     */
    private static function occurred(string $publishedAt, string $updatedAt): string
    {
        $at = CarbonImmutable::parse($publishedAt);
        $at = CarbonImmutable::parse($updatedAt)->min($at);

        return $at->isFuture() ? now('UTC')->toIso8601String() : $at->toIso8601String();
    }

    /**
     * One history entry, its stored instant as UTC ISO 8601 so entries sort as text.
     *
     * @param  'business.authority.configure'|'business.authority.revoke'|'campaign.publish'|'campaign.cancel'|'campaign.expire'|'campaign.failed_closing'|'campaign.disburse'|'note.repay'|'report.publish'  $command
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
    private function row(object $record, array $ratings): array
    {
        $row = (array) $record;
        $id = (string) $row['business_id'];

        return ['business_id' => $id, 'name' => (string) $row['name'], 'sector' => (string) $row['sector'], 'district' => (string) $row['district'],
            'company_code' => $row['company_code'] === null ? null : (string) $row['company_code'],
            'kyc' => $row['kyc'] === 'verified' ? 'verified' : 'pending', 'rating' => $ratings[$id] ?? null,
            'active_notes' => (int) $row['active_notes'], 'investors' => (int) $row['investors'], 'raised' => (string) $row['raised'],
            'stage' => match ((string) $row['stage']) {
                'listed' => 'listed', 'auditing' => 'auditing', 'applied' => 'applied', 'applying' => 'applying', 'not_applied' => 'not_applied',
                default => 'kyb_pending',
            }];
    }
}
