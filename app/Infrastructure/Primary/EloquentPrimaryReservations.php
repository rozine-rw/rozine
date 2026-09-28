<?php

declare(strict_types=1);

namespace App\Infrastructure\Primary;

use App\Application\Business\Contracts\PrimaryCampaignSource;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Application\Primary\ReservedCheckout;
use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\PostingSource;
use App\Domain\Operations\CommandRejection;
use App\Domain\Primary\PrimaryReservation;
use App\Domain\Primary\PrimaryViolation;
use App\Domain\Primary\ReservationWindow;
use App\Domain\Primary\UnitOrdinals;
use App\Domain\Primary\UnitRights;
use App\Domain\Wallet\WalletMoney;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Brick\Math\BigInteger;
use Closure;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/** @phpstan-import-type CampaignInput from PrimaryCampaignSource */
final readonly class EloquentPrimaryReservations implements PrimaryReservations
{
    public function __construct(private PrimaryCampaignSource $campaigns, private WalletPostings $wallets, private CanonicalJson $json) {}

    public function reserve(string $campaignId, string $partyId, string $originOperationId, string $units, Closure $admit): ReservedCheckout
    {
        if (DB::transactionLevel() === 0) {
            throw new CommandRejection('PRIMARY_TRANSACTION_REQUIRED');
        }

        try {
            return DB::transaction(function () use ($campaignId, $partyId, $originOperationId, $units, $admit): ReservedCheckout {
                $campaign = $this->campaigns->lock($campaignId);
                $quantity = UnitOrdinals::quantity($units);
                $occupied = [];
                $partyUnits = BigInteger::zero();
                $records = PrimaryReservationRecord::query()->where('business_campaign_id', $campaign['id'])->orderBy('id')->lockForUpdate()->get();
                foreach ($records as $record) {
                    $ordinals = $this->retainedOrdinals($record, $campaign);
                    $occupied = [...$occupied, ...$ordinals->ranges];
                    if ($record->party_id === $partyId) {
                        $partyUnits = $partyUnits->plus($ordinals->count);
                    }
                }
                try {
                    UnitOrdinals::fromRanges($campaign['units'], $occupied);
                } catch (PrimaryViolation $exception) {
                    throw new RuntimeException('RESERVATION_INTEGRITY_FAILED', previous: $exception);
                }
                if ($partyUnits->plus($quantity)->multipliedBy(2)->isGreaterThan($campaign['units'])) {
                    throw new CommandRejection('INVESTOR_CAMPAIGN_CAP_EXCEEDED', 422);
                }
                $rights = UnitRights::allocate($campaign['principal'], $campaign['payments'], UnitOrdinals::reserve($campaign['units'], $occupied, $units));
                $terms = $admit($rights, $campaign);
                if ($terms->policyVersion !== $campaign['policy_version'] || $terms->ratePercent !== $campaign['rate_pct'] || $terms->termMonths !== $campaign['term_months']) {
                    throw new PrimaryViolation('INVALID_PRIMARY_TERMS');
                }
                $reservation = PrimaryReservation::hold($rights, $terms, ReservationWindow::open(now('UTC')->toDateTimeImmutable(), $campaign['expires_at']));
                $id = strtolower((string) Str::ulid());
                $source = new PostingSource('primary_reservation', $id, $originOperationId);
                $root = new PrimaryReservationRecord;
                $root->forceFill(['id' => $id, 'business_campaign_id' => $campaign['id'], 'publication_sha256' => $campaign['publication_sha256'],
                    'party_id' => $partyId, 'origin_operation_id' => $originOperationId, 'units' => $quantity->toInt(), 'principal' => (string) $rights->principal, 'ordinal_ranges' => $this->ordinalRanges($rights->ordinals),
                    'created_at' => $reservation->window->startsAt, 'expires_at' => $reservation->window->expiresAt]);
                $payload = $this->rootPayload($root, $campaign, $rights);
                $root->forceFill(['payload' => $payload, 'sha256' => $this->digest($payload)])->save();
                $version = ['contract' => 'primary-reservation-version-1', 'reservation_id' => $id, 'reservation_sha256' => $root->sha256,
                    'revision' => 1, 'state' => 'held', 'operation_id' => $originOperationId, 'previous_sha256' => null,
                    'terms' => $terms->toArray(), 'disclosure_sha256' => $terms->disclosureSha256, 'recorded_at' => $this->instant($reservation->window->startsAt)];
                (new PrimaryReservationVersion)->forceFill(['primary_reservation_id' => $id, 'revision' => 1, 'state' => 'held',
                    'operation_id' => $originOperationId, 'previous_sha256' => null, 'payload' => $version, 'sha256' => $this->digest($version),
                    'created_at' => $reservation->window->startsAt])->save();
                $hold = $this->wallets->hold($this->wallets->lockForParty($partyId), WalletMoney::of((string) $rights->principal), $source);

                return new ReservedCheckout($id, $campaign['id'], $partyId, $originOperationId, $reservation, $hold);
            });
        } catch (PrimaryViolation $exception) {
            throw new CommandRejection($exception->reasonCode, $exception->reasonCode === 'INVALID_UNITS' ? 422 : 409);
        }
    }

    /** @param CampaignInput $campaign */
    private function retainedOrdinals(PrimaryReservationRecord $record, array $campaign): UnitOrdinals
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

        return $ordinals;
    }

    private function ordinalRanges(UnitOrdinals $ordinals): string
    {
        return '{'.implode(',', array_map(fn (array $range): string => '['.$range['first'].','.BigInteger::of($range['last'])->plus(1).')', $ordinals->ranges)).'}';
    }

    /**
     * @param  CampaignInput  $campaign
     * @return array<string, mixed>
     */
    private function rootPayload(PrimaryReservationRecord $record, array $campaign, UnitRights $rights): array
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
