<?php

declare(strict_types=1);

namespace App\Infrastructure\Secondary;

use App\Application\Identity\AuthorizeActiveRole;
use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Operations\Contracts\OperationJournal;
use App\Application\Secondary\Contracts\NoteUnitHolders;
use App\Application\Secondary\Contracts\SecondaryMarket;
use App\Application\Secondary\Contracts\SecondaryNotes;
use App\Application\Wallet\Contracts\WalletPostings;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Domain\Secondary\HoldingLot;
use App\Domain\Secondary\PriceCap;
use App\Domain\Secondary\SecondaryListing;
use App\Domain\Secondary\SecondaryTerms;
use App\Domain\Secondary\SecondaryViolation;
use App\Domain\Secondary\TradingWindow;
use App\Models\PrimaryHolding;
use App\Models\SecondaryHalt;
use App\Models\SecondaryHaltLift;
use App\Models\SecondaryListingRecord;
use App\Models\SecondaryReservationRecord;
use App\Models\SecondaryTransfer;
use Brick\Math\BigInteger;
use Closure;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The secondary market on the shared ledger (BRS v3.0 §12). Each command runs under the caller's
 * current authority through the command journal, and the Domain decides every rule (`SecondaryListing`,
 * `TradingWindow`, `PriceCap`, `HoldingLot`, `SettlementQuote`) from facts read under its locks.
 *
 * Lock order: every command takes the market lock shared, then its note's lock, then the listing row,
 * then wallets in Party id order. A market halt takes the market lock exclusively, so it waits for
 * every command in flight and nothing starts until it has cancelled every listing. A reservation
 * that has lapsed is released (its held cash returned) by the next command on its listing or by the
 * `secondary:expire` sweep.
 *
 * Bound only while `app.secondary_trading` is on; otherwise `UnavailableSecondaryMarket` refuses all.
 *
 * @phpstan-import-type MarketResult from SecondaryMarket
 * @phpstan-import-type Note from SecondaryNotes
 */
