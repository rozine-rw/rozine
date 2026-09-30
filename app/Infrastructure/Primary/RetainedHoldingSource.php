<?php

declare(strict_types=1);

namespace App\Infrastructure\Primary;

use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Primary\Contracts\CampaignFundingEvidence;
use App\Application\Primary\Contracts\HoldingSource;
use App\Domain\Primary\PrimaryReservation;
use App\Domain\Primary\PrimaryTerms;
use App\Domain\Primary\PrimaryViolation;
use App\Domain\Primary\ReservationWindow;
use App\Domain\Primary\UnitOrdinals;
use App\Domain\Primary\UnitRights;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use ErrorException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use TypeError;

/**
 * Replays a funded commitment's acknowledged disclosure from retained evidence only. The funding
 * evidence must exist and verify (it re-derives the funding, reservation and revision digests).
 * Then the rights are re-allocated from the retained campaign schedule and ordinals, every retained
 * revision is replayed through the domain at its own recorded instant up to the confirmation, and
 * the confirmed terms, their disclosure digest and the rights must equal what the revision, the
 * reservation and the funding record each retain. Current campaign inputs, fee policy and tiers
 * are never read, so nothing is repriced. No locks, no writes.
 */
final readonly class RetainedHoldingSource implements HoldingSource
{
    /** PostgreSQL bounds a campaign at 20,000 units, so every retained ordinal converts to an integer without loss. */
    private const string MAXIMUM_UNITS = '20000';

    public function __construct(private CampaignFundingEvidence $fundings, private CanonicalJson $json) {}

    public function facts(string $commitmentId): array
    {
        $commitment = PrimaryCommitment::query()->whereKey($commitmentId)->first();
        $root = PrimaryReservationRecord::query()->whereKey($commitment?->primary_reservation_id)->first();
        $funding = $root === null ? null : $this->fundings->find($root->business_campaign_id);
        if ($commitment === null || $root === null || $funding === null) {
            throw new RuntimeException('PRIMARY_HOLDING_SOURCE_UNAVAILABLE');
        }
        $purchase = Arr::first(Arr::wrap($funding['commitments'] ?? null), fn (mixed $purchase): bool => Arr::get($purchase, 'commitment_id') === $commitment->id);
        $evidence = $root->payload;
        try {
            $ordinals = UnitOrdinals::fromRanges($evidence['campaign_units'], $evidence['ordinals']);
            $rights = UnitRights::allocate($evidence['campaign_principal'], $evidence['campaign_payments'], $ordinals);
            $window = ReservationWindow::open($root->created_at->toDateTimeImmutable(), $root->expires_at->toDateTimeImmutable());
            $reservation = null;
            $terms = null;
            $confirmed = null;
            foreach (PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->orderBy('revision')->get() as $version) {
                $disclosed = $version->payload['terms'];
                $terms = PrimaryTerms::disclosed($disclosed['rate_pct'], $disclosed['term_months'], $disclosed['policy_version'],
                    $disclosed['disclosure_version'], $disclosed['earnings_fee'], $disclosed['payout_fee']['amount'], $rights);
                $at = $version->created_at->toDateTimeImmutable();
                $reservation = match (true) {
                    $reservation === null => PrimaryReservation::hold($rights, $terms, $window),
                    $version->state === 'held' => $reservation->requote($at, $terms),
                    default => $reservation->confirm($at, $terms, $disclosed['disclosure_version'], $version->payload['disclosure_sha256']),
                };
                $confirmed = $version;
            }
        } catch (PrimaryViolation|TypeError|ErrorException $exception) {
            throw new RuntimeException('PRIMARY_HOLDING_SOURCE_INTEGRITY_FAILED', previous: $exception);
        }
        if ($reservation?->state !== 'confirmed' || $terms === null || $confirmed->id !== $commitment->primary_reservation_version_id
            || $ordinals->ranges !== $evidence['ordinals'] || $ordinals->totalUnits->isGreaterThan(self::MAXIMUM_UNITS)
            || (string) $ordinals->count !== (string) $root->units || (string) $rights->principal !== $root->principal
            || $this->json->encode(['rights' => $rights->toArray(), 'terms' => $terms->toArray(), 'disclosure_sha256' => $terms->disclosureSha256])
                !== $this->json->encode(['rights' => $evidence['rights'], 'terms' => $confirmed->payload['terms'], 'disclosure_sha256' => $confirmed->payload['disclosure_sha256']])
            || $this->json->encode(['rights' => $rights->toArray(), 'terms' => $terms->toArray(), 'disclosure_sha256' => $terms->disclosureSha256, 'ordinals' => $ordinals->ranges])
                !== $this->json->encode(['rights' => Arr::get($purchase, 'rights'), 'terms' => Arr::get($purchase, 'terms'),
                    'disclosure_sha256' => Arr::get($purchase, 'disclosure_sha256'), 'ordinals' => Arr::get($purchase, 'ordinals')])) {
            throw new RuntimeException('PRIMARY_HOLDING_SOURCE_INTEGRITY_FAILED');
        }

        return ['business_campaign_id' => $root->business_campaign_id, 'commitment_id' => $commitment->id, 'primary_reservation_id' => $root->id,
            'party_id' => $root->party_id, 'units' => $root->units, 'principal' => $root->principal,
            'ordinals' => array_map(fn (array $range): array => ['first' => (int) $range['first'], 'last' => (int) $range['last']], $ordinals->ranges),
            'rights' => $rights->toArray(), 'terms' => $terms->toArray(), 'reservation_sha256' => $root->sha256,
            'confirmation_version_id' => $confirmed->id, 'confirmation_revision' => $confirmed->revision, 'confirmation_sha256' => $confirmed->sha256];
    }

    public function verify(string $holdingId): void
    {
        $holding = DB::table('primary_holdings')->where('id', $holdingId)->first();
        if ($holding === null) {
            throw new RuntimeException('PRIMARY_HOLDING_NOT_FOUND');
        }
        $held = ['business_campaign_id' => $holding->business_campaign_id, 'commitment_id' => $holding->commitment_id,
            'primary_reservation_id' => $holding->primary_reservation_id, 'party_id' => $holding->party_id, 'units' => $holding->units,
            'principal' => $holding->principal, 'ordinals' => json_decode($holding->ordinals, true, 512, JSON_THROW_ON_ERROR),
            'rights' => json_decode($holding->rights, true, 512, JSON_THROW_ON_ERROR), 'terms' => json_decode($holding->terms, true, 512, JSON_THROW_ON_ERROR),
            'reservation_sha256' => $holding->reservation_sha256, 'confirmation_version_id' => $holding->confirmation_version_id,
            'confirmation_revision' => $holding->confirmation_revision, 'confirmation_sha256' => $holding->confirmation_sha256];
        if ($this->json->encode($held) !== $this->json->encode($this->facts($holding->commitment_id))) {
            throw new RuntimeException('PRIMARY_HOLDING_SOURCE_MISMATCH');
        }
    }
}
