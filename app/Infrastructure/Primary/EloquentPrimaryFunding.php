<?php

declare(strict_types=1);

namespace App\Infrastructure\Primary;

use App\Application\Business\Contracts\BusinessNoticeAudience;
use App\Application\Business\Contracts\PrimaryCampaignSource;
use App\Application\Identity\Contracts\PartyNotices;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Operations\Contracts\ChangeFeed;
use App\Application\Primary\Contracts\CampaignFundingEvidence;
use App\Application\Primary\Contracts\PrimaryFunding;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Application\Primary\PrimaryFundingCandidate;
use App\Domain\Identity\PartyNoticeKind;
use App\Domain\Operations\ChangeScope;
use App\Domain\Operations\CommandRejection;
use App\Domain\Primary\FundingAdmission;
use App\Models\PrimaryCampaignFunding;
use App\Models\PrimaryCommitment;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

final class EloquentPrimaryFunding implements PrimaryFunding
{
    public function __construct(private PrimaryCampaignSource $campaigns, private PrimaryReservations $reservations, private CanonicalJson $json, private CampaignFundingEvidence $fundings,
        private ChangeFeed $changes, private PartyNotices $notices, private BusinessNoticeAudience $audience) {}

    public function lock(string $campaignId, Closure $admit): array
    {
        if (DB::transactionLevel() === 0) {
            throw new CommandRejection('PRIMARY_TRANSACTION_REQUIRED');
        }

        return DB::transaction(function () use ($campaignId, $admit): array {
            $this->campaigns->lockBusiness($campaignId);
            $admission = $admit($campaignId);
            if ($admission === []) {
                throw new CommandRejection('POLICY_INPUT_REQUIRED');
            }
            $campaign = $this->campaigns->lockForFunding($campaignId);
            $refusal = FundingAdmission::rejectionReason($admission, $campaignId, $campaign['publication_sha256']);
            if ($refusal !== null) {
                throw new CommandRejection($refusal);
            }
            $candidate = $this->reservations->lockFundingCandidate($campaignId);
            $purchases = $this->purchases($candidate);
            $retained = $this->fundings->find($campaignId);
            if ($retained !== null) {
                if ($this->json->encode(['commitments' => $retained['commitments']]) !== $this->json->encode(['commitments' => $purchases])) {
                    throw new RuntimeException('PRIMARY_FUNDING_INTEGRITY_FAILED');
                }

                return $retained;
            }
            $funding = new PrimaryCampaignFunding;
            $funding->id = strtolower((string) Str::ulid());
            $at = now('UTC')->toImmutable();
            $payload = ['scope' => 'primary-funding-v1', 'funding_id' => $funding->id, 'campaign_id' => $campaignId,
                'business_id' => $campaign['business_id'], 'exposure_reservation_id' => $campaign['exposure_reservation_id'],
                'publication_sha256' => $candidate->publicationSha256, 'principal' => $candidate->principal,
                'recorded_at' => $at->format('Y-m-d\TH:i:s.u\Z'), 'admission' => $admission, 'commitments' => $purchases];
            $funding->forceFill(['business_campaign_id' => $campaignId, 'business_id' => $campaign['business_id'],
                'exposure_reservation_id' => $campaign['exposure_reservation_id'], 'publication_sha256' => $candidate->publicationSha256,
                'principal' => $candidate->principal, 'payload' => $payload, 'sha256' => hash('sha256', $this->json->encode($payload)), 'created_at' => $at])->save();
            foreach ($purchases as $purchase) {
                DB::table('primary_funding_commitments')->insert(['funding_id' => $funding->id,
                    'commitment_id' => $purchase['commitment_id'], 'reservation_id' => $purchase['reservation_id'],
                    'commit_entry_id' => $purchase['cash']['commit_entry_id'], 'hold_entry_id' => $purchase['cash']['hold_entry_id'],
                    'wallet_id' => $purchase['cash']['wallet_id'], 'origin_operation_id' => $purchase['cash']['origin_operation_id']]);
                $this->changes->record(ChangeScope::party($purchase['party_id']), 'purchase', $purchase['reservation_id']);
            }
            $this->noticeFunded($campaignId, $campaign['business_id'], (string) $candidate->principal, $purchases);

            return $payload;
        });
    }

    /**
     * Tells each buying Party, with its units and amount, and the Business that the raise is fully
     * funded (BRS v2.0 NTF-R01), once per campaign.
     *
     * @param  list<array<string, mixed>>  $purchases
     */
    private function noticeFunded(string $campaignId, string $businessId, string $principal, array $purchases): void
    {
        $presentation = $this->campaigns->presentation($campaignId);
        $held = [];
        foreach ($purchases as $purchase) {
            $party = (string) $purchase['party_id'];
            $held[$party] = [($held[$party][0] ?? 0) + (int) $purchase['units'], ($held[$party][1] ?? 0) + (int) $purchase['principal']];
        }
        ksort($held, SORT_STRING);
        foreach ($held as $party => [$units, $amount]) {
            $this->notices->record($party, PartyNoticeKind::RaiseFunded, ['business' => $presentation['business'], 'title' => $presentation['title'],
                'units' => (string) $units, 'amount' => (string) $amount, 'currency' => 'RWF', 'campaign_id' => $campaignId], 'raise-funded:'.$campaignId.':'.$party);
        }
        $this->notices->record($this->audience->entityParty($businessId), PartyNoticeKind::BusinessRaiseFunded, ['title' => $presentation['title'],
            'principal' => $principal, 'investors' => (string) count($held), 'currency' => 'RWF', 'business_id' => $businessId, 'campaign_id' => $campaignId],
            'business-raise-funded:'.$campaignId);
    }

    /** @return list<array<string, mixed>> */
    private function purchases(PrimaryFundingCandidate $candidate): array
    {
        return array_map(function (array $purchase): array {
            $reservation = $purchase['reservation'];
            $commitment = PrimaryCommitment::query()->whereKey($purchase['commitment_id'])->firstOrFail();
            $cash = $purchase['cash'];

            return ['commitment_id' => $purchase['commitment_id'], 'reservation_id' => $purchase['reservation_id'],
                'confirmation_version_id' => $commitment->primary_reservation_version_id, 'party_id' => $purchase['party_id'],
                'units' => (string) $reservation->rights->ordinals->count, 'principal' => (string) $reservation->rights->principal,
                'ordinals' => $reservation->rights->ordinals->ranges, 'rights' => $reservation->rights->toArray(), 'terms' => $reservation->terms->toArray(),
                'disclosure_sha256' => $reservation->terms->disclosureSha256,
                'cash' => ['hold_entry_id' => $cash->holdEntryId, 'commit_entry_id' => $cash->commitEntryId,
                    'wallet_id' => $cash->walletId, 'origin_operation_id' => $cash->originOperationId, 'amount' => $cash->amount]];
        }, $candidate->purchases);
    }
}
