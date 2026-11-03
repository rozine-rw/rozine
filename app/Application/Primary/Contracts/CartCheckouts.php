<?php

declare(strict_types=1);

namespace App\Application\Primary\Contracts;

use Closure;

/**
 * The whole-cart checkout aggregate (#383 step 4, contract cart-checkout-1 §§5–9).
 *
 * `reserve()` holds every line of the saved cart at once, or none. It locks every Business in the
 * cart (by Business, then campaign), then current Investor authority, then journals one
 * `cart.reserve` operation per (Party, request id). Inside it, under the cart root lock, it
 * authenticates the quote the Investor accepted, locks each campaign in ascending id order,
 * prices the cart again through `$price` and refuses `CART_QUOTE_STALE` if anything the Investor
 * acknowledged changed. It then reserves each line as an ordinary primary reservation under the
 * one common band, each with its own `primary.reserve` receipt that names the parent. Any line
 * refused rolls back every hold, and the parent keeps the refusal with every line's code.
 *
 * `confirm()` buys every held line at once, or none (step 4b). It takes the same locks as
 * `reserve()` for the checkout's campaigns, then journals one `cart.confirm` per (Party, request id)
 * targeting the checkout. Under the cart root lock it requires the expected checkout revision, a held
 * checkout before its deadline and the acknowledgement of its current quote, then prices the held
 * cart again through `$price`. If the cart's terms and every line's terms still stand, each line is
 * confirmed under its own `primary.confirm` receipt, the bought lines leave the cart in one revision
 * and the checkout is `confirmed`. If anything the Investor acknowledged changed, nothing is bought:
 * a replacement quote is kept, every changed line takes its new terms, the checkout moves to the
 * next held revision and the result is `CART_REQUOTED`, to be confirmed again explicitly. Any line
 * refused refuses the whole cart and changes nothing.
 *
 * `reservation()` reads the Party's own checkout without changing anything. Each line carries its
 * reserved return and earnings fee as its held reservation fixed them (the fee rounded per scheduled
 * payout), and `reserved` sums them: these, not the quote's indicative figures, are what Confirm all
 * acknowledges. A held checkout past
 * its deadline reads `release_pending` until expiry settles it; a read never settles.
 *
 * @phpstan-import-type QuotedCart from InvestorCartQuotes
 * @phpstan-import-type QuoteBody from InvestorCartQuotes
 * @phpstan-import-type CartCampaign from InvestorCartQuotes
 *
 * @phpstan-type CartReservation array{id: string, cart_id: string, cart_revision: int, revision: int, state: string,
 *     quote: array<string, mixed>, created_at: string, expires_at: string,
 *     lines: list<array{cart_line_id: string, campaign_id: string, reservation_id: string, reservation_revision: int,
 *         principal: array{currency: string, amount: string}, contractual_return: array{currency: string, amount: string},
 *         earnings_fee: array{currency: string, amount: string}, net_return: array{currency: string, amount: string},
 *         disclosure_sha256: string}>,
 *     total_held: array{currency: string, amount: string},
 *     reserved: array{contractual_return: array{currency: string, amount: string}, earnings_fee: array{currency: string, amount: string},
 *         net_return: array{currency: string, amount: string}}}
 */
interface CartCheckouts
{
    /** The command a whole-cart reserve journals under. */
    public const string RESERVE_COMMAND = 'cart.reserve';

    /** The command a whole-cart confirmation journals under. */
    public const string CONFIRM_COMMAND = 'cart.confirm';

    /** The command an Investor's whole-cart release journals under. */
    public const string RELEASE_COMMAND = 'cart.release';

    /** The command a staff retry of a stuck expiry journals under. */
    public const string SETTLE_COMMAND = 'cart.settle';

    /** A held checkout this long past its last window is stuck even without a recorded failure. */
    public const int STUCK_AFTER_SECONDS = 300;

    /** Minutes a failed group expiry waits per failed attempt before the sweep retries it. */
    public const int RETRY_BACKOFF_MINUTES = 5;

    /** The longest a failed group expiry waits between sweep retries. */
    public const int RETRY_BACKOFF_MAX_MINUTES = 60;

    /**
     * @param  Closure(string, QuotedCart, array<string, CartCampaign>): QuoteBody  $price
     * @return array<string, mixed>
     */
    public function reserve(int $userId, int $contextRevision, int $expectedCartRevision, string $quoteId, int $expectedQuoteRevision,
        string $quoteDisclosureSha256, string $requestId, Closure $price): array;

    /**
     * @param  Closure(string, QuotedCart, array<string, CartCampaign>): QuoteBody  $price
     * @return array<string, mixed>
     */
    public function confirm(int $userId, int $contextRevision, string $checkoutId, int $expectedRevision, string $quoteId, int $expectedQuoteRevision,
        string $acknowledgedDisclosureSha256, string $requestId, Closure $price): array;

    /** @return CartReservation */
    public function reservation(int $userId, int $contextRevision, string $checkoutId): array;

    /**
     * The Party's live checkout, held or past its deadline and not yet settled, read as
     * `reservation()` reads it; null when the Party has none. The cart page opens on it.
     *
     * @return CartReservation|null
     */
    public function live(int $userId, int $contextRevision): ?array;

    /**
     * The retained `cart.reserve` result for this Party and request id, or `OPERATION_NOT_FOUND`.
     *
     * @return array<string, mixed>
     */
    public function findReservation(int $userId, int $contextRevision, string $requestId): array;

    /**
     * Returns every held line's principal at once, or none (step 4c, contract
     * `ReleaseCartReservation`): one `cart.release` per (Party, request id) targeting the checkout,
     * each line released under its own `primary.release` receipt. The cart keeps its lines.
     *
     * @return array<string, mixed>
     */
    public function release(int $userId, int $contextRevision, string $checkoutId, int $expectedRevision, string $requestId): array;

    /**
     * The retained `cart.confirm` or `cart.release` result for this Party, checkout and request id,
     * or `OPERATION_NOT_FOUND`, also for another command or another checkout's request.
     *
     * @return array<string, mixed>
     */
    public function findOperation(int $userId, int $contextRevision, string $command, string $checkoutId, string $requestId): array;

    /**
     * Expires every held checkout whose last line window has closed, each as one group: every
     * child expires and returns its principal together, or none does (contract P383-05). A
     * checkout that fails is recorded as stuck, and its first failure alerts every staff member who
     * may resolve it; the sweep goes on with the next one and then reports the first failure.
     * A failed checkout is retried after `RETRY_BACKOFF_MINUTES` per attempt, at most
     * `RETRY_BACKOFF_MAX_MINUTES`. Fresh and due retries each get half of every batch, and the odd slot
     * goes to whichever has waited longer, so neither starves the other however the sweeps are spaced.
     * A checkout settled elsewhere after it was picked is skipped.
     */
    public function expireDue(int $limit): int;

    /**
     * Staff retry of one stuck expiry from the console (`cart.releases.resolve`), journaled as
     * `cart.settle` under the operator. It runs the same group expiry, never one child on its own.
     *
     * @return array<string, mixed>
     */
    public function settleStuck(int $staffUserId, string $checkoutId, string $requestId): array;

    /**
     * Held checkouts the sweep has not settled: each one whose settlement failed, and each one still
     * held more than `STUCK_AFTER_SECONDS` after its last window closed, which also shows a sweep
     * that stopped running. Oldest first.
     *
     * @return list<array{checkout_id: string, party_id: string, investor: string|null, total: string, lines: int, settles_at: string,
     *     attempts: int, reason_code: string|null, last_attempted_at: string|null}>
     */
    public function stuck(): array;
}
