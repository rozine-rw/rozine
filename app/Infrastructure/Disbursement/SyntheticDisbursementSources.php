<?php

declare(strict_types=1);

namespace App\Infrastructure\Disbursement;

use App\Application\Disbursement\Contracts\FundedCampaigns;
use App\Application\Disbursement\Contracts\PayoutDestinations;
use App\Application\Disbursement\Contracts\StaffConnections;
use App\Application\Disbursement\Contracts\SyntheticDisbursementFixtures;
use App\Application\Disbursement\FailedClosing;
use App\Application\Disbursement\FundedCampaign;
use App\Application\Disbursement\FundedCampaignRef;
use App\Application\Disbursement\FundedCommitment;
use App\Application\Disbursement\IssueInstruction;
use App\Application\Disbursement\RecheckResult;
use App\Application\Disbursement\SyntheticDisbursementGuard;
use App\Application\Disbursement\VerifiedDestination;
use App\Application\Environment\EnvironmentIsolation;
use App\Domain\Disbursement\DisbursementViolation;
use App\Domain\Operations\CommandRejection;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Explicit synthetic stand-ins for the S3-C funding source, the Business destination source and
 * the staff connection source, for local and testing only (#96 5871504691). They hold synthetic
 * facts in the application cache, never write Primary or Business records, and serialize like the
 * real sources do: an advisory lock for the Business, then one for the campaign, inside the
 * caller's transaction. Recorded funding effects commit with that transaction or not at all.
 */
final class SyntheticDisbursementSources implements FundedCampaigns, PayoutDestinations, StaffConnections, SyntheticDisbursementFixtures
{
    private const string PREFIX = 'synthetic-disbursement:';

    public function __construct(private SyntheticDisbursementGuard $guard, private CacheFactory $cache, private EnvironmentIsolation $isolation) {}

    public function funded(?string $before, int $limit): array
    {
        $this->guard->assertAllowed();
        $ids = $this->store()->get(self::PREFIX.'campaigns', []);
        rsort($ids);
        $refs = [];
        foreach ($ids as $id) {
            if (($before === null || strcmp($id, $before) < 0) && count($refs) < $limit) {
                $campaign = $this->campaign($id);
                $refs[] = new FundedCampaignRef($campaign->campaignId, $campaign->businessId, $campaign->fundedAt);
            }
        }

        return $refs;
    }

    public function lockBusiness(string $businessId): void
    {
        $this->assertTransaction();
        DB::select('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))', ['synthetic-business:'.$businessId]);
    }

    public function lockFunded(string $campaignId): FundedCampaign
    {
        $this->assertTransaction();
        $campaign = $this->campaign($campaignId);
        DB::select('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))', ['synthetic-campaign:'.$campaignId]);

        return $campaign;
    }

    public function recheck(FundedCampaign $campaign): RecheckResult
    {
        $this->assertTransaction();
        $script = $this->store()->get(self::PREFIX.'recheck:'.$campaign->campaignId, ['outcome' => 'passed', 'causes' => []]);

        return match ($script['outcome']) {
            'failed' => RecheckResult::failed($script['causes'], 'synthetic-recheck-v1'),
            'unavailable' => RecheckResult::unavailable($script['causes'], 'synthetic-recheck-v1'),
            default => RecheckResult::passed('synthetic-recheck-v1'),
        };
    }

    public function issue(FundedCampaign $campaign, IssueInstruction $instruction): void
    {
        $this->record($campaign, ['kind' => 'issued', 'closing_id' => $instruction->closingId, 'disbursement_id' => $instruction->disbursementId,
            'effective_at' => $instruction->effectiveAt, 'effective_date' => $instruction->effectiveDate, 'due_dates' => $instruction->dueDates,
            'holdings' => array_map(fn (FundedCommitment $commitment): array => ['commitment_id' => $commitment->id, 'ordinals' => $commitment->ordinals,
                'rights' => $commitment->rights, 'terms' => $commitment->terms, 'principal' => $commitment->principal], $campaign->commitments),
            'exposure_reservation_id' => $campaign->exposureReservationId]);
    }

    public function failClose(FundedCampaign $campaign, FailedClosing $closing): void
    {
        $this->record($campaign, ['kind' => 'failed_closing', 'closing_id' => $closing->closingId, 'disbursement_id' => $closing->disbursementId,
            'cause' => $closing->cause, 'causes' => $closing->causes, 'refunds' => array_map(fn (FundedCommitment $commitment): array => [
                'commitment_id' => $commitment->id, 'party_id' => $commitment->partyId, 'amount' => $commitment->principal, 'fee' => '0'], $campaign->commitments),
            'exposure_reservation_id' => $campaign->exposureReservationId]);
    }

    public function verified(string $businessId, string $environment): ?VerifiedDestination
    {
        $this->guard->assertAllowed();
        $destination = $this->store()->get(self::PREFIX.'destination:'.$businessId);
        if (! is_array($destination) || $destination['state'] !== 'verified' || $destination['environment'] !== $environment
            || $destination['expires_at'] <= now('UTC')->toIso8601String()) {
            return null;
        }

        return new VerifiedDestination($destination['id'], $destination['revision'], $businessId, $destination['mandate_id'], 'synthetic-bank',
            $destination['account_token_sha256'], $environment, $destination['evidence_sha256'], $destination['verified_at'], $destination['expires_at'],
            $destination['masked']);
    }

    public function connection(int $staffUserId, string $businessId, array $partyIds): string
    {
        $this->guard->assertAllowed();
        if ($this->store()->get(self::PREFIX.'connections') === 'unavailable') {
            return 'unavailable';
        }

        return $this->store()->get(self::PREFIX.'connection:'.$staffUserId, 'unconnected');
    }

    public function fund(array $commitmentUnits = [300, 300], int $termMonths = 12): FundedCampaign
    {
        $this->guard->assertAllowed();
        $id = fn (): string => strtolower((string) Str::ulid());
        $commitments = [];
        $next = 1;
        foreach ($commitmentUnits as $units) {
            $commitments[] = ['id' => $id(), 'party_id' => $id(), 'origin_operation_id' => $id(), 'units' => $units,
                'ordinals' => [['first' => $next, 'last' => $next + $units - 1]], 'rights' => ['synthetic' => true, 'units' => $units],
                'terms' => ['rate_pct' => '12.1', 'term_months' => $termMonths, 'synthetic' => true], 'principal' => (string) ($units * FundedCommitment::UNIT)];
            $next += $units;
        }
        usort($commitments, fn (array $a, array $b): int => strcmp($a['id'], $b['id']));
        $campaign = ['campaign_id' => $id(), 'business_id' => $id(), 'business_name' => 'Synthetic Business', 'title' => 'Synthetic note',
            'exposure_reservation_id' => $id(), 'principal' => (string) (array_sum($commitmentUnits) * FundedCommitment::UNIT),
            'funded_at' => now('UTC')->startOfSecond()->toIso8601String(), 'term_months' => $termMonths, 'commitments' => $commitments];
        $this->store()->forever(self::PREFIX.'campaign:'.$campaign['campaign_id'], $campaign);
        $this->store()->forever(self::PREFIX.'campaigns', [...$this->store()->get(self::PREFIX.'campaigns', []), $campaign['campaign_id']]);
        $this->setDestination($campaign['business_id'], 'verified');

        return $this->campaign($campaign['campaign_id']);
    }

    public function scriptRecheck(string $campaignId, string $outcome, array $causes = []): void
    {
        $this->guard->assertAllowed();
        $this->store()->forever(self::PREFIX.'recheck:'.$campaignId, ['outcome' => $outcome, 'causes' => $causes]);
    }

    public function setDestination(string $businessId, string $state): void
    {
        $this->guard->assertAllowed();
        $current = $this->store()->get(self::PREFIX.'destination:'.$businessId);
        $revision = is_array($current) ? $current['revision'] + ($state === 'rotated' ? 1 : 0) : 1;
        $this->store()->forever(self::PREFIX.'destination:'.$businessId, ['id' => 'synthetic-destination-'.substr($businessId, -8), 'revision' => $revision,
            'state' => $state === 'rotated' ? 'verified' : $state, 'mandate_id' => 'synthetic-mandate-'.substr($businessId, -8),
            'account_token_sha256' => hash('sha256', 'synthetic-account|'.$businessId.'|'.$revision), 'environment' => $this->isolation->profile(),
            'evidence_sha256' => hash('sha256', 'synthetic-evidence|'.$businessId.'|'.$revision),
            'verified_at' => now('UTC')->subDay()->startOfSecond()->toIso8601String(),
            'expires_at' => ($state === 'expired' ? now('UTC')->subSecond() : now('UTC')->addYear())->startOfSecond()->toIso8601String(),
            'masked' => 'Synthetic Bank •••• '.str_pad((string) (1000 + $revision), 4, '0', STR_PAD_LEFT)]);
    }

    public function setConnection(?int $userId, string $state): void
    {
        $this->guard->assertAllowed();
        if ($userId === null) {
            $this->store()->forever(self::PREFIX.'connections', $state === 'unavailable' ? 'unavailable' : 'available');

            return;
        }
        $this->store()->forever(self::PREFIX.'connection:'.$userId, $state);
    }

    public function effects(string $campaignId): array
    {
        return $this->store()->get(self::PREFIX.'effects:'.$campaignId, []);
    }

    private function campaign(string $campaignId): FundedCampaign
    {
        $this->guard->assertAllowed();
        $campaign = $this->store()->get(self::PREFIX.'campaign:'.$campaignId);
        if (! is_array($campaign)) {
            throw new CommandRejection('FUNDING_SOURCE_UNAVAILABLE', 409);
        }

        return new FundedCampaign($campaign['campaign_id'], $campaign['business_id'], $campaign['business_name'], $campaign['title'],
            $campaign['exposure_reservation_id'], $campaign['principal'], $campaign['funded_at'], $campaign['term_months'],
            array_values(array_map(fn (array $commitment): FundedCommitment => new FundedCommitment($commitment['id'], $commitment['party_id'],
                $commitment['origin_operation_id'], $commitment['units'], $commitment['ordinals'], $commitment['rights'], $commitment['terms'],
                $commitment['principal']), $campaign['commitments'])));
    }

    /**
     * Records a funding effect once per closing, exclusive of the other kind, committed with the
     * caller's transaction and discarded by its rollback.
     *
     * @param  array<string, mixed>  $effect
     */
    private function record(FundedCampaign $campaign, array $effect): void
    {
        $this->assertTransaction();
        $current = $this->lockFunded($campaign->campaignId);
        $recorded = $this->effects($current->campaignId);
        foreach ($recorded as $previous) {
            if ($previous['closing_id'] === $effect['closing_id'] && $previous['kind'] === $effect['kind']) {
                return;
            }
            throw new DisbursementViolation('FUNDING_CLOSING_EXCLUSIVE');
        }
        DB::afterCommit(fn () => $this->store()->forever(self::PREFIX.'effects:'.$current->campaignId, [...$this->effects($current->campaignId), $effect]));
    }

    private function assertTransaction(): void
    {
        $this->guard->assertAllowed();
        // A transaction the caller opened, not merely an enclosing test transaction.
        if (app('db.transactions')->callbackApplicableTransactions()->isEmpty()) {
            throw new DisbursementViolation('FUNDING_TRANSACTION_REQUIRED');
        }
    }

    private function store(): Cache
    {
        return $this->cache->store();
    }
}
