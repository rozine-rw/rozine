<?php

declare(strict_types=1);

namespace App\Domain\Identity;

/**
 * Robert's rules for institutional investors (26 Nov 2026), on top of BRS v3 KYC-R02: an institution
 * commits to distributing at least RWF 30,000,000 a month and automates that distribution under rules
 * it sets itself, because Rozine never recommends a business. Monthly top-up reminders start on the
 * 25th, for the following month's distribution.
 */
final class InstitutionalInvestorTerms
{
    /** The smallest monthly distribution an institution may commit to, in RWF. */
    public const int MIN_MONTHLY_COMMITMENT = 30000000;

    /** The largest monthly commitment the form takes, in RWF: the sandbox's whole principal limit (SBX-R01). */
    public const int MAX_MONTHLY_COMMITMENT = 5000000000;

    /** The day of the month the top-up reminders for next month's distribution start. */
    public const int TOP_UP_REMINDER_DAY = 25;

    public const array TYPES = ['pension_fund', 'venture_capital', 'asset_manager', 'insurer', 'bank', 'corporate_treasury', 'other'];

    /**
     * Whether a bank account's holder name is the institution's registered name, ignoring case,
     * punctuation and spacing: the money must sit in the institution's own name (KYC-R03).
     */
    public static function accountNameMatches(string $accountName, string $legalName): bool
    {
        $normalise = fn (string $name): string => (string) preg_replace('/[^\p{L}\p{N}]+/u', '', mb_strtolower($name));

        return $normalise($accountName) !== '' && $normalise($accountName) === $normalise($legalName);
    }
}
