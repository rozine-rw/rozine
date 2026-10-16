<?php

declare(strict_types=1);

namespace App\Domain\Secondary;

/**
 * The secondary market's fixed terms (BRS v2.0 §12): a listing lasts seven days (SEC-R02), a
 * buyer's reservation five minutes (SEC-R05), the seller pays 2% of the sale price and the buyer
 * nothing (SEC-R06), and a note pauses from 23:00 Kigali on its due date until that month's
 * payouts post (SEC-R07). There are no listing, reservation, cancellation or expiry fees.
 */
final class SecondaryTerms
{
    public const string VERSION = 'SEC-R/2026-10';

    public const string ZONE = 'Africa/Kigali';

    public const int LISTING_SECONDS = 7 * 24 * 3600;

    public const int RESERVATION_SECONDS = 300;

    public const int SELLER_FEE_BPS = 200;

    public const int BUYER_FEE_BPS = 0;

    public const string PAUSE_FROM = '23:00';
}
