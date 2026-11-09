<?php

declare(strict_types=1);

namespace App\Application\Primary\Contracts;

use Closure;
use DateTimeImmutable;

/**
 * Immutable whole-cart quotes (#383, contract cart-checkout-1 §5). A quote prices the entire saved
 * cart at the revision the Investor saw, and holds no units or cash.
 *
 * `quote()` takes the Business locks of every campaign in the cart first, then current Investor
 * authority, then the cart root, so the cart cannot change while it is priced, and then each
 * campaign's retained publication. A cart at another revision is refused `CART_REVISION_CONFLICT`,
 * an empty cart `CART_EMPTY`, and a line whose raise is closed or funded `CART_LINE_UNAVAILABLE`
 * naming every such line: the whole cart is refused, never a subset. `$price` runs under those
 * locks with no external effects and returns the quote's lines, totals and band; the store gives
 * the quote its id, times and digests and keeps it.
 *
 * @phpstan-type CartCampaign array{publication_sha256: string, rate_pct: string, term_months: int, policy_version: string}
 * @phpstan-type QuotedCart array{id: string, revision: int, sha256: string, lines: list<array{id: string, campaign_id: string, units: int}>}
 * @phpstan-type QuoteBody array{lines: list<array<string, mixed>>, principal: array{currency: string, amount: string},
 *     earnings_fee: array{currency: string, amount: string}, net_return: array{currency: string, amount: string}, fee_band: array<string, mixed>}
 * @phpstan-type Quote array<string, mixed>
 */
interface InvestorCartQuotes
{
    public const string CONTRACT_VERSION = 'cart-checkout-1';

    /**
     * @param  Closure(string, QuotedCart, array<string, CartCampaign>): QuoteBody  $price
     * @return Quote
     */
    public function quote(int $userId, int $contextRevision, int $expectedCartRevision, Closure $price): array;

    /**
     * Keeps a replacement quote a held cart checkout priced again under its own locks (#383 step
     * 4b): the cart it holds, the body it priced, and the checkout's own deadline, which a requote
     * never extends. The caller holds every lock `quote()` takes.
     *
     * @param  QuotedCart  $cart
     * @param  QuoteBody  $body
     * @return Quote
     */
    public function replacement(string $partyId, array $cart, array $body, DateTimeImmutable $expiresAt): array;
}
