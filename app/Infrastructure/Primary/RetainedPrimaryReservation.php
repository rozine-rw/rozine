<?php

declare(strict_types=1);

namespace App\Infrastructure\Primary;

use App\Application\Operations\Contracts\CanonicalJson;
use App\Domain\Primary\PrimaryReservation;
use App\Domain\Primary\PrimaryTerms;
use App\Domain\Primary\PrimaryViolation;
use App\Domain\Primary\ReservationWindow;
use App\Domain\Primary\UnitOrdinals;
use App\Domain\Primary\UnitRights;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Brick\Math\BigInteger;
use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;

/** Immutable root and full revision replay, without locks or writes.
 * @phpstan-type ReplayFacts array{id: string, publication_sha256: string, principal: string, units: string, rate_pct: string, term_months: int, policy_version: string, payments: list<string>, expires_at: DateTimeImmutable}
 */
final readonly class RetainedPrimaryReservation
{
    public function __construct(private CanonicalJson $json) {}

    /** @param ReplayFacts $campaign
     * @return array{PrimaryReservation, PrimaryReservationVersion}
     */
    public function read(PrimaryReservationRecord $root, array $campaign): array
    {
        $rights = $this->rights($root, $campaign);
        $previous = null;
        $reservation = null;
        try {
            $window = ReservationWindow::open($root->created_at->toDateTimeImmutable(), $campaign['expires_at']);
            if ($window->expiresAt != $root->expires_at->toDateTimeImmutable()) {
                throw new RuntimeException('RESERVATION_INTEGRITY_FAILED');
            }
            foreach (PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->orderBy('revision')->get() as $version) {
                $payload = $version->payload;
                $disclosed = $payload['terms'] ?? null;
                if (! is_array($disclosed) || ! is_string($disclosed['rate_pct'] ?? null) || ! is_int($disclosed['term_months'] ?? null)
                    || ! is_string($disclosed['policy_version'] ?? null) || ! is_string($disclosed['disclosure_version'] ?? null)
                    || ! is_array($disclosed['payout_fee'] ?? null) || ! is_string($disclosed['payout_fee']['amount'] ?? null)) {
                    throw new RuntimeException('RESERVATION_INTEGRITY_FAILED');
                }
                $terms = PrimaryTerms::disclosed($disclosed['rate_pct'], $disclosed['term_months'], $disclosed['policy_version'],
                    $disclosed['disclosure_version'], $disclosed['earnings_fee'] ?? null, $disclosed['payout_fee']['amount'], $rights);
                $terms->requireRights($rights);
                $at = $version->created_at->toDateTimeImmutable();
                if ($previous === null) {
                    if ($version->state !== 'held' || $version->operation_id !== $root->origin_operation_id || $at != $window->startsAt) {
                        throw new RuntimeException('RESERVATION_INTEGRITY_FAILED');
                    }
                    $reservation = PrimaryReservation::hold($rights, $terms, $window);
                } else {
                    if ($reservation->state !== 'held' || $at < $previous->created_at->toDateTimeImmutable()) {
                        throw new RuntimeException('RESERVATION_INTEGRITY_FAILED');
                    }
                    $reservation = match ($version->state) {
                        'held' => $reservation->requote($at, $terms),
                        'confirmed' => $reservation->confirm($at, $terms, $terms->disclosureVersion, $terms->disclosureSha256),
                        'released', 'expired' => $reservation->release($at),
                        default => throw new RuntimeException('RESERVATION_INTEGRITY_FAILED'),
                    };
                }
                if ($version->state !== $reservation->state || $version->revision !== ($previous === null ? 0 : $previous->revision) + 1 || $version->previous_sha256 !== $previous?->sha256
                    || $terms->policyVersion !== $campaign['policy_version'] || $terms->ratePercent !== $campaign['rate_pct'] || $terms->termMonths !== $campaign['term_months']
                    || ! hash_equals($version->sha256, $this->digest($payload))
                    || $this->json->encode($payload) !== $this->json->encode($this->versionPayload($root, $reservation, $version->operation_id, $previous, $at))) {
                    throw new RuntimeException('RESERVATION_INTEGRITY_FAILED');
                }
                $previous = $version;
            }
        } catch (PrimaryViolation $exception) {
            throw new RuntimeException('RESERVATION_INTEGRITY_FAILED', previous: $exception);
        }
        if ($reservation === null) {
            throw new RuntimeException('RESERVATION_INTEGRITY_FAILED');
        }

        return [$reservation, $previous];
    }

    /** Replay inputs from the bound encrypted purchase itself, never publication availability or current authority.
     * Every caller must subsequently read() the full root/revision chain; a retirement additionally binds its original SHA.
     *
     * @return ReplayFacts
     */
    public function facts(PrimaryReservationRecord $root): array
    {
        $payload = $root->payload;
        $initial = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->orderBy('revision')->first();
        $terms = $initial?->payload['terms'] ?? null;
        if (! is_string($payload['campaign_principal'] ?? null) || ! is_string($payload['campaign_units'] ?? null)
            || ! is_array($payload['campaign_payments'] ?? null) || ! array_is_list($payload['campaign_payments'])
            || ! is_array($terms) || ! is_string($terms['rate_pct'] ?? null) || ! is_int($terms['term_months'] ?? null)
            || ! is_string($terms['policy_version'] ?? null)) {
            throw new RuntimeException('RESERVATION_INTEGRITY_FAILED');
        }
        foreach ($payload['campaign_payments'] as $payment) {
            if (! is_string($payment)) {
                throw new RuntimeException('RESERVATION_INTEGRITY_FAILED');
            }
        }

        return ['id' => $root->business_campaign_id, 'publication_sha256' => $root->publication_sha256,
            'principal' => $payload['campaign_principal'], 'units' => $payload['campaign_units'],
            'payments' => $payload['campaign_payments'], 'rate_pct' => $terms['rate_pct'],
            'term_months' => $terms['term_months'], 'policy_version' => $terms['policy_version'],
            'expires_at' => $root->expires_at->toDateTimeImmutable()];
    }

    /** @return array<string, mixed> */
    public function versionPayload(PrimaryReservationRecord $root, PrimaryReservation $reservation, ?string $operationId,
        ?PrimaryReservationVersion $previous, DateTimeImmutable $at): array
    {
        return ['contract' => 'primary-reservation-version-1', 'reservation_id' => $root->id, 'reservation_sha256' => $root->sha256,
            'revision' => ($previous === null ? 0 : $previous->revision) + 1, 'state' => $reservation->state, 'operation_id' => $operationId, 'previous_sha256' => $previous?->sha256,
            'terms' => $reservation->terms->toArray(), 'disclosure_sha256' => $reservation->terms->disclosureSha256, 'recorded_at' => $this->instant($at)];
    }

    /** @param ReplayFacts $campaign */
    public function rights(PrimaryReservationRecord $record, array $campaign): UnitRights
    {
        $payload = $record->payload;
        $ranges = $payload['ordinals'] ?? null;
        if (! is_array($ranges) || ! array_is_list($ranges)) {
            throw new RuntimeException('RESERVATION_INTEGRITY_FAILED');
        }
        foreach ($ranges as $range) {
            if (! is_array($range) || ! is_string($range['first'] ?? null) || ! is_string($range['last'] ?? null)) {
                throw new RuntimeException('RESERVATION_INTEGRITY_FAILED');
            }
        }
        try {
            $ordinals = UnitOrdinals::fromRanges($campaign['units'], $ranges);
            $rights = UnitRights::allocate($campaign['principal'], $campaign['payments'], $ordinals);
        } catch (PrimaryViolation $exception) {
            throw new RuntimeException('RESERVATION_INTEGRITY_FAILED', previous: $exception);
        }
        if ($record->ordinal_ranges !== $this->ordinalRanges($ordinals) || $record->publication_sha256 !== $campaign['publication_sha256'] || (string) $ordinals->count !== (string) $record->units
            || (string) $rights->principal !== $record->principal || ! hash_equals($record->sha256, $this->digest($payload))
            || $this->json->encode($payload) !== $this->json->encode($this->rootPayload($record, $campaign, $rights))) {
            throw new RuntimeException('RESERVATION_INTEGRITY_FAILED');
        }

        return $rights;
    }

    public function ordinalRanges(UnitOrdinals $ordinals): string
    {
        return '{'.implode(',', array_map(fn (array $range): string => '['.$range['first'].','.BigInteger::of($range['last'])->plus(1).')', $ordinals->ranges)).'}';
    }

    /**
     * @param  ReplayFacts  $campaign
     * @return array<string, mixed>
     */
    public function rootPayload(PrimaryReservationRecord $record, array $campaign, UnitRights $rights): array
    {
        return ['contract' => 'primary-reservation-1', 'reservation_id' => $record->id, 'campaign_id' => $campaign['id'],
            'publication_sha256' => $campaign['publication_sha256'], 'party_id' => $record->party_id, 'origin_operation_id' => $record->origin_operation_id,
            'units' => (string) $record->units, 'principal' => $record->principal,
            'campaign_principal' => $campaign['principal'], 'campaign_units' => $campaign['units'], 'campaign_payments' => $campaign['payments'],
            'ordinals' => $rights->ordinals->ranges, 'rights' => $rights->toArray(),
            'created_at' => $this->instant($record->created_at->toDateTimeImmutable()), 'expires_at' => $this->instant($record->expires_at->toDateTimeImmutable())];
    }

    /** @param array<string, mixed> $payload */
    private function digest(array $payload): string
    {
        return hash('sha256', $this->json->encode($payload));
    }

    private function instant(DateTimeImmutable $at): string
    {
        return $at->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s.u\Z');
    }
}
