<?php

declare(strict_types=1);

namespace App\Infrastructure\Primary;

use App\Application\Business\Contracts\PrimaryCampaignSource;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Operations\Contracts\ChangeFeed;
use App\Application\Primary\Contracts\CampaignFundingEvidence;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Application\Primary\PrimaryCampaignReturns;
use App\Application\Primary\PrimaryFundingCandidate;
use App\Application\Primary\ReservationConfirmation;
use App\Application\Primary\ReservationRefund;
use App\Application\Primary\ReservationRelease;
use App\Application\Primary\ReservedCheckout;
use App\Application\Wallet\Contracts\PrimaryCommittedCash;
use App\Application\Wallet\Contracts\PrimaryReturnedCash;
use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\PostingSource;
use App\Domain\Operations\ChangeScope;
use App\Domain\Operations\CommandRejection;
use App\Domain\Primary\PrimaryReservation;
use App\Domain\Primary\PrimaryTerms;
use App\Domain\Primary\PrimaryViolation;
use App\Domain\Primary\ReservationWindow;
use App\Domain\Primary\UnitOrdinals;
use App\Domain\Primary\UnitRights;
use App\Domain\Wallet\WalletMoney;
use App\Domain\Wallet\WalletViolation;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Brick\Math\BigInteger;
use Closure;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/** @phpstan-import-type CampaignInput from PrimaryCampaignSource */
final readonly class EloquentPrimaryReservations implements PrimaryReservations
{
    public function __construct(private PrimaryCampaignSource $campaigns, private WalletPostings $wallets, private CanonicalJson $json, private PrimaryCommittedCash $cash, private PrimaryReturnedCash $returnedCash, private CampaignFundingEvidence $fundings, private ChangeFeed $changes) {}

    public function reserve(string $campaignId, string $partyId, string $originOperationId, string $units, Closure $admit): ReservedCheckout
    {
        if (DB::transactionLevel() === 0) {
            throw new CommandRejection('PRIMARY_TRANSACTION_REQUIRED');
        }

        try {
            return DB::transaction(function () use ($campaignId, $partyId, $originOperationId, $units, $admit): ReservedCheckout {
                $campaign = $this->campaigns->lock($campaignId);
                $this->campaigns->rejectKnownConnections($campaign['business_id'], [$partyId]);
                $quantity = UnitOrdinals::quantity($units);
                $occupied = [];
                $partyUnits = BigInteger::zero();
                $records = PrimaryReservationRecord::query()->where('business_campaign_id', $campaign['id'])->orderBy('id')->lockForUpdate()->get();
                foreach ($records as $record) {
                    $ordinals = $this->retainedRights($record, $campaign)->ordinals;
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
                $this->appendVersion($root, $reservation, $originOperationId, null, $reservation->window->startsAt);
                $hold = $this->wallets->hold($this->wallets->lockForParty($partyId), WalletMoney::of((string) $rights->principal), $source);

                return new ReservedCheckout($id, $campaign['id'], $partyId, $originOperationId, $reservation, $hold);
            });
        } catch (PrimaryViolation $exception) {
            throw new CommandRejection($exception->reasonCode, $exception->reasonCode === 'INVALID_UNITS' ? 422 : 409);
        }
    }

    public function confirm(string $campaignId, string $reservationId, string $partyId, string $operationId,
        int $expectedRevision, string $disclosureVersion, string $disclosureSha256, Closure $admit): ReservationConfirmation
    {
        if (DB::transactionLevel() === 0) {
            throw new CommandRejection('PRIMARY_TRANSACTION_REQUIRED');
        }
        try {
            return DB::transaction(function () use ($campaignId, $reservationId, $partyId, $operationId, $expectedRevision, $disclosureVersion, $disclosureSha256, $admit): ReservationConfirmation {
                $campaign = $this->campaigns->lockRetained($campaignId);
                $root = PrimaryReservationRecord::query()->where('business_campaign_id', $campaign['id'])->where('party_id', $partyId)
                    ->whereKey($reservationId)->lockForUpdate()->first() ?? throw new CommandRejection('RESERVATION_NOT_FOUND', 404);
                [$reservation, $previous] = $this->retainedReservation($root, $campaign);
                if ($previous->revision !== $expectedRevision) {
                    throw new CommandRejection('VERSION_CONFLICT', 409, $previous->revision);
                }
                if ($reservation->state !== 'held') {
                    throw new CommandRejection('RESERVATION_NOT_HELD', 409, $previous->revision);
                }
                try {
                    $reservation->window->requireOpen(now('UTC')->toDateTimeImmutable());
                    $this->campaigns->lock($campaignId);
                    $this->campaigns->rejectKnownConnections($campaign['business_id'], [$partyId]);
                    $terms = $admit($reservation->rights, $campaign);
                    if ($terms->policyVersion !== $campaign['policy_version'] || $terms->ratePercent !== $campaign['rate_pct'] || $terms->termMonths !== $campaign['term_months']) {
                        throw new PrimaryViolation('INVALID_PRIMARY_TERMS');
                    }
                    $terms->requireRights($reservation->rights);
                    $at = now('UTC')->toDateTimeImmutable();
                    if ($terms->disclosureSha256 !== $reservation->terms->disclosureSha256) {
                        $requote = $reservation->requote($at, $terms);
                        $version = $this->appendVersion($root, $requote, $operationId, $previous, $at);

                        return new ReservationConfirmation($root->id, $version->revision, $requote, null, null);
                    }
                    $confirmed = $reservation->confirm($at, $terms, $disclosureVersion, $disclosureSha256);
                    $version = $this->appendVersion($root, $confirmed, $operationId, $previous, $at);
                    $commitment = new PrimaryCommitment;
                    $commitment->forceFill(['primary_reservation_id' => $root->id, 'primary_reservation_version_id' => $version->id,
                        'operation_id' => $operationId, 'confirmed_at' => $at, 'created_at' => $at])->save();
                    $posting = $this->wallets->commit($this->wallets->lockForParty($partyId), WalletMoney::of($root->principal),
                        new PostingSource('primary_reservation', $root->id, $root->origin_operation_id));

                    if ($posting->replayed) {
                        throw new RuntimeException('RESERVATION_INTEGRITY_FAILED');
                    }

                    return new ReservationConfirmation($root->id, $version->revision, $confirmed, $commitment->id, $posting);
                } catch (PrimaryViolation $exception) {
                    if ($exception->reasonCode === 'RESERVATION_EXPIRED') {
                        throw new CommandRejection('RESERVATION_EXPIRED', 409, $previous->revision + 1,
                            data: ['reservation_id' => $root->id, 'amount' => $root->principal]);
                    }
                    throw $exception;
                }
            });
        } catch (PrimaryViolation $exception) {
            throw new CommandRejection($exception->reasonCode);
        }
    }

    public function release(string $campaignId, string $reservationId, string $partyId, string $operationId, int $expectedRevision): ReservationRelease
    {
        if (DB::transactionLevel() === 0) {
            throw new CommandRejection('PRIMARY_TRANSACTION_REQUIRED');
        }
        try {
            return DB::transaction(function () use ($campaignId, $reservationId, $partyId, $operationId, $expectedRevision): ReservationRelease {
                $campaign = $this->campaigns->lockRetained($campaignId);
                $root = PrimaryReservationRecord::query()->where('business_campaign_id', $campaign['id'])->where('party_id', $partyId)
                    ->whereKey($reservationId)->lockForUpdate()->first() ?? throw new CommandRejection('RESERVATION_NOT_FOUND', 404);
                [$reservation, $previous] = $this->retainedReservation($root, $campaign);
                if ($previous->revision !== $expectedRevision) {
                    throw new CommandRejection('VERSION_CONFLICT', 409, $previous->revision);
                }
                $at = now('UTC')->toDateTimeImmutable();
                $released = $reservation->release($at);
                if ($released->state === 'expired') {
                    throw new CommandRejection('RESERVATION_EXPIRED', 409, $previous->revision + ($reservation->state === 'held' ? 1 : 0),
                        data: ['reservation_id' => $root->id, 'amount' => $root->principal]);
                }

                return $this->releaseCash($root, $released, $previous, $operationId, $at);
            });
        } catch (PrimaryViolation $exception) {
            throw new CommandRejection($exception->reasonCode);
        }
    }

    public function refund(string $campaignId, string $reservationId, string $partyId, int $expectedRevision): ReservationRefund
    {
        if (DB::transactionLevel() === 0) {
            throw new CommandRejection('PRIMARY_TRANSACTION_REQUIRED');
        }

        return DB::transaction(function () use ($campaignId, $reservationId, $partyId, $expectedRevision): ReservationRefund {
            $campaign = $this->campaigns->lockRetained($campaignId);
            $root = PrimaryReservationRecord::query()->where('business_campaign_id', $campaign['id'])->where('party_id', $partyId)
                ->whereKey($reservationId)->lockForUpdate()->first() ?? throw new CommandRejection('RESERVATION_NOT_FOUND', 404);
            [$reservation, $previous] = $this->retainedReservation($root, $campaign);
            if ($previous->revision !== $expectedRevision) {
                throw new CommandRejection('VERSION_CONFLICT', 409, $previous->revision);
            }
            if ($reservation->state !== 'confirmed') {
                throw new CommandRejection('RESERVATION_NOT_CONFIRMED', 409, $previous->revision);
            }
            if ($this->fundings->find($campaignId) !== null) {
                throw new CommandRejection('CAMPAIGN_FUNDED', 409, $previous->revision);
            }
            $commitment = PrimaryCommitment::query()->where('primary_reservation_id', $root->id)->lockForUpdate()->first();
            if ($commitment === null || $commitment->primary_reservation_version_id !== $previous->id
                || $commitment->operation_id !== $previous->operation_id || ! $commitment->confirmed_at->equalTo($previous->created_at)) {
                throw new RuntimeException('RESERVATION_INTEGRITY_FAILED');
            }
            $wallet = $this->wallets->lockForParty($partyId);
            $amount = WalletMoney::of($root->principal);
            $source = new PostingSource('primary_reservation', $root->id, $root->origin_operation_id);
            $posting = $this->wallets->refund($wallet, $amount, $source);
            $returned = $this->returnedCash->requireReturned($wallet, $amount, $source);
            if ($returned->returnKind !== 'primary_refund' || $returned->returnEntryId !== $posting->entryId || $returned->commitEntryId === null) {
                throw new RuntimeException('RESERVATION_INTEGRITY_FAILED');
            }

            return new ReservationRefund($root->id, $previous->revision, $commitment->id, $returned, $posting->replayed);
        });
    }

    public function lockFundingCandidate(string $campaignId): PrimaryFundingCandidate
    {
        if (DB::transactionLevel() === 0) {
            throw new CommandRejection('PRIMARY_TRANSACTION_REQUIRED');
        }

        return $this->lockCommittedCandidate($campaignId, checkConnections: true);
    }

    /** System expiry authenticates original cash without granting current funding admission. */
    private function lockCommittedCandidate(string $campaignId, bool $checkConnections): PrimaryFundingCandidate
    {
        return DB::transaction(function () use ($campaignId, $checkConnections): PrimaryFundingCandidate {
            $campaign = $this->campaigns->lockForFunding($campaignId);
            $roots = PrimaryReservationRecord::query()->where('business_campaign_id', $campaignId)->orderBy('id')->lockForUpdate()->get();
            $commitments = PrimaryCommitment::query()->whereIn('primary_reservation_id', $roots->modelKeys())
                ->orderBy('id')->lockForUpdate()->get()->keyBy('primary_reservation_id');
            $ranges = [];
            $principal = BigInteger::zero();
            $retained = [];
            foreach ($roots as $root) {
                [$reservation, $version] = $this->retainedReservation($root, $campaign);
                if ($reservation->state !== 'confirmed') {
                    throw new CommandRejection('CAMPAIGN_NOT_FULLY_COMMITTED');
                }
                $commitment = $commitments->get($root->id);
                if ($commitment === null || $commitment->primary_reservation_version_id !== $version->id
                    || $commitment->operation_id !== $version->operation_id || ! $commitment->confirmed_at->equalTo($version->created_at)) {
                    throw new RuntimeException('RESERVATION_INTEGRITY_FAILED');
                }
                $ranges = [...$ranges, ...$reservation->rights->ordinals->ranges];
                $principal = $principal->plus($reservation->rights->principal);
                $retained[$root->id] = ['commitment_id' => $commitment->id, 'reservation' => $reservation];
            }
            $ordinals = UnitOrdinals::fromRanges($campaign['units'], $ranges);
            if (! $ordinals->count->isEqualTo($campaign['units']) || ! $principal->isEqualTo($campaign['principal'])) {
                throw new CommandRejection('CAMPAIGN_NOT_FULLY_COMMITTED');
            }
            $partyIds = $roots->map(fn (PrimaryReservationRecord $root): string => $root->party_id)->unique()->sort()->values();
            if ($checkConnections) {
                $this->campaigns->rejectKnownConnections($campaign['business_id'], array_values($partyIds->all()));
            }
            $wallets = [];
            foreach ($partyIds as $partyId) {
                $wallets[$partyId] = $this->wallets->lockForParty($partyId);
            }
            $purchases = [];
            foreach ($roots as $root) {
                $source = new PostingSource('primary_reservation', $root->id, $root->origin_operation_id);
                $amount = WalletMoney::of($root->principal);
                try {
                    $cash = $this->cash->requireCommitted($wallets[$root->party_id], $amount, $source);
                } catch (WalletViolation $exception) {
                    if ($exception->reason !== 'PRIMARY_COMMITTED_CASH_REQUIRED') {
                        throw $exception;
                    }
                    try {
                        $returned = $this->returnedCash->requireReturned($wallets[$root->party_id], $amount, $source);
                    } catch (WalletViolation $returnedException) {
                        throw $returnedException->reason === 'PRIMARY_RETURNED_CASH_REQUIRED' ? $exception : $returnedException;
                    }
                    if ($returned->returnKind !== 'primary_refund' || $returned->commitEntryId === null) {
                        throw $exception;
                    }
                    throw new CommandRejection('CAMPAIGN_NOT_FULLY_COMMITTED');
                }
                $purchases[] = [...$retained[$root->id], 'reservation_id' => $root->id, 'party_id' => $root->party_id, 'cash' => $cash];
            }

            return new PrimaryFundingCandidate($campaign['id'], $campaign['publication_sha256'], $campaign['principal'], $purchases);
        });
    }

    public function settleExpiredCampaign(string $campaignId, string $closureId): array
    {
        if (DB::transactionLevel() === 0) {
            throw new CommandRejection('PRIMARY_TRANSACTION_REQUIRED');
        }
        if (DB::scalar("SELECT current_setting('transaction_isolation')") !== 'read committed') {
            throw new WalletViolation('PRIMARY_CASH_ISOLATION_REQUIRED');
        }

        return DB::transaction(function () use ($campaignId, $closureId): array {
            $campaign = $this->campaigns->lockForFunding($campaignId);
            if (now('UTC')->lt($campaign['expires_at'])) {
                throw new CommandRejection('CAMPAIGN_NOT_EXPIRED');
            }
            if ($this->fundings->find($campaignId) !== null) {
                throw new CommandRejection('CAMPAIGN_FUNDED');
            }
            try {
                $this->lockCommittedCandidate($campaignId, checkConnections: false);
                throw new CommandRejection('CAMPAIGN_SETTLEMENT_REQUIRED');
            } catch (CommandRejection $exception) {
                if ($exception->reason !== 'CAMPAIGN_NOT_FULLY_COMMITTED') {
                    throw $exception;
                }
            }
            $roots = PrimaryReservationRecord::query()->where('business_campaign_id', $campaignId)->orderBy('id')->lockForUpdate()->get();
            PrimaryCommitment::query()->whereIn('primary_reservation_id', $roots->modelKeys())->orderBy('id')->lockForUpdate()->get();
            $retained = [];
            foreach ($roots as $root) {
                $retained[$root->id] = $this->retainedReservation($root, $campaign);
            }
            foreach ($roots->map(fn (PrimaryReservationRecord $root): string => $root->party_id)->unique()->sort()->values() as $partyId) {
                $this->wallets->lockForParty($partyId);
            }
            DB::table('primary_campaign_expiry_settlements')->insert(['business_campaign_id' => $campaignId,
                'business_campaign_closure_id' => $closureId, 'created_at' => now('UTC')->format('Y-m-d H:i:s.uP')]);
            $changed = [];
            foreach ($roots as $root) {
                [$reservation, $version] = $retained[$root->id];
                if ($reservation->state === 'confirmed') {
                    $refund = $this->refund($campaignId, $root->id, $root->party_id, $version->revision);
                    if (! $refund->replayed) {
                        $changed[] = ['party_id' => $root->party_id, 'reservation_id' => $root->id];
                    }
                } elseif ($reservation->state === 'held') {
                    if ($this->expire($campaignId, $root->id) !== null) {
                        $changed[] = ['party_id' => $root->party_id, 'reservation_id' => $root->id];
                    }
                }
            }

            return $changed;
        });
    }

    public function lockReturnedCampaign(string $campaignId): PrimaryCampaignReturns
    {
        if (DB::transactionLevel() === 0) {
            throw new CommandRejection('PRIMARY_TRANSACTION_REQUIRED');
        }

        if (DB::scalar("SELECT current_setting('transaction_isolation')") !== 'read committed') {
            throw new WalletViolation('PRIMARY_CASH_ISOLATION_REQUIRED');
        }

        return DB::transaction(function () use ($campaignId): PrimaryCampaignReturns {
            $campaign = $this->campaigns->lockRetained($campaignId);
            if ($this->fundings->find($campaignId) !== null) {
                throw new CommandRejection('CAMPAIGN_FUNDED');
            }
            $roots = PrimaryReservationRecord::query()->where('business_campaign_id', $campaignId)->orderBy('id')->lockForUpdate()->get();
            $commitments = PrimaryCommitment::query()->whereIn('primary_reservation_id', $roots->modelKeys())
                ->orderBy('id')->lockForUpdate()->get()->keyBy('primary_reservation_id');
            $retained = [];
            foreach ($roots as $root) {
                [$reservation, $version] = $this->retainedReservation($root, $campaign);
                if ($reservation->state === 'held') {
                    throw new CommandRejection('CAMPAIGN_SETTLEMENT_REQUIRED');
                }
                $commitment = $commitments->get($root->id);
                if (($reservation->state === 'confirmed' && ($commitment === null || $commitment->primary_reservation_version_id !== $version->id
                    || $commitment->operation_id !== $version->operation_id || ! $commitment->confirmed_at->equalTo($version->created_at)))
                    || ($reservation->state !== 'confirmed' && $commitment !== null)) {
                    throw new RuntimeException('RESERVATION_INTEGRITY_FAILED');
                }
                $retained[$root->id] = ['reservation_id' => $root->id, 'version_id' => $version->id, 'version_sha256' => $version->sha256,
                    'commitment_id' => $commitment?->id, 'party_id' => $root->party_id, 'reservation' => $reservation];
            }
            $wallets = [];
            foreach ($roots->map(fn (PrimaryReservationRecord $root): string => $root->party_id)->unique()->sort()->values() as $partyId) {
                $wallets[$partyId] = $this->wallets->lockForParty($partyId);
            }
            $released = BigInteger::zero();
            $refunded = BigInteger::zero();
            $investors = [];
            $returns = [];
            foreach ($roots as $root) {
                $purchase = $retained[$root->id];
                $cash = $this->returnedCash->requireReturned($wallets[$root->party_id], WalletMoney::of($root->principal),
                    new PostingSource('primary_reservation', $root->id, $root->origin_operation_id));
                $confirmed = $purchase['reservation']->state === 'confirmed';
                if ($cash->returnKind !== ($confirmed ? 'primary_refund' : 'primary_release') || ($cash->commitEntryId !== null) !== $confirmed) {
                    throw new RuntimeException('RESERVATION_INTEGRITY_FAILED');
                }
                if ($confirmed) {
                    $refunded = $refunded->plus($root->principal);
                    $investors[$root->party_id] = true;
                } else {
                    $released = $released->plus($root->principal);
                }
                $returns[] = [...$purchase, 'cash' => $cash];
            }

            return new PrimaryCampaignReturns($campaign['id'], $campaign['publication_sha256'], (string) $released, (string) $refunded,
                count($investors), $returns);
        });
    }

    public function expireDue(int $limit): int
    {
        if ($limit < 1 || $limit > 1000) {
            throw new CommandRejection('INVALID_SWEEP_LIMIT');
        }

        $result = DB::transactionLevel() > 0
            ? DB::transaction(fn (): array => $this->sweepExpired($limit, true), 3)
            : $this->sweepExpired($limit, false);
        if ($result['failure'] !== null) {
            throw $result['failure'];
        }

        return $result['expired'];
    }

    /** @return array{expired: int, failure: Throwable|null} */
    private function sweepExpired(int $limit, bool $deferChanges): array
    {
        $cutoff = now('UTC')->format('Y-m-d H:i:s.uP');
        $candidates = PrimaryReservationRecord::query()->select('primary_reservations.*')
            ->leftJoin('primary_expiry_failures as failures', 'failures.primary_reservation_id', 'primary_reservations.id')
            ->where('expires_at', '<=', $cutoff)
            ->whereNotExists(function (Builder $query): void {
                $query->selectRaw('1')->from('primary_reservation_versions')
                    ->whereColumn('primary_reservation_id', 'primary_reservations.id')
                    ->whereIn('state', ['confirmed', 'released', 'expired']);
            })->orderByRaw('failures.last_attempted_at ASC NULLS FIRST')
            ->orderBy('expires_at')->orderBy('primary_reservations.id')->limit($limit)->get();
        $expired = 0;
        $failure = null;
        $subjects = [];
        foreach ($candidates as $candidate) {
            try {
                $subject = DB::transaction(function () use ($candidate, $deferChanges): ?array {
                    $released = $this->expire($candidate->business_campaign_id, $candidate->id);
                    if ($released === null) {
                        return null;
                    }
                    $campaign = $this->campaigns->lockRetained($candidate->business_campaign_id);
                    $subject = ['party_id' => $candidate->party_id, 'reservation_id' => $candidate->id,
                        'business_id' => $campaign['business_id'], 'campaign_id' => $candidate->business_campaign_id];
                    if (! $deferChanges) {
                        $this->changedExpiries([$subject]);
                    }

                    return $subject;
                }, 3);
                if ($subject !== null) {
                    if ($deferChanges) {
                        $subjects[] = $subject;
                    }

                    $expired++;
                }
            } catch (Throwable $exception) {
                $reasonCode = $this->expiryFailureReason($exception);
                DB::table('primary_expiry_failures')->upsert([['primary_reservation_id' => $candidate->id,
                    'last_attempted_at' => now('UTC')->format('Y-m-d H:i:s.uP'), 'exception_class' => $exception::class, 'reason_code' => $reasonCode]],
                    ['primary_reservation_id'], ['last_attempted_at', 'exception_class', 'reason_code']);
                Log::error('Primary reservation expiry failed.', ['reservation_id' => $candidate->id,
                    'campaign_id' => $candidate->business_campaign_id, 'exception_class' => $exception::class, 'reason_code' => $reasonCode]);
                $failure ??= $exception;
            }
        }
        $this->changedExpiries($subjects);

        return ['expired' => $expired, 'failure' => $failure];
    }

    /** @param list<array{party_id: string, reservation_id: string, business_id: string, campaign_id: string}> $subjects */
    private function changedExpiries(array $subjects): void
    {
        usort($subjects, fn (array $left, array $right): int => [$left['party_id'], $left['reservation_id']] <=> [$right['party_id'], $right['reservation_id']]);
        $campaigns = [];
        foreach ($subjects as $subject) {
            $this->changes->record(ChangeScope::party($subject['party_id']), 'purchase', $subject['reservation_id']);
            $campaigns[$subject['business_id'].'|'.$subject['campaign_id']] = $subject;
        }
        ksort($campaigns, SORT_STRING);
        foreach ($campaigns as $subject) {
            $this->changes->record(ChangeScope::business($subject['business_id']), 'campaign', $subject['campaign_id']);
        }
    }

    private function expiryFailureReason(Throwable $exception): string
    {
        $reason = $exception instanceof WalletViolation || $exception instanceof CommandRejection
            ? $exception->reason : $exception->getMessage();

        return in_array($reason, ['RESERVATION_INTEGRITY_FAILED', 'CAMPAIGN_INTEGRITY_FAILED',
            'PRIMARY_CASH_ISOLATION_REQUIRED', 'PRIMARY_RETURNED_CASH_REQUIRED',
            'WALLET_POSTING_TRANSACTION_REQUIRED', 'WALLET_POSTING_SOURCE_INVALID', 'WALLET_POSTING_WALLET_INVALID',
            'WALLET_POSTING_CONFLICT', 'WALLET_POSTING_STATE_INVALID', 'WALLET_BUCKET_NEGATIVE',
            'RESERVATION_NOT_FOUND', 'PRIMARY_TRANSACTION_REQUIRED'], true) ? $reason : 'UNCLASSIFIED_EXPIRY_FAILURE';
    }

    public function expire(string $campaignId, string $reservationId, ?string $operationId = null): ?ReservationRelease
    {
        if (DB::transactionLevel() === 0) {
            throw new CommandRejection('PRIMARY_TRANSACTION_REQUIRED');
        }

        return DB::transaction(function () use ($campaignId, $reservationId, $operationId): ?ReservationRelease {
            $campaign = $this->campaigns->lockRetained($campaignId);
            $root = PrimaryReservationRecord::query()->where('business_campaign_id', $campaign['id'])->whereKey($reservationId)->lockForUpdate()->first()
                ?? throw new CommandRejection('RESERVATION_NOT_FOUND', 404);
            [$reservation, $previous] = $this->retainedReservation($root, $campaign);
            $at = now('UTC')->toDateTimeImmutable();
            if ($reservation->state !== 'held' || ! $reservation->window->isExpired($at)) {
                return null;
            }

            return $this->releaseCash($root, $reservation->release($at), $previous, $operationId, $at);
        });
    }

    private function releaseCash(PrimaryReservationRecord $root, PrimaryReservation $released, PrimaryReservationVersion $previous,
        ?string $operationId, DateTimeImmutable $at): ReservationRelease
    {
        $version = $previous->state === 'held'
            ? $this->appendVersion($root, $released, $operationId, $previous, $at) : $previous;
        $wallet = $this->wallets->lockForParty($root->party_id);
        $amount = WalletMoney::of($root->principal);
        $source = new PostingSource('primary_reservation', $root->id, $root->origin_operation_id);
        $posting = $this->wallets->release($wallet, $amount, $source);
        if ($previous->state === 'held' && $posting->replayed) {
            throw new RuntimeException('RESERVATION_INTEGRITY_FAILED');
        }

        $returned = $this->returnedCash->requireReturned($wallet, $amount, $source);
        if ($returned->returnKind !== 'primary_release' || $returned->returnEntryId !== $posting->entryId) {
            throw new RuntimeException('RESERVATION_INTEGRITY_FAILED');
        }

        return new ReservationRelease($root->id, $version->revision, $released, $posting);
    }

    /**
     * Replays immutable evidence, verifying every projection, digest and domain transition.
     *
     * @param  CampaignInput  $campaign
     * @return array{PrimaryReservation, PrimaryReservationVersion}
     */
    private function retainedReservation(PrimaryReservationRecord $root, array $campaign): array
    {
        $rights = $this->retainedRights($root, $campaign);
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

    private function appendVersion(PrimaryReservationRecord $root, PrimaryReservation $reservation, ?string $operationId,
        ?PrimaryReservationVersion $previous, DateTimeImmutable $at): PrimaryReservationVersion
    {
        $payload = $this->versionPayload($root, $reservation, $operationId, $previous, $at);
        $version = new PrimaryReservationVersion;
        $version->forceFill(['primary_reservation_id' => $root->id, 'revision' => $payload['revision'], 'state' => $reservation->state,
            'operation_id' => $operationId, 'previous_sha256' => $previous?->sha256, 'payload' => $payload, 'sha256' => $this->digest($payload), 'created_at' => $at])->save();

        return $version;
    }

    /** @return array<string, mixed> */
    private function versionPayload(PrimaryReservationRecord $root, PrimaryReservation $reservation, ?string $operationId,
        ?PrimaryReservationVersion $previous, DateTimeImmutable $at): array
    {
        return ['contract' => 'primary-reservation-version-1', 'reservation_id' => $root->id, 'reservation_sha256' => $root->sha256,
            'revision' => ($previous === null ? 0 : $previous->revision) + 1, 'state' => $reservation->state, 'operation_id' => $operationId, 'previous_sha256' => $previous?->sha256,
            'terms' => $reservation->terms->toArray(), 'disclosure_sha256' => $reservation->terms->disclosureSha256, 'recorded_at' => $this->instant($at)];
    }

    /** @param CampaignInput $campaign */
    private function retainedRights(PrimaryReservationRecord $record, array $campaign): UnitRights
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
