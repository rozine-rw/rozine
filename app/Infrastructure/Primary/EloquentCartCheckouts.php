<?php

declare(strict_types=1);

namespace App\Infrastructure\Primary;

use App\Application\Business\Contracts\PrimaryCampaignSource;
use App\Application\Identity\AuthorizeActiveRole;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Operations\Contracts\ChangeFeed;
use App\Application\Operations\Contracts\OperationJournal;
use App\Application\Primary\CartQuoteDisclosure;
use App\Application\Primary\Contracts\CartCheckouts;
use App\Application\Primary\Contracts\InvestorCart;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Domain\Operations\ChangeScope;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Domain\Primary\PrimaryDisclosure;
use App\Domain\Primary\PrimaryTerms;
use App\Domain\Primary\UnitRights;
use App\Models\InvestorCartCheckout;
use App\Models\InvestorCartCheckoutLine;
use App\Models\InvestorCartCheckoutVersion;
use App\Models\InvestorCartQuote;
use App\Models\InvestorCartRoot;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use App\Models\User;
use Brick\Math\BigInteger;
use Closure;
use DateTimeImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

/**
 * The whole-cart checkout (#383 step 4a, contract cart-checkout-1). One `cart.reserve` holds every
 * line of the saved cart, or none.
 *
 * Lock order, the same as the quote's and the single-line checkout's (contract §8): every Business
 * in the cart, by Business then campaign; current Investor authority (the actor and Party); the
 * parent journal key; the cart root; each campaign in ascending id; then, inside each line's
 * reservation, that campaign's reservations and the affected wallets. The cart lines are first read
 * without locks only to know which Businesses to lock. Cart writers also hold Investor authority and
 * raise the cart revision, so once authority and the root are held a cart at the expected revision
 * has exactly the lines that were read.
 *
 * Each line is reserved by the primary reservation port with its own child receipt: a
 * `primary.reserve` operation whose request id is derived from the parent, so the child keeps every
 * existing actor, receipt and cash constraint. Children all take the cart's one band. Any line
 * refused rolls back every hold: the parent keeps the refusal, naming each refused line with its
 * own code.
 *
 * Inferences (contract proposals P383-01 to P383-04, noted on the PR): one held checkout per Party,
 * refused `CART_CHECKOUT_ACTIVE`; a standalone hold in a cart campaign refuses
 * `CART_EXISTING_RESERVATION`; a stale quote refuses `CART_QUOTE_STALE` without a replacement, so the
 * Investor quotes again; the confirmation deadline is the earliest child window, and expiry can
 * settle once the last child window has closed.
 *
 * @phpstan-import-type CartReservation from CartCheckouts
 */
