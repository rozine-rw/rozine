<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Application\Wallet\LockedWallet;
use App\Application\Wallet\PostingSource;

/**
 * The lifecycle source a primary posting test holds, commits and issues under. With S3-C's retained
 * reservations present (#175), every primary_reservation posting must name a real reservation with
 * the matching Party, originating operation and principal, so this always delegates to #175's
 * `PrimaryReservationFixture::postingSource`. The fresh-identity fallback it carried before the
 * integration is gone: the schema refuses it, and static analysis proves it unreachable.
 */
final class PrimarySourceFixture
{
    public static function reservation(LockedWallet $wallet, string $principal): PostingSource
    {
        return PrimaryReservationFixture::postingSource($wallet, $principal);
    }
}