final readonly class EloquentSecondaryMarket implements SecondaryMarket
{
    /** Refusals about what the caller asked for, rather than the market's current state. */
    private const array INPUT_REFUSALS = ['INVALID_UNITS', 'INVALID_PRICE', 'PRICE_ABOVE_CAP', 'UNITS_NOT_HELD', 'UNITS_NOT_LISTED', 'HOLDING_LIMIT_EXCEEDED',
        'OWN_LISTING', 'REASON_REQUIRED'];

    public function __construct(private AuthorizeActiveRole $authority, private AuthorizeStaffPermission $staff, private OperationJournal $journal,
        private SecondaryNotes $notes, private NoteUnitHolders $holders, private WalletPostings $wallets, private SecondaryPostings $postings) {}

    public function list(int $userId, int $contextRevision, string $holdingId, string $units, string $price, string $requestId): array
    {
        return $this->asInvestor($userId, $contextRevision, 'secondary.list', $requestId, 'secondary_lot', strtolower($holdingId),
            ['units' => $units, 'price' => $price], fn (string $partyId, string $operationId): OperationResult => $this->openListing($partyId, strtolower($holdingId), $units, $price, $operationId));
    }

    public function reserve(int $userId, int $contextRevision, string $listingId, string $units, string $requestId): array
    {
        return $this->asInvestor($userId, $contextRevision, 'secondary.reserve', $requestId, 'secondary_listing', strtolower($listingId), ['units' => $units],
            fn (string $partyId, string $operationId): OperationResult => $this->reserveUnits($partyId, strtolower($listingId), $units, $operationId));
    }

    public function settle(int $userId, int $contextRevision, string $listingId, string $reservationId, string $requestId): array
    {
        return $this->asInvestor($userId, $contextRevision, 'secondary.settle', $requestId, 'secondary_listing', strtolower($listingId),
            ['reservation_id' => strtolower($reservationId)],
            fn (string $partyId): OperationResult => $this->settleReservation($partyId, strtolower($listingId), strtolower($reservationId)));
    }

    public function amend(int $userId, int $contextRevision, string $listingId, ?string $price, string $requestId): array
    {
        return $this->asInvestor($userId, $contextRevision, 'secondary.amend', $requestId, 'secondary_listing', strtolower($listingId), ['price' => $price],
            fn (string $partyId): OperationResult => $this->amendListing($partyId, strtolower($listingId), $price));
    }

    public function halt(int $staffUserId, ?string $noteId, string $reason, string $requestId): array
    {
        return $this->asStaff($staffUserId, 'secondary.halt', 'secondary.halt', $requestId, $noteId === null ? 'secondary_market' : 'secondary_note',
            $noteId ?? 'market', ['reason' => $reason], fn (string $operationId): OperationResult => $this->haltMarket($staffUserId, $noteId, $reason, $operationId));
    }

    public function lift(int $staffUserId, ?string $noteId, string $reason, string $requestId): array
    {
        return $this->asStaff($staffUserId, 'secondary.halt', 'secondary.lift', $requestId, $noteId === null ? 'secondary_market' : 'secondary_note',
            $noteId ?? 'market', ['reason' => $reason], fn (string $operationId): OperationResult => $this->liftHalt($staffUserId, $noteId, $reason, $operationId));
    }

    public function cancelListing(int $staffUserId, string $listingId, string $reason, string $requestId): array
    {
        return $this->asStaff($staffUserId, 'secondary.listings.cancel', 'secondary.cancel', $requestId, 'secondary_listing', strtolower($listingId),
            ['reason' => $reason], fn (): OperationResult => $this->cancelByStaff(strtolower($listingId), $reason));
    }

    /**
     * Ends what time has ended: releases every lapsed reservation's held cash and records every listing
     * past its seven days as expired. Each listing is settled in its own transaction under its locks.
     *
     * @return array{released: int, expired: int}
     */
    public function sweep(DateTimeImmutable $at): array
    {
        $released = 0;
        $expired = 0;
        $candidates = SecondaryListingRecord::query()->where('state', 'open')
            ->where(fn ($query) => $query->where('expires_at', '<=', $at)
                ->orWhereIn('id', SecondaryReservationRecord::query()->whereNull('ended')->where('expires_at', '<=', $at)->select('listing_id')))
            ->orderBy('id')->get(['id', 'business_campaign_id']);
        foreach ($candidates as $candidate) {
            [$wasReleased, $wasExpired] = DB::transaction(function () use ($candidate, $at): array {
                $this->lockNote($candidate->business_campaign_id);
                $outcome = [false, false];
                // A command may have closed the listing since it was selected; only a listing still open is swept.
                foreach (SecondaryListingRecord::query()->whereKey($candidate->id)->where('state', 'open')->lockForUpdate()->get() as $row) {
                    $open = SecondaryReservationRecord::query()->where('listing_id', $row->id)->whereNull('ended')->first();
                    $this->releaseLapsed($row, $at);
                    $current = $this->restore($row, null)->at($at);
                    if ($current->state === 'expired') {
                        $this->persist($row, $current, $at);
                    }
                    $outcome = [$open !== null && $open->expires_at <= $at, $current->state === 'expired'];
                }

                return $outcome;
            }, 3);
            $released += (int) $wasReleased;
            $expired += (int) $wasExpired;
        }

        return ['released' => $released, 'expired' => $expired];
    }

    private function openListing(string $partyId, string $lotId, string $units, string $price, string $operationId): OperationResult
    {
        $now = self::now();
        $lotType = PrimaryHolding::query()->whereKey($lotId)->exists() ? 'primary_holding' : 'secondary_transfer';
        $noteId = $lotType === 'primary_holding' ? PrimaryHolding::query()->whereKey($lotId)->value('business_campaign_id')
            : SecondaryTransfer::query()->whereKey($lotId)->value('business_campaign_id');
        if (! is_string($noteId)) {
            throw new CommandRejection('LOT_NOT_FOUND', 404);
        }
        $this->lockNote($noteId);
        $lot = $this->holders->lot($lotType, $lotId);
        if ($lot === null || $lot['party_id'] !== $partyId) {
            throw new CommandRejection('LOT_NOT_FOUND', 404);
        }
        $note = $this->note($noteId);
        $listed = SecondaryListingRecord::query()->where('lot_type', $lotType)->where('lot_id', $lotId)->where('state', 'open')->get()
            ->sum(fn (SecondaryListingRecord $row): int => $this->restore($row, null)->at($now)->state === 'open' ? $row->remaining_units : 0);
        $free = (string) max(0, (int) $lot['units'] - $listed);
        $cap = $this->cap($note);
        $window = $this->window($note);
        $listing = $this->decide(fn (): SecondaryListing => SecondaryListing::open(strtolower((string) Str::ulid()), $partyId, $lotId, $units, $free, $price,
            $cap, $window, $now), ['PRICE_ABOVE_CAP' => ['cap' => self::money((string) $cap)], 'UNITS_NOT_HELD' => ['free_units' => $free],
                'TRADING_PAUSED' => $this->paused($window, $now)]);
        $row = new SecondaryListingRecord;
        $row->forceFill(['id' => $listing->id, 'seller_party_id' => $partyId, 'business_campaign_id' => $noteId, 'lot_type' => $lotType, 'lot_id' => $lotId,
            'listed_units' => (int) $listing->listedUnits, 'remaining_units' => (int) $listing->remainingUnits, 'price' => $listing->price,
            'listed_at' => $listing->listedAt, 'expires_at' => $listing->expiresAt, 'state' => 'open', 'terms_version' => SecondaryTerms::VERSION,
            'operation_id' => $operationId, 'updated_at' => $now])->save();

        return new OperationResult('SECONDARY_LISTED', ['listing' => $this->present($row, $now), 'cap' => self::money((string) $cap)], 1);
    }

    private function reserveUnits(string $partyId, string $listingId, string $units, string $operationId): OperationResult
    {
        $now = self::now();
        $row = $this->openRow($listingId);
        $note = $this->note($row->business_campaign_id);
        $window = $this->window($note);
        $open = $this->releaseLapsed($row, $now);
        $held = $this->heldUnits($partyId, $row->business_campaign_id, $now);
        $reservationId = strtolower((string) Str::ulid());
        $listing = $this->decide(fn (): SecondaryListing => $this->restore($row, $open)->reserve($reservationId, $partyId, $units, $note['raise_units'], $held,
            $window, $now), ['HOLDING_LIMIT_EXCEEDED' => ['maximum_units' => (string) TradingWindow::buyerRoom($note['raise_units'], $held)],
                'UNITS_NOT_LISTED' => ['listed_units' => (string) $row->remaining_units], 'TRADING_PAUSED' => $this->paused($window, $now),
                'LISTING_RESERVED' => ['reserved_until' => $open?->expires_at->utc()->format(DateTimeInterface::ATOM)]]);
        $reservation = $listing->reservation ?? throw new SecondaryViolation('RESERVATION_NOT_OPEN');
        $gross = BigInteger::of($reservation['units'])->multipliedBy($row->price);
        $record = new SecondaryReservationRecord;
        $record->forceFill(['id' => $reservationId, 'listing_id' => $row->id, 'buyer_party_id' => $partyId, 'business_campaign_id' => $row->business_campaign_id,
            'units' => (int) $reservation['units'], 'price' => $row->price, 'gross' => (string) $gross, 'reserved_at' => $now,
            'expires_at' => $reservation['expires_at'], 'operation_id' => $operationId])->save();
        $this->postings->hold($this->wallets->lockForParty($partyId), $gross, $reservationId, $operationId);
        $row->forceFill(['updated_at' => $now])->save();

        return new OperationResult('SECONDARY_RESERVED', ['reservation' => $this->presentReservation($record), 'listing' => $this->present($row, $now)], 1);
    }

    private function settleReservation(string $partyId, string $listingId, string $reservationId): OperationResult
    {
        $now = self::now();
        $row = $this->openRow($listingId);
        $reservation = SecondaryReservationRecord::query()->whereKey($reservationId)->where('listing_id', $row->id)->whereNull('ended')->first();
        if ($reservation === null || $reservation->buyer_party_id !== $partyId) {
            throw new CommandRejection('RESERVATION_NOT_OPEN');
        }
        $note = $this->note($row->business_campaign_id);
        $window = $this->window($note);
        ['listing' => $after, 'quote' => $quote] = $this->decide(fn (): array => $this->restore($row, $reservation)->settle($reservationId, $window, $now),
            ['TRADING_PAUSED' => $this->paused($window, $now)]);
        $lot = $this->holders->lot($row->lot_type, $row->lot_id);
        $moved = $this->decide(fn (): HoldingLot => HoldingLot::of($lot['ordinals'] ?? [])->split((string) $quote->units)['moved']);
        $wallets = [];
        foreach (collect([$row->seller_party_id, $partyId])->sort()->values() as $holder) {
            $wallets[$holder] = $this->wallets->lockForParty($holder);
        }
        $this->postings->purchase($wallets[$partyId], $quote->buyerPays, $reservationId, $reservation->operation_id);
        $transfer = new SecondaryTransfer;
        $transfer->forceFill(['id' => strtolower((string) Str::ulid()), 'reservation_id' => $reservationId, 'listing_id' => $row->id,
            'business_campaign_id' => $row->business_campaign_id, 'seller_party_id' => $row->seller_party_id, 'buyer_party_id' => $partyId,
            'source_lot_type' => $row->lot_type, 'source_lot_id' => $row->lot_id, 'units' => $quote->units->toInt(), 'ordinals' => $moved->ranges,
            'price' => (string) $quote->price, 'gross' => (string) $quote->gross, 'seller_fee' => (string) $quote->sellerFee,
            'seller_receives' => (string) $quote->sellerReceives, 'terms_version' => SecondaryTerms::VERSION, 'operation_id' => $reservation->operation_id,
            'settled_at' => $now])->save();
        $this->postings->sale($wallets[$row->seller_party_id], $quote->gross, $quote->sellerReceives, $quote->sellerFee, $reservationId, $reservation->operation_id);
        $reservation->forceFill(['ended' => 'settled', 'end_reason' => 'settled', 'ended_at' => $now])->save();
        $this->persist($row, $after, $now);

        return new OperationResult('SECONDARY_SETTLED', ['transfer' => ['lot_id' => $transfer->id, 'note_id' => $transfer->business_campaign_id,
            'ordinals' => $moved->ranges, ...$quote->toArray(), 'settled_at' => $now->format(DateTimeInterface::ATOM)],
            'listing' => $this->present($row, $now)], 1);
    }

    private function amendListing(string $partyId, string $listingId, ?string $price): OperationResult
    {
        $now = self::now();
        $row = $this->openRow($listingId);
        if ($row->seller_party_id !== $partyId) {
            throw new CommandRejection('LISTING_NOT_FOUND', 404);
        }
        $open = $this->releaseLapsed($row, $now);
        $reserved = ['LISTING_RESERVED' => ['reserved_until' => $open?->expires_at->utc()->format(DateTimeInterface::ATOM)]];
        if ($price === null) {
            $this->persist($row, $this->decide(fn (): SecondaryListing => $this->restore($row, $open)->cancel($now), $reserved), $now);

            return new OperationResult('SECONDARY_LISTING_CANCELLED', ['listing' => $this->present($row, $now)], 1);
        }
        $note = $this->note($row->business_campaign_id);
        $cap = $this->cap($note);
        $window = $this->window($note);
        $this->persist($row, $this->decide(fn (): SecondaryListing => $this->restore($row, $open)->reprice($price, $cap, $window, $now),
            [...$reserved, 'PRICE_ABOVE_CAP' => ['cap' => self::money((string) $cap)], 'TRADING_PAUSED' => $this->paused($window, $now)]), $now);

        return new OperationResult('SECONDARY_REPRICED', ['listing' => $this->present($row, $now), 'cap' => self::money((string) $cap)], 1);
    }

    private function cancelByStaff(string $listingId, string $reason): OperationResult
    {
        $now = self::now();
        $row = $this->openRow($listingId);
        $open = $this->releaseLapsed($row, $now);
        $this->persist($row, $this->decide(fn (): SecondaryListing => $this->restore($row, $open)->cancelByStaff($reason, $now),
            ['LISTING_RESERVED' => ['reserved_until' => $open?->expires_at->utc()->format(DateTimeInterface::ATOM)]]), $now);

        return new OperationResult('SECONDARY_LISTING_CANCELLED', ['listing' => $this->present($row, $now)], 1);
    }

    private function haltMarket(int $staffUserId, ?string $noteId, string $reason, string $operationId): OperationResult
    {
        $now = self::now();
        if (trim($reason) === '') {
            throw new CommandRejection('REASON_REQUIRED', 422);
        }
        if ($noteId === null) {
            DB::select('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))', ['secondary-market']);
        } else {
            if ($this->notes->find($noteId) === null) {
                throw new CommandRejection('NOTE_NOT_FOUND', 404);
            }
            $this->lockNote($noteId);
        }
        if ($this->activeHalt($noteId) !== null) {
            throw new CommandRejection($noteId === null ? 'MARKET_ALREADY_HALTED' : 'NOTE_ALREADY_HALTED');
        }
        $halt = new SecondaryHalt;
        $halt->forceFill(['id' => strtolower((string) Str::ulid()), 'business_campaign_id' => $noteId, 'reason' => $reason, 'staff_user_id' => $staffUserId,
            'operation_id' => $operationId, 'halted_at' => $now])->save();
        $cancelled = 0;
        $released = 0;
        $listings = SecondaryListingRecord::query()->where('state', 'open')->when($noteId !== null, fn ($query) => $query->where('business_campaign_id', $noteId))
            ->orderBy('id')->pluck('id');
        foreach ($listings as $listingId) {
            $row = $this->lockListing($listingId);
            $open = SecondaryReservationRecord::query()->where('listing_id', $row->id)->whereNull('ended')->first();
            if ($open !== null) {
                $this->release($open, $open->expires_at <= $now ? 'lapsed' : 'halted', $now);
                $released++;
            }
            $current = $this->restore($row, null)->at($now);
            if ($current->state === 'open') {
                $current = $current->halt($reason, $now);
                $cancelled++;
            }
            $this->persist($row, $current, $now);
        }

        return new OperationResult('SECONDARY_HALTED', ['halt_id' => $halt->id, 'note_id' => $noteId, 'reason' => $reason,
            'halted_at' => $now->format(DateTimeInterface::ATOM), 'cancelled_listings' => $cancelled, 'released_reservations' => $released], 1);
    }

    private function liftHalt(int $staffUserId, ?string $noteId, string $reason, string $operationId): OperationResult
    {
        $now = self::now();
        if (trim($reason) === '') {
            throw new CommandRejection('REASON_REQUIRED', 422);
        }
        DB::select('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))', ['secondary-market']);
        $halt = $this->activeHalt($noteId) ?? throw new CommandRejection($noteId === null ? 'MARKET_NOT_HALTED' : 'NOTE_NOT_HALTED');
        (new SecondaryHaltLift)->forceFill(['halt_id' => $halt->id, 'reason' => $reason, 'staff_user_id' => $staffUserId, 'operation_id' => $operationId,
            'lifted_at' => $now])->save();

        return new OperationResult('SECONDARY_HALT_LIFTED', ['halt_id' => $halt->id, 'note_id' => $noteId, 'reason' => $reason,
            'lifted_at' => $now->format(DateTimeInterface::ATOM)], 1);
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  Closure(string, string): OperationResult  $operation
     * @return MarketResult
     */
    private function asInvestor(int $userId, int $contextRevision, string $command, string $requestId, string $targetType, string $targetId, array $input,
        Closure $operation): array
    {
        return DB::transaction(fn (): array => $this->authority->handle($userId, 'investor', null, $contextRevision,
            function (array $identity) use ($userId, $contextRevision, $command, $requestId, $targetType, $targetId, $input, $operation): array {
                $partyId = strtolower((string) $identity['party']['id']);

                return self::result($this->journal->execute('party:'.$partyId, $userId, $command, $requestId, $targetType, $targetId,
                    ['identity_context_revision' => $contextRevision, ...$input], function (): void {},
                    fn (string $operationId): OperationResult => $operation($partyId, $operationId)), $requestId);
            }), 3);
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  Closure(string): OperationResult  $operation
     * @return MarketResult
     */
    private function asStaff(int $staffUserId, string $permission, string $command, string $requestId, string $targetType, string $targetId, array $input,
        Closure $operation): array
    {
        return DB::transaction(fn (): array => $this->staff->handle($staffUserId, $permission, fn (): array => self::result(
            $this->journal->execute('staff:'.$staffUserId, $staffUserId, $command, $requestId, $targetType, $targetId, $input, function (): void {}, $operation),
            $requestId)), 3);
    }

    /**
     * @param  array<string, mixed>  $recorded
     * @return MarketResult
     */
    private static function result(array $recorded, string $requestId): array
    {
        /** @var MarketResult $result */
        $result = ['status' => $recorded['status'], 'code' => $recorded['code'], 'operation_id' => $recorded['operation_id'],
            'request_id' => strtolower($requestId), 'revision' => $recorded['revision'], 'data' => $recorded['data'], 'http_status' => $recorded['http_status']];

        return $result;
    }

    /**
     * Runs a Domain transition and turns its refusal into the command's, with the facts the caller
     * needs to act on it (the cap, the most units allowed, when a reservation ends).
     *
     * @template TResult
     *
     * @param  Closure(): TResult  $transition
     * @param  array<string, array<string, mixed>>  $facts
     * @return TResult
     */
    private function decide(Closure $transition, array $facts = []): mixed
    {
        try {
            return $transition();
        } catch (SecondaryViolation $violation) {
            throw new CommandRejection($violation->reasonCode, in_array($violation->reasonCode, self::INPUT_REFUSALS, true) ? 422 : 409,
                data: $facts[$violation->reasonCode] ?? []);
        }
    }

    private function lockNote(string $noteId): void
    {
        DB::select('SELECT pg_advisory_xact_lock_shared(hashtextextended(?, 0))', ['secondary-market']);
        DB::select('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))', ['secondary-note:'.$noteId]);
    }

    private function lockListing(string $listingId): SecondaryListingRecord
    {
        return SecondaryListingRecord::query()->whereKey($listingId)->lockForUpdate()->sole();
    }

    /** The listing under its note's lock and its own; a missing listing is not found. */
    private function openRow(string $listingId): SecondaryListingRecord
    {
        $noteId = SecondaryListingRecord::query()->whereKey($listingId)->value('business_campaign_id');
        if (! is_string($noteId)) {
            throw new CommandRejection('LISTING_NOT_FOUND', 404);
        }
        $this->lockNote($noteId);

        return $this->lockListing($listingId);
    }

    /** @return Note */
    private function note(string $noteId): array
    {
        return $this->notes->find($noteId) ?? throw new CommandRejection('NOTE_NOT_FOUND', 404);
    }

    /** @param Note $note */
    private function cap(array $note): BigInteger
    {
        $remaining = array_values(array_filter($note['instalments'],
            fn (array $instalment): bool => $note['posted_through'] === null || substr($instalment['due_on'], 0, 10) > $note['posted_through']));

        return PriceCap::perUnit(array_map(fn (array $instalment): array => ['principal' => $instalment['principal'], 'return' => $instalment['return']], $remaining),
            $note['raise_units']);
    }

    /** @param Note $note */
    private function window(array $note): TradingWindow
    {
        return new TradingWindow($note['health'], $this->activeHalt(null) !== null, $this->activeHalt($note['note_id']) !== null,
            array_map(fn (array $instalment): string => substr($instalment['due_on'], 0, 10), $note['instalments']), $note['posted_through']);
    }

    /** @return array{paused_since: string|null, reopens: string} */
    private function paused(TradingWindow $window, DateTimeImmutable $at): array
    {
        return ['paused_since' => $window->pausedSince($at)?->setTimezone(new DateTimeZone('UTC'))->format(DateTimeInterface::ATOM), 'reopens' => 'when_payouts_post'];
    }

    private function activeHalt(?string $noteId): ?SecondaryHalt
    {
        return SecondaryHalt::query()->when($noteId === null, fn ($query) => $query->whereNull('business_campaign_id'),
            fn ($query) => $query->where('business_campaign_id', $noteId))->whereDoesntHave('lift')->first();
    }

    /** Units the buyer holds in the note now, plus those they hold reserved, primary and secondary together (INV-R01). */
    private function heldUnits(string $partyId, string $noteId, DateTimeImmutable $at): string
    {
        $held = array_sum(array_map(fn (array $lot): int => $lot['party_id'] === $partyId ? (int) $lot['units'] : 0, $this->holders->lots($noteId)));
        $reserved = (int) SecondaryReservationRecord::query()->where('buyer_party_id', $partyId)->where('business_campaign_id', $noteId)->whereNull('ended')
            ->where('expires_at', '>', $at)->sum('units');

        return (string) ($held + $reserved);
    }

    /** Releases the listing's reservation if it has lapsed, and returns the reservation still open. */
    private function releaseLapsed(SecondaryListingRecord $row, DateTimeImmutable $at): ?SecondaryReservationRecord
    {
        $open = SecondaryReservationRecord::query()->where('listing_id', $row->id)->whereNull('ended')->first();
        if ($open !== null && $open->expires_at <= $at) {
            $this->release($open, 'lapsed', $at);

            return null;
        }

        return $open;
    }

    /** @param 'lapsed'|'halted' $why */
    private function release(SecondaryReservationRecord $reservation, string $why, DateTimeImmutable $at): void
    {
        $this->postings->release($this->wallets->lockForParty($reservation->buyer_party_id), BigInteger::of($reservation->gross), $reservation->id,
            $reservation->operation_id);
        $reservation->forceFill(['ended' => 'released', 'end_reason' => $why, 'ended_at' => $at])->save();
    }

    private function restore(SecondaryListingRecord $row, ?SecondaryReservationRecord $open): SecondaryListing
    {
        /** @var 'open'|'sold'|'cancelled'|'expired' $state */
        $state = $row->state;
        /** @var array{by: 'seller'|'staff'|'halt', reason: string|null}|null $cancellation */
        $cancellation = $row->cancelled_by === null ? null : ['by' => $row->cancelled_by, 'reason' => $row->cancel_reason];

        return SecondaryListing::restore($row->id, $row->seller_party_id, $row->lot_id, (string) $row->listed_units, (string) $row->remaining_units, $row->price,
            $row->listed_at->toDateTimeImmutable(), $row->expires_at->toDateTimeImmutable(), $state,
            $open === null ? null : ['id' => $open->id, 'buyer_party_id' => $open->buyer_party_id, 'units' => (string) $open->units,
                'expires_at' => $open->expires_at->toDateTimeImmutable()], $cancellation);
    }

    private function persist(SecondaryListingRecord $row, SecondaryListing $listing, DateTimeImmutable $at): void
    {
        $row->forceFill(['remaining_units' => (int) $listing->remainingUnits, 'price' => $listing->price, 'state' => $listing->state,
            'cancelled_by' => $listing->cancellation['by'] ?? null, 'cancel_reason' => $listing->cancellation['reason'] ?? null, 'updated_at' => $at])->save();
    }

    /** @return array<string, mixed> */
    private function present(SecondaryListingRecord $row, DateTimeImmutable $at): array
    {
        $open = $row->state === 'open' ? SecondaryReservationRecord::query()->where('listing_id', $row->id)->whereNull('ended')->where('expires_at', '>', $at)->first() : null;

        return ['listing_id' => $row->id, 'note_id' => $row->business_campaign_id, 'lot_id' => $row->lot_id, 'listed_units' => (string) $row->listed_units,
            'remaining_units' => (string) $row->remaining_units, 'price' => self::money($row->price),
            'listed_at' => $row->listed_at->utc()->format(DateTimeInterface::ATOM), 'expires_at' => $row->expires_at->utc()->format(DateTimeInterface::ATOM),
            'state' => $this->restore($row, null)->at($at)->state,
            'cancellation' => $row->cancelled_by === null ? null : ['by' => $row->cancelled_by, 'reason' => $row->cancel_reason],
            'reservation' => $open === null ? null : $this->presentReservation($open), 'terms_version' => $row->terms_version];
    }

    /** @return array{reservation_id: string, units: string, price: array{currency: string, amount: string}, buyer_pays: array{currency: string, amount: string}, expires_at: string} */
    private function presentReservation(SecondaryReservationRecord $reservation): array
    {
        return ['reservation_id' => $reservation->id, 'units' => (string) $reservation->units, 'price' => self::money($reservation->price),
            'buyer_pays' => self::money($reservation->gross), 'expires_at' => $reservation->expires_at->utc()->format(DateTimeInterface::ATOM)];
    }

    /** @return array{currency: string, amount: string} */
    private static function money(string $amount): array
    {
        return ['currency' => 'RWF', 'amount' => $amount];
    }

    private static function now(): DateTimeImmutable
    {
        return now('UTC')->toDateTimeImmutable();
    }
}