final readonly class EloquentCartCheckouts implements CartCheckouts
{
    /** Child request ids are UUIDv5 names under this namespace, derived from the parent operation. */
    private const string CHILD_NAMESPACE = '6f1d3c2a-8b4e-5f60-9a7b-1c2d3e4f5a6b';

    public function __construct(private AuthorizeActiveRole $authority, private PrimaryCampaignSource $campaigns, private OperationJournal $journal,
        private PrimaryReservations $reservations, private InvestorCart $cart, private ChangeFeed $changes, private CanonicalJson $json) {}

    public function reserve(int $userId, int $contextRevision, int $expectedCartRevision, string $quoteId, int $expectedQuoteRevision,
        string $quoteDisclosureSha256, string $requestId, Closure $price): array
    {
        return DB::transaction(function () use ($userId, $contextRevision, $expectedCartRevision, $quoteId, $expectedQuoteRevision, $quoteDisclosureSha256, $requestId, $price): array {
            $this->lockBusinesses($userId);

            return $this->authority->handle($userId, 'investor', null, $contextRevision,
                function (array $identity) use ($userId, $contextRevision, $expectedCartRevision, $quoteId, $expectedQuoteRevision, $quoteDisclosureSha256, $requestId, $price): array {
                    $partyId = strtolower((string) $identity['party']['id']);
                    $cartId = InvestorCartRoot::query()->where('party_id', $partyId)->value('id')
                        ?? throw new CommandRejection('CART_REVISION_CONFLICT', 409, 0);

                    return $this->journal->execute('party:'.$partyId, $userId, self::RESERVE_COMMAND, $requestId, 'investor_cart', (string) $cartId,
                        ['identity_context_revision' => $contextRevision, 'expected_cart_revision' => $expectedCartRevision, 'quote_id' => strtolower($quoteId),
                            'expected_quote_revision' => $expectedQuoteRevision, 'quote_disclosure_sha256' => strtolower($quoteDisclosureSha256)],
                        function (): void {},
                        fn (string $operationId): OperationResult => $this->hold($userId, $contextRevision, $partyId, $operationId, $expectedCartRevision,
                            strtolower($quoteId), $expectedQuoteRevision, strtolower($quoteDisclosureSha256), $price));
                });
        }, 3);
    }

    public function reservation(int $userId, int $contextRevision, string $checkoutId): array
    {
        return DB::transaction(function () use ($userId, $contextRevision, $checkoutId): array {
            $partyId = strtolower((string) User::query()->whereKey($userId)->value('party_id'));
            $campaignIds = InvestorCartCheckoutLine::query()->where('investor_cart_checkout_id', strtolower($checkoutId))
                ->whereIn('investor_cart_checkout_id', InvestorCartCheckout::query()->select('id')->where('party_id', $partyId))
                ->pluck('business_campaign_id')->map(fn (mixed $id): string => (string) $id)->all();
            $this->lockCampaignBusinesses($campaignIds);

            return $this->authority->handle($userId, 'investor', null, $contextRevision, function (array $identity) use ($checkoutId): array {
                $partyId = strtolower((string) $identity['party']['id']);
                $checkout = InvestorCartCheckout::query()->whereKey(strtolower($checkoutId))->where('party_id', $partyId)->first()
                    ?? throw new CommandRejection('CART_RESERVATION_NOT_FOUND', 404);

                return $this->project($checkout, $partyId);
            });
        });
    }

    public function findReservation(int $userId, int $contextRevision, string $requestId): array
    {
        return $this->authority->handle($userId, 'investor', null, $contextRevision, function (array $identity) use ($requestId): array {
            $partyId = strtolower((string) $identity['party']['id']);

            // The actor key already scopes the receipt to this Party, and every `cart.reserve` targets its own cart.
            return $this->journal->find('party:'.$partyId, self::RESERVE_COMMAND, $requestId, function (): void {});
        });
    }

    /**
     * The effect of one new `cart.reserve`, inside the journal's savepoint: every line held, or a
     * refusal that leaves nothing behind.
     */
    private function hold(int $userId, int $contextRevision, string $partyId, string $operationId, int $expectedCartRevision, string $quoteId,
        int $expectedQuoteRevision, string $quoteDisclosureSha256, Closure $price): OperationResult
    {
        $root = InvestorCartRoot::query()->where('party_id', $partyId)->lockForUpdate()->first();
        if ($root === null || $root->revision !== $expectedCartRevision) {
            throw new CommandRejection('CART_REVISION_CONFLICT', 409, $root->revision ?? 0);
        }
        $quote = InvestorCartQuote::query()->whereKey($quoteId)->where('party_id', $partyId)->first()
            ?? throw new CommandRejection('CART_QUOTE_NOT_FOUND', 404, $root->revision);
        if ($expectedQuoteRevision !== 1 || $quote->investor_cart_id !== $root->id || $quote->cart_revision !== $root->revision
            || $quote->cart_sha256 !== $root->sha256 || ! hash_equals($quote->disclosure_sha256, $quoteDisclosureSha256)) {
            throw $this->stale($root, $quote);
        }
        if (now('UTC')->greaterThanOrEqualTo($quote->expires_at)) {
            throw new CommandRejection('CART_QUOTE_EXPIRED', 409, $root->revision, [], ['cart_id' => $root->id, 'quote_id' => $quote->id,
                'atomic' => true, 'financial_effect' => 'none']);
        }
        $live = DB::table('investor_cart_live_checkouts')->where('party_id', $partyId)->value('investor_cart_checkout_id');
        if ($live !== null) {
            throw new CommandRejection('CART_CHECKOUT_ACTIVE', 409, $root->revision, [], ['cart_id' => $root->id, 'checkout_id' => (string) $live,
                'atomic' => true, 'financial_effect' => 'none']);
        }
        // The quote matched this cart's snapshot hash, and a quote is only ever made of a cart with lines.
        $lines = $this->cart->partyLines($partyId);
        $canonical = $lines;
        usort($canonical, fn (array $left, array $right): int => [$left['campaign_id'], $left['id']] <=> [$right['campaign_id'], $right['id']]);
        $inputs = [];
        $unavailable = [];
        foreach ($canonical as $line) {
            try {
                $inputs[$line['campaign_id']] = $this->campaigns->lock($line['campaign_id']);
            } catch (CommandRejection) {
                $unavailable[] = $line['campaign_id'];
            }
        }
        if ($unavailable !== []) {
            throw new CommandRejection('CART_LINE_UNAVAILABLE', 409, $root->revision, [], ['campaign_ids' => $unavailable]);
        }
        foreach ($inputs as $input) {
            $this->campaigns->rejectKnownConnections($input['business_id'], [$partyId]);
        }
        $standalone = $this->standaloneHolds($partyId, array_keys($inputs));
        if ($standalone !== []) {
            throw new CommandRejection('CART_EXISTING_RESERVATION', 409, $root->revision, [], ['cart_id' => $root->id, 'reservation_ids' => $standalone,
                'atomic' => true, 'financial_effect' => 'none']);
        }
        $cart = ['id' => $root->id, 'revision' => $root->revision, 'sha256' => $root->sha256, 'lines' => $lines];
        $body = $price($partyId, $cart, $inputs);
        if (! hash_equals($quote->disclosure_sha256, CartQuoteDisclosure::sha256($this->json, $cart, $body))) {
            throw $this->stale($root, $quote);
        }
        $band = ['tier' => $body['fee_band']['tier'], 'rate_bps' => $body['fee_band']['rate_bps'], 'basis' => $body['fee_band']['basis'],
            'policy_version' => $body['fee_band']['policy_version']];
        $admit = fn (UnitRights $rights, array $campaign): PrimaryTerms => PrimaryTerms::disclosed($campaign['rate_pct'], $campaign['term_months'],
            $campaign['policy_version'], PrimaryDisclosure::VERSION, $band, PrimaryTerms::payoutFeeFor($rights, $band['rate_bps']), $rights);
        $held = [];
        $refusals = [];
        foreach ($canonical as $line) {
            $child = $this->reserveLine($userId, $contextRevision, $partyId, $operationId, $line, $admit);
            if ($child['status'] !== 'completed') {
                $refusals[] = ['cart_line_id' => $line['id'], 'campaign_id' => $line['campaign_id'], 'code' => $child['code'],
                    'http_status' => $child['http_status'], 'field_errors' => $child['field_errors']];

                continue;
            }
            $held[] = ['line' => $line, 'result' => $child];
        }
        if ($refusals !== []) {
            $funds = array_all($refusals, fn (array $refusal): bool => $refusal['code'] === 'INSUFFICIENT_AVAILABLE_FUNDS');

            throw new CommandRejection($funds ? 'INSUFFICIENT_AVAILABLE_FUNDS' : 'CART_LINES_REFUSED', $funds ? 422 : 409, $root->revision, [],
                ['cart_id' => $root->id, 'cart_revision' => $root->revision, 'atomic' => true, 'financial_effect' => 'none',
                    'line_refusals' => $refusals, 'cart_refusals' => []]);
        }

        return $this->retain($partyId, $operationId, $root, $quote, $body['fee_band'], $held);
    }

    /**
     * Holds one line as an ordinary primary reservation under its own `primary.reserve` receipt, kept
     * by the journal exactly as a single-line reserve keeps it, plus the parent it belongs to. Its
     * request id is derived from the parent and the campaign, so it is unique and never chosen by a
     * client. A refused child keeps a refused receipt, which the parent's refusal rolls back.
     *
     * @param  array{id: string, campaign_id: string, units: int}  $line
     * @return array<string, mixed>
     */
    private function reserveLine(int $userId, int $contextRevision, string $partyId, string $parentId, array $line, Closure $admit): array
    {
        return $this->journal->execute('party:'.$partyId, $userId, 'primary.reserve', self::childRequestId($parentId, 'reserve', $line['campaign_id']),
            'campaign', $line['campaign_id'], ['identity_context_revision' => $contextRevision, 'units' => (string) $line['units'], 'cart_operation_id' => $parentId],
            function (): void {},
            function (string $childId) use ($line, $partyId, $parentId, $admit): OperationResult {
                $reserved = $this->reservations->reserve($line['campaign_id'], $partyId, $childId, (string) $line['units'], $admit);

                return new OperationResult('RESERVATION_HELD', ['reservation_id' => $reserved->id, 'entry_id' => $reserved->hold->entryId,
                    'origin_operation_id' => $childId, 'amount' => $reserved->hold->amount, 'cart_operation_id' => $parentId], 1);
            });
    }

    /**
     * Keeps the checkout, its first held version, its lines and the live marker, and returns the
     * parent's `CART_RESERVED` result.
     *
     * @param  array<string, mixed>  $band
     * @param  list<array{line: array{id: string, campaign_id: string, units: int}, result: array<string, mixed>}>  $held
     */
    private function retain(string $partyId, string $operationId, InvestorCartRoot $root, InvestorCartQuote $quote, array $band, array $held): OperationResult
    {
        $at = now('UTC')->toDateTimeImmutable();
        $checkoutId = strtolower((string) Str::ulid());
        $total = BigInteger::zero();
        $lines = [];
        $records = [];
        foreach ($held as $position => $child) {
            /** @var array{reservation_id: string, entry_id: string} $data */
            $data = $child['result']['data'];
            $record = PrimaryReservationRecord::query()->whereKey($data['reservation_id'])->sole();
            $facts = $this->reservations->reservationFacts($child['line']['campaign_id'], $partyId, $record->id);
            $total = $total->plus($record->principal);
            $records[] = $record;
            $lines[] = ['cart_line_id' => $child['line']['id'], 'campaign_id' => $child['line']['campaign_id'], 'reservation_id' => $record->id,
                'reservation_revision' => 1, 'principal' => self::money($record->principal), 'disclosure_sha256' => $facts['disclosure_sha256'],
                'entry_id' => $data['entry_id'], 'position' => $position + 1, 'child_operation_id' => (string) $child['result']['operation_id']];
        }
        /** @var non-empty-list<DateTimeImmutable> $deadlines Every checkout holds at least one line. */
        $deadlines = array_map(fn (PrimaryReservationRecord $record): DateTimeImmutable => $record->expires_at->toDateTimeImmutable(), $records);
        $checkout = new InvestorCartCheckout;
        $checkout->forceFill(['id' => $checkoutId, 'party_id' => $partyId, 'investor_cart_id' => $root->id, 'investor_cart_quote_id' => $quote->id,
            'cart_revision' => $root->revision, 'origin_operation_id' => $operationId, 'principal' => (string) $total, 'created_at' => $at,
            'expires_at' => min($deadlines), 'settles_at' => max($deadlines)])->save();
        $version = new InvestorCartCheckoutVersion;
        $version->forceFill(['id' => strtolower((string) Str::ulid()), 'investor_cart_checkout_id' => $checkoutId, 'revision' => 1, 'state' => 'held',
            'investor_cart_quote_id' => $quote->id, 'operation_id' => $operationId, 'created_at' => $at])->save();
        foreach ($lines as $line) {
            $record = new InvestorCartCheckoutLine;
            $record->forceFill(['investor_cart_checkout_id' => $checkoutId, 'investor_cart_line_id' => $line['cart_line_id'],
                'business_campaign_id' => $line['campaign_id'], 'primary_reservation_id' => $line['reservation_id'],
                'child_operation_id' => $line['child_operation_id'], 'position' => $line['position']])->save();
        }
        DB::table('investor_cart_live_checkouts')->insert(['party_id' => $partyId, 'investor_cart_checkout_id' => $checkoutId]);
        foreach ($lines as $line) {
            $campaign = $this->campaigns->lockRetained($line['campaign_id']);
            $this->changes->record(ChangeScope::party($partyId), 'purchase', $line['reservation_id']);
            $this->changes->record(ChangeScope::business($campaign['business_id']), 'campaign', $line['campaign_id']);
        }

        return new OperationResult('CART_RESERVED', ['checkout_id' => $checkoutId, 'cart_id' => $root->id, 'cart_revision' => $root->revision,
            'quote_id' => $quote->id, 'disclosure_sha256' => $quote->disclosure_sha256, 'state' => 'held', 'fee_band' => $band,
            'expires_at' => self::time(min($deadlines)), 'total_held' => self::money((string) $total),
            'lines' => array_map(fn (array $line): array => array_diff_key($line, ['position' => true, 'child_operation_id' => true]), $lines)], 1);
    }

    /** A child's request id: a UUIDv5 name of the parent operation, the phase and the campaign. */
    public static function childRequestId(string $parentOperationId, string $phase, string $campaignId): string
    {
        return Uuid::uuid5(self::CHILD_NAMESPACE, 'cart-child:'.$parentOperationId.':'.$phase.':'.$campaignId)->toString();
    }

    /** @return CartReservation */
    private function project(InvestorCartCheckout $checkout, string $partyId): array
    {
        $version = InvestorCartCheckoutVersion::query()->where('investor_cart_checkout_id', $checkout->id)->orderByDesc('revision')->firstOrFail();
        $quote = InvestorCartQuote::query()->whereKey($version->investor_cart_quote_id)->firstOrFail();
        $state = $version->state === 'held' && now('UTC')->greaterThanOrEqualTo($checkout->expires_at) ? 'release_pending' : $version->state;
        $lines = [];
        foreach (InvestorCartCheckoutLine::query()->where('investor_cart_checkout_id', $checkout->id)->orderBy('position')->get() as $line) {
            $facts = $this->reservations->reservationFacts($line->business_campaign_id, $partyId, $line->primary_reservation_id);
            $lines[] = ['cart_line_id' => $line->investor_cart_line_id, 'campaign_id' => $line->business_campaign_id,
                'reservation_id' => $line->primary_reservation_id, 'reservation_revision' => $facts['revision'],
                'principal' => $facts['principal'], 'disclosure_sha256' => $facts['disclosure_sha256']];
        }

        return ['id' => $checkout->id, 'cart_id' => $checkout->investor_cart_id, 'cart_revision' => $checkout->cart_revision,
            'revision' => $version->revision, 'state' => $state, 'quote' => $quote->payload, 'created_at' => self::time($checkout->created_at->toDateTimeImmutable()),
            'expires_at' => self::time($checkout->expires_at->toDateTimeImmutable()), 'lines' => $lines, 'total_held' => self::money($checkout->principal)];
    }

    /**
     * The Party's held standalone reservations in these campaigns: any reservation not yet confirmed,
     * released or expired that no cart checkout owns.
     *
     * @param  list<string>  $campaignIds
     * @return list<string>
     */
    private function standaloneHolds(string $partyId, array $campaignIds): array
    {
        return array_values(PrimaryReservationRecord::query()->where('party_id', $partyId)->whereIn('business_campaign_id', $campaignIds)
            ->whereNotExists(fn (Builder $query) => $query->selectRaw('1')->from((new PrimaryReservationVersion)->getTable())
                ->whereColumn('primary_reservation_id', 'primary_reservations.id')->whereIn('state', ['confirmed', 'released', 'expired']))
            ->whereNotExists(fn (Builder $query) => $query->selectRaw('1')->from('investor_cart_checkout_lines')
                ->whereColumn('primary_reservation_id', 'primary_reservations.id'))
            ->orderBy('id')->pluck('id')->map(fn (mixed $id): string => (string) $id)->all());
    }

    private function stale(InvestorCartRoot $root, InvestorCartQuote $quote): CommandRejection
    {
        return new CommandRejection('CART_QUOTE_STALE', 409, $root->revision, [], ['cart_id' => $root->id, 'quote_id' => $quote->id,
            'atomic' => true, 'financial_effect' => 'none', 'requires_reconfirmation' => true]);
    }

    /** Locks the Business of every campaign in the user's cart, by Business then campaign. */
    private function lockBusinesses(int $userId): void
    {
        $partyId = strtolower((string) User::query()->whereKey($userId)->value('party_id'));
        $this->lockCampaignBusinesses(array_column($this->cart->partyLines($partyId), 'campaign_id'));
    }

    /** @param list<string> $campaignIds */
    private function lockCampaignBusinesses(array $campaignIds): void
    {
        $ordered = DB::table('business_campaigns')->whereIn('id', $campaignIds)->orderBy('business_id')->orderBy('id')->pluck('id');
        foreach ($ordered as $campaignId) {
            $this->campaigns->lockBusiness((string) $campaignId);
        }
    }

    /** @return array{currency: string, amount: string} */
    private static function money(string $amount): array
    {
        return ['currency' => 'RWF', 'amount' => $amount];
    }

    private static function time(DateTimeImmutable $at): string
    {
        return $at->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s.u\Z');
    }
}
