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
 * `reservation()` reads the Party's own checkout without changing anything. A held checkout past
 * its deadline reads `release_pending` until expiry settles it; a read never settles.
 *
 * @phpstan-import-type QuotedCart from InvestorCartQuotes
 * @phpstan-import-type QuoteBody from InvestorCartQuotes
 * @phpstan-import-type CartCampaign from InvestorCartQuotes
 *
 * @phpstan-type CartReservation array{id: string, cart_id: string, cart_revision: int, revision: int, state: string,
 *     quote: array<string, mixed>, created_at: string, expires_at: string,
 *     lines: list<array{cart_line_id: string, campaign_id: string, reservation_id: string, reservation_revision: int,
 *         principal: array{currency: string, amount: string}, disclosure_sha256: string}>,
 *     total_held: array{currency: string, amount: string}}
 */
interface CartCheckouts
{
    /** The command a whole-cart reserve journals under. */
    public const string RESERVE_COMMAND = 'cart.reserve';

    /**
     * @param  Closure(string, QuotedCart, array<string, CartCampaign>): QuoteBody  $price
     * @return array<string, mixed>
     */
    public function reserve(int $userId, int $contextRevision, int $expectedCartRevision, string $quoteId, int $expectedQuoteRevision,
        string $quoteDisclosureSha256, string $requestId, Closure $price): array;

    /** @return CartReservation */
    public function reservation(int $userId, int $contextRevision, string $checkoutId): array;

    /**
     * The retained `cart.reserve` result for this Party and request id, or `OPERATION_NOT_FOUND`.
     *
     * @return array<string, mixed>
     */
    public function findReservation(int $userId, int $contextRevision, string $requestId): array;
}
