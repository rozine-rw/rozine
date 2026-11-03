<?php

declare(strict_types=1);

namespace App\Infrastructure\Primary;

use App\Application\Business\Contracts\PrimaryCampaignSource;
use App\Application\Identity\AuthorizeActiveRole;
use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Operations\Contracts\ChangeFeed;
use App\Application\Operations\Contracts\OperationJournal;
use App\Application\Primary\CartQuoteDisclosure;
use App\Application\Primary\Contracts\CartCheckouts;
use App\Application\Primary\Contracts\CartSettlementAlerts;
use App\Application\Primary\Contracts\InvestorCart;
use App\Application\Primary\Contracts\InvestorCartQuotes;
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
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Closure;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use Throwable;

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
 * @phpstan-import-type QuotedCart from InvestorCartQuotes
 * @phpstan-import-type QuoteBody from InvestorCartQuotes
 */
final readonly class EloquentCartCheckouts implements CartCheckouts
{
    /** Child request ids are UUIDv5 names under this namespace, derived from the parent operation. */
    private const string CHILD_NAMESPACE = '6f1d3c2a-8b4e-5f60-9a7b-1c2d3e4f5a6b';

    public function __construct(private AuthorizeActiveRole $authority, private PrimaryCampaignSource $campaigns, private OperationJournal $journal,
        private PrimaryReservations $reservations, private InvestorCart $cart, private InvestorCartQuotes $quotes, private ChangeFeed $changes,
        private CanonicalJson $json, private AuthorizeStaffPermission $staff, private CartSettlementAlerts $alerts) {}

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

    public function confirm(int $userId, int $contextRevision, string $checkoutId, int $expectedRevision, string $quoteId, int $expectedQuoteRevision,
        string $acknowledgedDisclosureSha256, string $requestId, Closure $price): array
    {
        $checkoutId = strtolower($checkoutId);

        return DB::transaction(function () use ($userId, $contextRevision, $checkoutId, $expectedRevision, $quoteId, $expectedQuoteRevision, $acknowledgedDisclosureSha256, $requestId, $price): array {
            $this->lockCampaignBusinesses($this->checkoutCampaigns($userId, $checkoutId));

            return $this->authority->handle($userId, 'investor', null, $contextRevision,
                function (array $identity) use ($userId, $contextRevision, $checkoutId, $expectedRevision, $quoteId, $expectedQuoteRevision, $acknowledgedDisclosureSha256, $requestId, $price): array {
                    $partyId = strtolower((string) $identity['party']['id']);
                    $checkout = InvestorCartCheckout::query()->whereKey($checkoutId)->where('party_id', $partyId)->first()
                        ?? throw new CommandRejection('CART_RESERVATION_NOT_FOUND', 404);

                    return $this->journal->execute('party:'.$partyId, $userId, self::CONFIRM_COMMAND, $requestId, 'investor_cart_checkout', $checkout->id,
                        ['identity_context_revision' => $contextRevision, 'expected_revision' => $expectedRevision, 'quote_id' => strtolower($quoteId),
                            'expected_quote_revision' => $expectedQuoteRevision, 'acknowledged_disclosure_sha256' => strtolower($acknowledgedDisclosureSha256)],
                        function (): void {},
                        fn (string $operationId): OperationResult => $this->settle($userId, $contextRevision, $partyId, $operationId, $checkout,
                            $expectedRevision, strtolower($quoteId), $expectedQuoteRevision, strtolower($acknowledgedDisclosureSha256), $price));
                });
        }, 3);
    }

    public function reservation(int $userId, int $contextRevision, string $checkoutId): array
    {
        return DB::transaction(function () use ($userId, $contextRevision, $checkoutId): array {
            $this->lockCampaignBusinesses($this->checkoutCampaigns($userId, strtolower($checkoutId)));

            return $this->authority->handle($userId, 'investor', null, $contextRevision, function (array $identity) use ($checkoutId): array {
                $partyId = strtolower((string) $identity['party']['id']);
                $checkout = InvestorCartCheckout::query()->whereKey(strtolower($checkoutId))->where('party_id', $partyId)->first()
                    ?? throw new CommandRejection('CART_RESERVATION_NOT_FOUND', 404);

                return $this->project($checkout, $partyId);
            });
        });
    }

    public function live(int $userId, int $contextRevision): ?array
    {
        $checkoutId = $this->authority->handle($userId, 'investor', null, $contextRevision, fn (array $identity): mixed => DB::table('investor_cart_live_checkouts')
            ->where('party_id', strtolower((string) $identity['party']['id']))->value('investor_cart_checkout_id'));

        return is_string($checkoutId) ? $this->reservation($userId, $contextRevision, $checkoutId) : null;
    }

    public function findReservation(int $userId, int $contextRevision, string $requestId): array
    {
        return $this->authority->handle($userId, 'investor', null, $contextRevision, function (array $identity) use ($requestId): array {
            $partyId = strtolower((string) $identity['party']['id']);

            // The actor key already scopes the receipt to this Party, and every `cart.reserve` targets its own cart.
            return $this->journal->find('party:'.$partyId, self::RESERVE_COMMAND, $requestId, function (): void {});
        });
    }

    public function release(int $userId, int $contextRevision, string $checkoutId, int $expectedRevision, string $requestId): array
    {
        $checkoutId = strtolower($checkoutId);

        return DB::transaction(function () use ($userId, $contextRevision, $checkoutId, $expectedRevision, $requestId): array {
            $this->lockCampaignBusinesses($this->checkoutCampaigns($userId, $checkoutId));

            return $this->authority->handle($userId, 'investor', null, $contextRevision,
                function (array $identity) use ($userId, $contextRevision, $checkoutId, $expectedRevision, $requestId): array {
                    $partyId = strtolower((string) $identity['party']['id']);
                    $checkout = InvestorCartCheckout::query()->whereKey($checkoutId)->where('party_id', $partyId)->first()
                        ?? throw new CommandRejection('CART_RESERVATION_NOT_FOUND', 404);

                    return $this->journal->execute('party:'.$partyId, $userId, self::RELEASE_COMMAND, $requestId, 'investor_cart_checkout', $checkout->id,
                        ['identity_context_revision' => $contextRevision, 'expected_revision' => $expectedRevision], function (): void {},
                        fn (string $operationId): OperationResult => $this->releaseHeld($userId, $contextRevision, $partyId, $operationId, $checkout, $expectedRevision));
                });
        }, 3);
    }

    public function findOperation(int $userId, int $contextRevision, string $command, string $checkoutId, string $requestId): array
    {
        if (! in_array($command, [self::CONFIRM_COMMAND, self::RELEASE_COMMAND], true)) {
            throw new CommandRejection('OPERATION_NOT_FOUND', 404);
        }

        return $this->authority->handle($userId, 'investor', null, $contextRevision, function (array $identity) use ($command, $checkoutId, $requestId): array {
            $partyId = strtolower((string) $identity['party']['id']);

            // Every cart.confirm and cart.release targets a checkout; the request must name this one.
            return $this->journal->find('party:'.$partyId, $command, $requestId, function (string $type, string $id) use ($checkoutId): void {
                if ($id !== strtolower($checkoutId)) {
                    throw new CommandRejection('OPERATION_NOT_FOUND', 404);
                }
            });
        });
    }

    public function expireDue(int $limit): int
    {
        if ($limit < 1 || $limit > 1000) {
            throw new CommandRejection('INVALID_SWEEP_LIMIT');
        }
        $now = now('UTC')->format('Y-m-d H:i:s.uP');
        $fresh = $this->due($now)->whereNull('failures.investor_cart_checkout_id')
            ->orderBy('investor_cart_checkouts.settles_at')->orderBy('investor_cart_checkouts.id')->limit($limit)->get();
        $retries = $this->due($now)->whereNotNull('failures.investor_cart_checkout_id')
            ->selectRaw('failures.last_attempted_at + make_interval(mins => least(?, ? * failures.attempts)) as retry_due_at',
                [self::RETRY_BACKOFF_MAX_MINUTES, self::RETRY_BACKOFF_MINUTES])
            ->whereRaw('failures.last_attempted_at + make_interval(mins => least(?, ? * failures.attempts)) <= ?::timestamptz',
                [self::RETRY_BACKOFF_MAX_MINUTES, self::RETRY_BACKOFF_MINUTES, $now])
            ->orderBy('retry_due_at')->orderBy('investor_cart_checkouts.id')->limit($limit)->get();
        $oldestRetry = $retries->first()?->getAttribute('retry_due_at');
        [$freshTaken, $retriesTaken] = self::shares($limit, $fresh->count(), $retries->count(),
            self::retryWaitedLonger($fresh->first()?->settles_at, is_string($oldestRetry) ? CarbonImmutable::parse($oldestRetry) : null));
        $candidates = $retries->take($retriesTaken)->concat($fresh->take($freshTaken));
        $expired = 0;
        $failure = null;
        foreach ($candidates as $checkout) {
            try {
                $settled = DB::transaction(function () use ($checkout): bool {
                    $this->lockCampaignBusinesses($this->linesOf($checkout->id));

                    return $this->expireHeld($checkout, null) !== null;
                }, 3);
                DB::table('investor_cart_settlement_failures')->where('investor_cart_checkout_id', $checkout->id)->delete();
                $expired += (int) $settled;
            } catch (Throwable $exception) {
                $this->stuckFailure($checkout, $exception);
                $failure ??= $exception;
            }
        }
        if ($failure !== null) {
            throw $failure;
        }

        return $expired;
    }

    /**
     * How many fresh and retry candidates one sweep takes. Each class gets half the limit, the odd
     * slot alternating between them (`$retryFirst`, from the sweep turn counter, so successive
     * sweeps alternate however they are scheduled), and a class with
     * fewer due than its share gives the rest to the other. Neither class can starve the other,
     * even at a limit of 1.
     *
     * @return array{int, int} fresh, then retries
     */
    public static function shares(int $limit, int $fresh, int $retries, bool $retryFirst): array
    {
        $retryShare = intdiv($limit, 2) + ($retryFirst ? $limit % 2 : 0);
        $retriesTaken = min($retries, max($retryShare, $limit - $fresh));

        return [min($fresh, $limit - $retriesTaken), $retriesTaken];
    }

    /**
     * Whether the odd sweep slot goes to the retry: the class whose oldest checkout has waited longer
     * takes it. A fresh checkout waits from its settlement time and a retry from the end of its
     * backoff. A served retry that fails again waits from its next backoff, and a served fresh checkout
     * either settles or becomes a retry, so the other class is next however the sweeps are spaced.
     */
    public static function retryWaitedLonger(?CarbonInterface $oldestFresh, ?CarbonInterface $oldestRetry): bool
    {
        if ($oldestRetry === null) {
            return false;
        }

        return $oldestFresh === null || $oldestRetry->lessThan($oldestFresh);
    }

    /**
     * Live checkouts whose last line window has closed, with their failure row when they have one.
     *
     * @return EloquentBuilder<InvestorCartCheckout>
     */
    private function due(string $now): EloquentBuilder
    {
        return InvestorCartCheckout::query()->select('investor_cart_checkouts.*')
            ->join('investor_cart_live_checkouts as live', 'live.investor_cart_checkout_id', 'investor_cart_checkouts.id')
            ->leftJoin('investor_cart_settlement_failures as failures', 'failures.investor_cart_checkout_id', 'investor_cart_checkouts.id')
            ->where('investor_cart_checkouts.settles_at', '<=', $now);
    }

    public function settleStuck(int $staffUserId, string $checkoutId, string $requestId): array
    {
        $checkoutId = strtolower($checkoutId);
        $this->staff->check($staffUserId, 'cart.releases.resolve');

        return DB::transaction(function () use ($staffUserId, $checkoutId, $requestId): array {
            $this->lockCampaignBusinesses($this->linesOf($checkoutId));

            return $this->journal->execute('staff:'.$staffUserId, $staffUserId, self::SETTLE_COMMAND, $requestId, 'investor_cart_checkout', $checkoutId, [],
                function (): void {}, function (string $operationId) use ($checkoutId): OperationResult {
                    $checkout = InvestorCartCheckout::query()->whereKey($checkoutId)->first() ?? throw new CommandRejection('CART_RESERVATION_NOT_FOUND', 404);
                    $result = $this->expireHeld($checkout, $operationId);
                    DB::table('investor_cart_settlement_failures')->where('investor_cart_checkout_id', $checkout->id)->delete();

                    return $result;
                });
        }, 3);
    }

    public function stuck(): array
    {
        $cutoff = now('UTC')->subSeconds(self::STUCK_AFTER_SECONDS)->format('Y-m-d H:i:s.uP');

        return array_values(InvestorCartCheckout::query()
            ->select('investor_cart_checkouts.*', 'failures.attempts', 'failures.reason_code', 'failures.last_attempted_at', 'users.name as investor')
            ->join('investor_cart_live_checkouts as live', 'live.investor_cart_checkout_id', 'investor_cart_checkouts.id')
            ->leftJoin('users', 'users.party_id', 'investor_cart_checkouts.party_id')
            ->leftJoin('investor_cart_settlement_failures as failures', 'failures.investor_cart_checkout_id', 'investor_cart_checkouts.id')
            ->where(fn (EloquentBuilder $query) => $query->whereNotNull('failures.investor_cart_checkout_id')->orWhere('investor_cart_checkouts.settles_at', '<=', $cutoff))
            ->orderBy('investor_cart_checkouts.settles_at')->orderBy('investor_cart_checkouts.id')->get()
            ->map(fn (InvestorCartCheckout $checkout): array => ['checkout_id' => $checkout->id, 'party_id' => $checkout->party_id,
                'investor' => self::nullableString($checkout->getAttribute('investor')), 'total' => $checkout->principal,
                'lines' => InvestorCartCheckoutLine::query()->where('investor_cart_checkout_id', $checkout->id)->count(),
                'settles_at' => self::time($checkout->settles_at->toDateTimeImmutable()), 'attempts' => (int) $checkout->getAttribute('attempts'),
                'reason_code' => self::nullableString($checkout->getAttribute('reason_code')),
                'last_attempted_at' => $checkout->getAttribute('last_attempted_at') === null ? null
                    : self::time(new DateTimeImmutable((string) $checkout->getAttribute('last_attempted_at')))])->all());
    }

    /**
     * The effect of one new `cart.release`, inside the journal's savepoint: every line released and
     * its principal returned, or a refusal that leaves nothing behind.
     */
    private function releaseHeld(int $userId, int $contextRevision, string $partyId, string $operationId, InvestorCartCheckout $checkout, int $expectedRevision): OperationResult
    {
        [, $version] = $this->heldVersion($checkout, $expectedRevision);
        $released = [];
        foreach ($this->heldLines($checkout) as $line) {
            $facts = $this->reservations->reservationFacts($line->business_campaign_id, $partyId, $line->primary_reservation_id);
            // The checkout is held and inside its window, so every line releases.
            $child = $this->journal->execute('party:'.$partyId, $userId, 'primary.release', self::childRequestId($operationId, 'release', $line->business_campaign_id),
                'primary_reservation', $line->primary_reservation_id, ['identity_context_revision' => $contextRevision, 'campaign_id' => $line->business_campaign_id,
                    'expected_revision' => $facts['revision'], 'cart_operation_id' => $operationId],
                function (): void {},
                function (string $childId) use ($line, $partyId, $operationId, $facts): OperationResult {
                    $result = $this->reservations->release($line->business_campaign_id, $line->primary_reservation_id, $partyId, $childId, $facts['revision']);

                    return new OperationResult('RESERVATION_RELEASED', ['reservation_id' => $result->id, 'entry_id' => $result->posting->entryId,
                        'origin_operation_id' => (string) PrimaryReservationRecord::query()->whereKey($result->id)->value('origin_operation_id'), 'amount' => $result->posting->amount, 'cart_operation_id' => $operationId],
                        $result->revision);
                });
            if ($child['status'] !== 'completed') {
                throw new RuntimeException('CART_CHECKOUT_INTEGRITY_FAILED');
            }
            $released[] = ['cart_line_id' => $line->investor_cart_line_id, 'campaign_id' => $line->business_campaign_id,
                'reservation_id' => $line->primary_reservation_id, 'entry_id' => $child['data']['entry_id'], 'amount' => self::money((string) $child['data']['amount'])];
            $this->recordChange($partyId, $line);
        }
        $at = now('UTC')->toDateTimeImmutable();
        $this->appendVersion($checkout, $version->revision + 1, 'released', $version->investor_cart_quote_id, $operationId, $at);
        DB::table('investor_cart_live_checkouts')->where('investor_cart_checkout_id', $checkout->id)->delete();

        return new OperationResult('CART_RELEASED', ['checkout_id' => $checkout->id, 'state' => 'released', 'released_at' => self::time($at),
            'total_returned' => self::money($checkout->principal), 'lines' => $released], $version->revision + 1);
    }

    /**
     * Expires one held checkout as a group once its last line window has closed: every child
     * expires and returns its principal, the checkout appends its `expired` version and is no
     * longer live. The caller holds the Business locks. A sweep passes no operation, and gets null
     * for a checkout that was settled after it picked it; a staff retry passes its `cart.settle`
     * operation and is refused instead.
     *
     * @return ($operationId is null ? OperationResult|null : OperationResult)
     */
    private function expireHeld(InvestorCartCheckout $checkout, ?string $operationId): ?OperationResult
    {
        InvestorCartRoot::query()->whereKey($checkout->investor_cart_id)->lockForUpdate()->sole();
        $version = InvestorCartCheckoutVersion::query()->where('investor_cart_checkout_id', $checkout->id)->orderByDesc('revision')->firstOrFail();
        if ($version->state !== 'held' && $operationId === null) {
            return null;
        }
        if ($version->state !== 'held') {
            throw new CommandRejection('CART_RESERVATION_NOT_HELD', 409, $version->revision, [], ['checkout_id' => $checkout->id, 'state' => $version->state]);
        }
        if (now('UTC')->lessThan($checkout->settles_at)) {
            throw new CommandRejection('CART_SETTLEMENT_NOT_DUE', 409, $version->revision, [], ['checkout_id' => $checkout->id,
                'settles_at' => self::time($checkout->settles_at->toDateTimeImmutable())]);
        }
        $expired = [];
        foreach ($this->heldLines($checkout) as $line) {
            $result = $this->reservations->expire($line->business_campaign_id, $line->primary_reservation_id)
                ?? throw new RuntimeException('CART_CHECKOUT_INTEGRITY_FAILED');
            $expired[] = ['cart_line_id' => $line->investor_cart_line_id, 'campaign_id' => $line->business_campaign_id,
                'reservation_id' => $line->primary_reservation_id, 'entry_id' => $result->posting->entryId, 'amount' => self::money($result->posting->amount)];
            $this->recordChange($checkout->party_id, $line);
        }
        $at = now('UTC')->toDateTimeImmutable();
        $this->appendVersion($checkout, $version->revision + 1, 'expired', $version->investor_cart_quote_id, $operationId, $at);
        DB::table('investor_cart_live_checkouts')->where('investor_cart_checkout_id', $checkout->id)->delete();

        return new OperationResult('CART_EXPIRED', ['checkout_id' => $checkout->id, 'state' => 'expired', 'expired_at' => self::time($at),
            'total_returned' => self::money($checkout->principal), 'lines' => $expired], $version->revision + 1);
    }

    /**
     * Records a failed group expiry. Its first failure alerts every enabled staff member who may
     * resolve it from the console; a later failure only counts the attempt.
     */
    private function stuckFailure(InvestorCartCheckout $checkout, Throwable $exception): void
    {
        $reasonCode = $exception instanceof CommandRejection ? $exception->reason : 'SETTLEMENT_FAILED';
        $at = now('UTC')->format('Y-m-d H:i:s.uP');
        $first = DB::table('investor_cart_settlement_failures')->insertOrIgnore(['investor_cart_checkout_id' => $checkout->id, 'first_failed_at' => $at,
            'last_attempted_at' => $at, 'attempts' => 1, 'reason_code' => $reasonCode, 'exception_class' => mb_substr($exception::class, 0, 200)]) === 1;
        if (! $first) {
            DB::table('investor_cart_settlement_failures')->where('investor_cart_checkout_id', $checkout->id)->update(['last_attempted_at' => $at,
                'attempts' => DB::raw('attempts + 1'), 'reason_code' => $reasonCode, 'exception_class' => mb_substr($exception::class, 0, 200)]);
        }
        Log::error('Cart checkout expiry failed.', ['checkout_id' => $checkout->id, 'exception_class' => $exception::class, 'reason_code' => $reasonCode]);
        if ($first) {
            $this->alerts->stuck($checkout->id, $checkout->principal, $reasonCode);
        }
    }

    /**
     * The latest version of a checkout that is held, at the expected revision and inside its
     * window, or the refusal that says why not.
     */
    /** @return array{InvestorCartRoot, InvestorCartCheckoutVersion, array<string, mixed>} */
    private function heldVersion(InvestorCartCheckout $checkout, int $expectedRevision): array
    {
        $root = InvestorCartRoot::query()->whereKey($checkout->investor_cart_id)->lockForUpdate()->sole();
        $version = InvestorCartCheckoutVersion::query()->where('investor_cart_checkout_id', $checkout->id)->orderByDesc('revision')->firstOrFail();
        $refusal = ['cart_id' => $root->id, 'checkout_id' => $checkout->id, 'cart_revision' => $root->revision, 'atomic' => true, 'financial_effect' => 'none'];
        if ($version->revision !== $expectedRevision) {
            throw new CommandRejection('VERSION_CONFLICT', 409, $version->revision, [], $refusal);
        }
        if ($version->state !== 'held') {
            throw new CommandRejection('CART_RESERVATION_NOT_HELD', 409, $version->revision, [], [...$refusal, 'state' => $version->state]);
        }
        if (now('UTC')->greaterThanOrEqualTo($checkout->expires_at)) {
            throw new CommandRejection('CART_RESERVATION_EXPIRED', 409, $version->revision, [], [...$refusal, 'state' => 'release_pending']);
        }

        return [$root, $version, $refusal];
    }

    /** @return list<InvestorCartCheckoutLine> */
    private function heldLines(InvestorCartCheckout $checkout): array
    {
        return array_values(InvestorCartCheckoutLine::query()->where('investor_cart_checkout_id', $checkout->id)->orderBy('business_campaign_id')->get()->all());
    }

    /**
     * The campaigns of any checkout, read without locks only to know which Businesses to lock.
     *
     * @return list<string>
     */
    private function linesOf(string $checkoutId): array
    {
        return array_values(InvestorCartCheckoutLine::query()->where('investor_cart_checkout_id', $checkoutId)
            ->pluck('business_campaign_id')->map(fn (mixed $id): string => (string) $id)->all());
    }

    private static function nullableString(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
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
     * The effect of one new `cart.confirm`, inside the journal's savepoint: every line bought, every
     * changed line requoted with nothing bought, or a refusal that leaves nothing behind.
     */
    private function settle(int $userId, int $contextRevision, string $partyId, string $operationId, InvestorCartCheckout $checkout, int $expectedRevision,
        string $quoteId, int $expectedQuoteRevision, string $acknowledgedSha256, Closure $price): OperationResult
    {
        [$root, $version, $refusal] = $this->heldVersion($checkout, $expectedRevision);
        $quote = InvestorCartQuote::query()->whereKey($version->investor_cart_quote_id)->sole();
        if ($quoteId !== $quote->id || $expectedQuoteRevision !== 1 || ! hash_equals($quote->disclosure_sha256, $acknowledgedSha256)) {
            throw new CommandRejection('CART_QUOTE_STALE', 409, $version->revision, [], [...$refusal, 'quote_id' => $quote->id, 'requires_reconfirmation' => true]);
        }
        // While the checkout is held the cart cannot change, so the cart now is the cart it holds.
        $lines = $this->cart->partyLines($partyId);
        $held = InvestorCartCheckoutLine::query()->where('investor_cart_checkout_id', $checkout->id)->orderBy('business_campaign_id')->get();
        if ($root->revision !== $checkout->cart_revision || count($lines) !== $held->count()) {
            throw new RuntimeException('CART_CHECKOUT_INTEGRITY_FAILED');
        }
        // Each campaign's retained publication prices its line; admitting the line again below refuses a closed or funded raise.
        $inputs = [];
        foreach ($held as $line) {
            $inputs[$line->business_campaign_id] = $this->campaigns->lockRetained($line->business_campaign_id);
        }
        $cart = ['id' => $root->id, 'revision' => $root->revision, 'sha256' => $root->sha256, 'lines' => $lines];
        $body = $price($partyId, $cart, $inputs);
        $band = ['tier' => $body['fee_band']['tier'], 'rate_bps' => $body['fee_band']['rate_bps'], 'basis' => $body['fee_band']['basis'],
            'policy_version' => $body['fee_band']['policy_version']];
        $admit = fn (UnitRights $rights, array $campaign): PrimaryTerms => PrimaryTerms::disclosed($campaign['rate_pct'], $campaign['term_months'],
            $campaign['policy_version'], PrimaryDisclosure::VERSION, $band, PrimaryTerms::payoutFeeFor($rights, $band['rate_bps']), $rights);
        $changed = [];
        $refusals = [];
        foreach ($held as $line) {
            try {
                if ($this->reservations->requotes($line->business_campaign_id, $line->primary_reservation_id, $partyId, $admit)) {
                    $changed[] = $line;
                }
            } catch (CommandRejection $exception) {
                $refusals[] = self::lineRefusal($line, $exception->reason, $exception->status, $exception->fieldErrors);
            }
        }
        if ($refusals !== []) {
            throw $this->linesRefused($refusal, $refusals);
        }
        if ($changed !== [] || ! hash_equals($quote->disclosure_sha256, CartQuoteDisclosure::sha256($this->json, $cart, $body))) {
            return $this->requote($userId, $contextRevision, $partyId, $operationId, $checkout, $version, $cart, $body, $changed, $admit);
        }

        return $this->buy($userId, $contextRevision, $partyId, $operationId, $checkout, $version, $quote, $body['fee_band'], array_values($held->all()), $admit);
    }

    /**
     * Confirms every held line under its own `primary.confirm` receipt, takes the bought lines out
     * of the cart in one revision and closes the checkout `confirmed`.
     *
     * @param  array<string, mixed>  $band
     * @param  list<InvestorCartCheckoutLine>  $held
     */
    private function buy(int $userId, int $contextRevision, string $partyId, string $operationId, InvestorCartCheckout $checkout,
        InvestorCartCheckoutVersion $version, InvestorCartQuote $quote, array $band, array $held, Closure $admit): OperationResult
    {
        $bought = [];
        foreach ($held as $line) {
            // Every line was admitted again at its current terms just now, so each one must buy.
            $child = $this->confirmLine($userId, $contextRevision, $partyId, $operationId, 'confirm', $line, $admit);
            if ($child['status'] !== 'completed' || $child['code'] !== 'RESERVATION_CONFIRMED') {
                throw new RuntimeException('CART_CHECKOUT_INTEGRITY_FAILED');
            }
            $bought[] = ['line' => $line, 'result' => $child];
        }
        $at = now('UTC')->toDateTimeImmutable();
        $this->appendVersion($checkout, $version->revision + 1, 'confirmed', $quote->id, $operationId, $at);
        DB::table('investor_cart_live_checkouts')->where('party_id', $partyId)->where('investor_cart_checkout_id', $checkout->id)->delete();
        $cart = $this->cart->checkedOut($partyId, array_map(fn (array $child): string => $child['line']->investor_cart_line_id, $bought));
        $lines = [];
        foreach ($bought as $child) {
            /** @var array{commitment_id: string, entry_id: string, terms: array{earnings_fee: array{rate_bps: int}}, disclosure_sha256: string} $data */
            $data = $child['result']['data'];
            $line = $child['line'];
            $lines[] = ['cart_line_id' => $line->investor_cart_line_id, 'campaign_id' => $line->business_campaign_id, 'reservation_id' => $line->primary_reservation_id,
                'commitment_id' => $data['commitment_id'], 'entry_id' => $data['entry_id'],
                'principal' => self::money((string) PrimaryReservationRecord::query()->whereKey($line->primary_reservation_id)->value('principal')),
                'fee_rate_bps' => $data['terms']['earnings_fee']['rate_bps'], 'disclosure_sha256' => $data['disclosure_sha256']];
            $this->recordChange($partyId, $line);
        }

        return new OperationResult('CART_CONFIRMED', ['checkout_id' => $checkout->id, 'quote_id' => $quote->id, 'disclosure_sha256' => $quote->disclosure_sha256,
            'fee_band' => $band, 'confirmed_at' => self::time($at), 'total_principal' => self::money($checkout->principal), 'lines' => $lines, 'cart' => $cart],
            $version->revision + 1);
    }

    /**
     * Keeps the replacement quote, gives every changed line its new terms under its own
     * `primary.confirm` receipt (a requote that buys nothing) and moves the checkout to its next held
     * revision. Nothing is bought, and every line stays held to the original deadline.
     *
     * @param  QuotedCart  $cart
     * @param  QuoteBody  $body
     * @param  list<InvestorCartCheckoutLine>  $changed
     */
    private function requote(int $userId, int $contextRevision, string $partyId, string $operationId, InvestorCartCheckout $checkout,
        InvestorCartCheckoutVersion $version, array $cart, array $body, array $changed, Closure $admit): OperationResult
    {
        $replacement = $this->quotes->replacement($partyId, $cart, $body, $checkout->expires_at->toDateTimeImmutable()->setTimezone(new \DateTimeZone('UTC')));
        foreach ($changed as $line) {
            $child = $this->confirmLine($userId, $contextRevision, $partyId, $operationId, 'requote', $line, $admit);
            if ($child['status'] !== 'completed' || $child['code'] !== 'RESERVATION_REQUOTED') {
                throw new RuntimeException('CART_CHECKOUT_INTEGRITY_FAILED');
            }
            $this->changes->record(ChangeScope::party($partyId), 'purchase', $line->primary_reservation_id);
        }
        $this->appendVersion($checkout, $version->revision + 1, 'held', (string) $replacement['id'], $operationId, now('UTC')->toDateTimeImmutable());

        return new OperationResult('CART_REQUOTED', ['checkout_id' => $checkout->id, 'state' => 'held', 'revision' => $version->revision + 1,
            'expires_at' => self::time($checkout->expires_at->toDateTimeImmutable()), 'replacement_quote' => $replacement, 'requires_reconfirmation' => true,
            'changed_line_ids' => array_map(fn (InvestorCartCheckoutLine $line): string => $line->investor_cart_line_id, $changed)], $version->revision + 1);
    }

    /**
     * Confirms one held line as an ordinary `primary.confirm`, with a request id derived from the
     * parent, the phase and the campaign. At the line's current terms it buys the line; at changed
     * terms it appends them as a requote and buys nothing.
     *
     * @return array<string, mixed>
     */
    private function confirmLine(int $userId, int $contextRevision, string $partyId, string $parentId, string $phase, InvestorCartCheckoutLine $line, Closure $admit): array
    {
        $facts = $this->reservations->reservationFacts($line->business_campaign_id, $partyId, $line->primary_reservation_id);

        return $this->journal->execute('party:'.$partyId, $userId, 'primary.confirm', self::childRequestId($parentId, $phase, $line->business_campaign_id),
            'primary_reservation', $line->primary_reservation_id, ['identity_context_revision' => $contextRevision, 'campaign_id' => $line->business_campaign_id,
                'expected_revision' => $facts['revision'], 'disclosure_version' => PrimaryDisclosure::VERSION, 'disclosure_sha256' => $facts['disclosure_sha256'],
                'cart_operation_id' => $parentId],
            function (): void {},
            function (string $childId) use ($line, $partyId, $parentId, $facts, $admit): OperationResult {
                $result = $this->reservations->confirm($line->business_campaign_id, $line->primary_reservation_id, $partyId, $childId, $facts['revision'],
                    PrimaryDisclosure::VERSION, $facts['disclosure_sha256'], $admit);

                return new OperationResult($result->commitmentId === null ? 'RESERVATION_REQUOTED' : 'RESERVATION_CONFIRMED',
                    ['reservation_id' => $result->id, 'commitment_id' => $result->commitmentId, 'entry_id' => $result->posting?->entryId,
                        'amount' => (string) $result->reservation->rights->principal, 'terms' => $result->reservation->terms->toArray(),
                        'disclosure_sha256' => $result->reservation->terms->disclosureSha256,
                        'expires_at' => $result->reservation->window->expiresAt->format('Y-m-d\TH:i:s.u\Z'), 'cart_operation_id' => $parentId], $result->revision);
            });
    }

    private function appendVersion(InvestorCartCheckout $checkout, int $revision, string $state, string $quoteId, ?string $operationId, DateTimeImmutable $at): void
    {
        $version = new InvestorCartCheckoutVersion;
        $version->forceFill(['id' => strtolower((string) Str::ulid()), 'investor_cart_checkout_id' => $checkout->id, 'revision' => $revision, 'state' => $state,
            'investor_cart_quote_id' => $quoteId, 'operation_id' => $operationId, 'created_at' => $at])->save();
    }

    private function recordChange(string $partyId, InvestorCartCheckoutLine $line): void
    {
        $campaign = $this->campaigns->lockRetained($line->business_campaign_id);
        $this->changes->record(ChangeScope::party($partyId), 'purchase', $line->primary_reservation_id);
        $this->changes->record(ChangeScope::business($campaign['business_id']), 'campaign', $line->business_campaign_id);
    }

    /**
     * @param  array<string, list<string>>  $fieldErrors
     * @return array{cart_line_id: string, campaign_id: string, code: string, http_status: int, field_errors: array<string, list<string>>}
     */
    private static function lineRefusal(InvestorCartCheckoutLine $line, string $code, int $httpStatus, array $fieldErrors): array
    {
        return ['cart_line_id' => $line->investor_cart_line_id, 'campaign_id' => $line->business_campaign_id, 'code' => $code,
            'http_status' => $httpStatus, 'field_errors' => $fieldErrors];
    }

    /**
     * @param  array<string, mixed>  $refusal
     * @param  list<array<string, mixed>>  $lines
     */
    private function linesRefused(array $refusal, array $lines): CommandRejection
    {
        return new CommandRejection('CART_LINES_REFUSED', 409, null, [], [...$refusal, 'line_refusals' => $lines, 'cart_refusals' => []]);
    }

    /**
     * The campaigns of the user's own checkout, read without locks only to know which Businesses to
     * lock; an unknown or foreign checkout has none and is refused once authority is held.
     *
     * @return list<string>
     */
    private function checkoutCampaigns(int $userId, string $checkoutId): array
    {
        $partyId = strtolower((string) User::query()->whereKey($userId)->value('party_id'));

        return array_values(InvestorCartCheckoutLine::query()->where('investor_cart_checkout_id', $checkoutId)
            ->whereIn('investor_cart_checkout_id', InvestorCartCheckout::query()->select('id')->where('party_id', $partyId))
            ->pluck('business_campaign_id')->map(fn (mixed $id): string => (string) $id)->all());
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
