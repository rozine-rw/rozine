<?php

declare(strict_types=1);

namespace App\Application\Primary;

use App\Application\Primary\Contracts\CartCheckouts;
use App\Domain\Operations\CommandRejection;

/**
 * Holds every line of the Investor's quoted cart at once, or none (#383 step 4a, contract
 * cart-checkout-1 `ReserveInvestorCart`). The Investor names the cart revision and the quote they
 * accepted; the checkout prices the cart again under its locks with the quote's own pricing, and
 * refuses `CART_QUOTE_STALE` if anything they acknowledged changed.
 *
 * @phpstan-import-type CartReservation from CartCheckouts
 */
final readonly class ReserveInvestorCart
{
    public function __construct(private GetInvestorViewer $viewer, private CartCheckouts $checkouts, private QuoteInvestorCart $quote) {}

    /** @return array<string, mixed> */
    public function handle(int $userId, int $contextRevision, int $expectedCartRevision, string $quoteId, int $expectedQuoteRevision,
        string $quoteDisclosureSha256, string $requestId): array
    {
        $wallet = $this->viewer->handle($userId, $contextRevision)['wallet'];
        if ($wallet === null) {
            throw new CommandRejection('IDENTITY_VERIFICATION_REQUIRED', 403);
        }
        if ($wallet['wallet']['status'] !== 'active') {
            throw new CommandRejection('RESTRICTION_ACTIVE', 409);
        }

        return $this->checkouts->reserve($userId, $contextRevision, $expectedCartRevision, $quoteId, $expectedQuoteRevision, $quoteDisclosureSha256,
            $requestId, fn (string $partyId, array $cart, array $campaigns): array => $this->quote->price($userId, $partyId, $cart, $campaigns));
    }

    /**
     * Buys every held line of the Investor's checkout at once, or none (#383 step 4b, contract
     * `confirmAll`). The Investor names the checkout revision, its current quote and the hash they
     * acknowledged; if the terms changed since, the checkout is requoted and nothing is bought.
     *
     * @return array<string, mixed>
     */
    public function confirm(int $userId, int $contextRevision, string $checkoutId, int $expectedRevision, string $quoteId, int $expectedQuoteRevision,
        string $acknowledgedDisclosureSha256, string $requestId): array
    {
        return $this->checkouts->confirm($userId, $contextRevision, $checkoutId, $expectedRevision, $quoteId, $expectedQuoteRevision,
            $acknowledgedDisclosureSha256, $requestId,
            fn (string $partyId, array $cart, array $campaigns): array => $this->quote->price($userId, $partyId, $cart, $campaigns, held: true));
    }

    /** @return CartReservation */
    public function reservation(int $userId, int $contextRevision, string $checkoutId): array
    {
        return $this->checkouts->reservation($userId, $contextRevision, $checkoutId);
    }

    /** @return array<string, mixed> */
    public function find(int $userId, int $contextRevision, string $requestId): array
    {
        return $this->checkouts->findReservation($userId, $contextRevision, $requestId);
    }

    /**
     * Returns every held line's principal at once (#383 step 4c, contract `ReleaseCartReservation`).
     *
     * @return array<string, mixed>
     */
    public function release(int $userId, int $contextRevision, string $checkoutId, int $expectedRevision, string $requestId): array
    {
        return $this->checkouts->release($userId, $contextRevision, $checkoutId, $expectedRevision, $requestId);
    }

    /** @return array<string, mixed> */
    public function findOperation(int $userId, int $contextRevision, string $command, string $checkoutId, string $requestId): array
    {
        return $this->checkouts->findOperation($userId, $contextRevision, $command, $checkoutId, $requestId);
    }
}
