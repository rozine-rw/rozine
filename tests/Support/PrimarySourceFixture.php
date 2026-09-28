<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Application\Wallet\LockedWallet;
use App\Application\Wallet\PostingSource;
use Illuminate\Support\Str;

/**
 * The lifecycle source a primary posting test holds, commits and issues under. Once S3-C's
 * retained reservations land (#175), every primary_reservation posting must name a real
 * reservation with the matching Party, originating operation and principal; this delegates to
 * #175's `PrimaryReservationFixture::postingSource` when it exists, and until then names a fresh
 * reservation identity the current schema accepts.
 */
final class PrimarySourceFixture
{
    public static function reservation(LockedWallet $wallet, string $principal): PostingSource
    {
        $retained = [__NAMESPACE__.'\\PrimaryReservationFixture', 'postingSource'];
        if (is_callable($retained)) {
            $source = $retained($wallet, $principal);
            if ($source instanceof PostingSource) {
                return $source;
            }
        }

        return new PostingSource('primary_reservation', strtolower((string) Str::ulid()), strtolower((string) Str::ulid()));
    }
}
