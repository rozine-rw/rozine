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
use DateTimeImmutable;
use DateTimeZone;
use ErrorException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use TypeError;

/**
 * Replays a funded commitment's acknowledged disclosure from retained evidence only. The funding
 * evidence must exist and verify (it re-derives the funding, reservation and revision digests).
 * Then the rights are re-allocated from the retained campaign schedule and ordinals, every retained
 * revision is authenticated (its digest, its predecessor's digest, its revision and its recorded instant)
 * and replayed through the domain at its own recorded instant up to the confirmation, and
 * the confirmed terms, their disclosure digest and the rights must equal what the revision, the
 * reservation and the funding record each retain. Current campaign inputs, fee policy and tiers
 * are never read, so nothing is repriced. No locks, no writes.
 *
 * @phpstan-import-type HoldingFacts from HoldingSource
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

        return $this->replay($commitment, $root, $funding,
            PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->orderBy('revision')->get());
    }

    public function campaignFacts(string $campaignId): array
    {
        $funding = $this->fundings->find($campaignId);
        if ($funding === null) {
            throw new RuntimeException('PRIMARY_HOLDING_SOURCE_UNAVAILABLE');
        }
        $purchases = Arr::wrap($funding['commitments'] ?? null);
        $ids = array_map(fn (mixed $purchase): mixed => Arr::get($purchase, 'commitment_id'), $purchases);
        if (count(array_unique($ids, SORT_REGULAR)) !== count($purchases)) {
            throw new RuntimeException('PRIMARY_HOLDING_SOURCE_INTEGRITY_FAILED');
        }
        $commitments = PrimaryCommitment::query()->whereIn('id', $ids)->get()->keyBy('id');
        $roots = PrimaryReservationRecord::query()->whereIn('id', $commitments->pluck('primary_reservation_id')->all())->get()->keyBy('id');
        $versions = PrimaryReservationVersion::query()->whereIn('primary_reservation_id', $roots->keys()->all())
            ->orderBy('primary_reservation_id')->orderBy('revision')->get()->groupBy('primary_reservation_id');
        $facts = [];
        foreach ($ids as $id) {
            $commitment = $commitments->get($id);
            $root = $roots->get($commitment?->primary_reservation_id);
            if ($commitment === null || $root === null) {
                throw new RuntimeException('PRIMARY_HOLDING_SOURCE_UNAVAILABLE');
            }
            $facts[$commitment->id] = $this->replay($commitment, $root, $funding, $versions->get($root->id) ?? []);
        }
        ksort($facts, SORT_STRING);

        return $facts;
    }

    /**
     * Replays one funded commitment's retained revisions against the already verified funding evidence of its campaign.
     *
     * @param  array<string, mixed>  $funding
     * @param  iterable<PrimaryReservationVersion>  $versions  every retained revision of the root, in revision order
     * @return HoldingFacts
     */
    private function replay(PrimaryCommitment $commitment, PrimaryReservationRecord $root, array $funding, iterable $versions): array
    {
        $purchase = Arr::first(Arr::wrap($funding['commitments'] ?? null), fn (mixed $purchase): bool => Arr::get($purchase, 'commitment_id') === $commitment->id);
        $evidence = $root->payload;
        try {
            $ordinals = UnitOrdinals::fromRanges($evidence['campaign_units'], $evidence['ordinals']);
            $rights = UnitRights::allocate($evidence['campaign_principal'], $evidence['campaign_payments'], $ordinals);
            $window = ReservationWindow::open($root->created_at->toDateTimeImmutable(), $root->expires_at->toDateTimeImmutable());
            $reservation = null;
            $terms = null;
            $confirmed = null;
            foreach ($versions as $version) {
                $payload = $version->payload;
                $disclosed = $payload['terms'];
                $terms = PrimaryTerms::disclosed($disclosed['rate_pct'], $disclosed['term_months'], $disclosed['policy_version'],
                    $disclosed['disclosure_version'], $disclosed['earnings_fee'], $disclosed['payout_fee']['amount'], $rights);
                $at = $version->created_at->toDateTimeImmutable();
                $reservation = match (true) {
                    $reservation === null => PrimaryReservation::hold($rights, $terms, $window),
                    $version->state === 'held' => $reservation->requote($at, $terms),
                    $version->state === 'confirmed' => $reservation->confirm($at, $terms, $disclosed['disclosure_version'], $payload['disclosure_sha256']),
                    default => throw new RuntimeException('PRIMARY_HOLDING_SOURCE_INTEGRITY_FAILED'),
                };
                $this->requireRevision($root, $version, $confirmed, $reservation, $at);
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

    /**
     * Authenticates one consumed revision before its replay is trusted: its stored digest must be its payload's,
     * it must follow its predecessor in revision, instant and digest, and its payload must be exactly what that
     * revision records for the replayed state at its own recorded instant. Read-only; it takes no locks.
     */
    private function requireRevision(PrimaryReservationRecord $root, PrimaryReservationVersion $version, ?PrimaryReservationVersion $previous,
        PrimaryReservation $reservation, DateTimeImmutable $at): void
    {
        $payload = $version->payload;
        $expected = ['contract' => 'primary-reservation-version-1', 'reservation_id' => $root->id, 'reservation_sha256' => $root->sha256,
            'revision' => ($previous === null ? 0 : $previous->revision) + 1, 'state' => $reservation->state, 'operation_id' => $version->operation_id,
            'previous_sha256' => $previous?->sha256, 'terms' => $reservation->terms->toArray(), 'disclosure_sha256' => $reservation->terms->disclosureSha256,
            'recorded_at' => $at->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s.u\Z')];
        if (! hash_equals($version->sha256, hash('sha256', $this->json->encode($payload)))
            || $version->revision !== $expected['revision'] || $version->state !== $reservation->state || $version->previous_sha256 !== $previous?->sha256
            || ($previous === null ? $version->operation_id !== $root->origin_operation_id || $at != $root->created_at->toDateTimeImmutable()
                : $at < $previous->created_at->toDateTimeImmutable())
            || $this->json->encode($payload) !== $this->json->encode($expected)) {
            throw new RuntimeException('PRIMARY_HOLDING_SOURCE_INTEGRITY_FAILED');
        }
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
