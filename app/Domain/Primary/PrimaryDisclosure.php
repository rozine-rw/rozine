<?php

declare(strict_types=1);

namespace App\Domain\Primary;

/**
 * The primary purchase disclosure the Investor acknowledges at confirm. Its version travels in
 * the disclosed terms, whose digest binds the exact rights and fees; these points explain them.
 */
final class PrimaryDisclosure
{
    public const string VERSION = 'primary-disclosure-2026-10-13';

    /** @return list<string> */
    public static function points(string $businessName, int $units, int $termMonths, string $ratePercent): array
    {
        return [
            sprintf('You buy %d note %s of RWF 5,000 principal in %s, repaid monthly over %d months.', $units, $units === 1 ? 'unit' : 'units', $businessName, $termMonths),
            sprintf('The %s%% return is flat over the whole term, not annualised, and is projected, not guaranteed.', $ratePercent),
            'A fee on earnings is taken from the return in each payout, never from your principal, at the rate shown; there is no purchase fee.',
            'Returns are paid only if the business pays. If it pays late or defaults, you can lose some or all of your money, and you may not find a buyer if you want to sell early.',
        ];
    }
}
