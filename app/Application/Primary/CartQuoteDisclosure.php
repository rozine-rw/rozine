<?php

declare(strict_types=1);

namespace App\Application\Primary;

use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Primary\Contracts\InvestorCartQuotes;

/**
 * The hash of everything the Investor acknowledges in a whole-cart quote (#383, contract
 * cart-checkout-1): the cart, every line, the totals and the band. The held-capital revision and
 * hash are left out, so a revision that changes no figure is not a change of terms. The quote keeps
 * it; the cart checkout recomputes it under its locks to detect a stale quote.
 */
final readonly class CartQuoteDisclosure
{
    /** The version of the canonical object the hash covers. */
    public const string VERSION = 'cart-quote-disclosure-1';

    /**
     * @param  array{id: string, revision: int, sha256: string}  $cart
     * @param  array{lines: list<array<string, mixed>>, principal: mixed, earnings_fee: mixed, net_return: mixed, fee_band: array<string, mixed>}  $body
     */
    public static function sha256(CanonicalJson $json, array $cart, array $body): string
    {
        return hash('sha256', $json->encode(['version' => self::VERSION, 'contract_version' => InvestorCartQuotes::CONTRACT_VERSION,
            'cart' => ['id' => $cart['id'], 'revision' => $cart['revision'], 'sha256' => $cart['sha256']], 'lines' => $body['lines'],
            'principal' => $body['principal'], 'earnings_fee' => $body['earnings_fee'], 'net_return' => $body['net_return'],
            'fee_band' => array_diff_key($body['fee_band'], ['capital_revision' => true, 'capital_sha256' => true])]));
    }
}
