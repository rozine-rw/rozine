<?php

declare(strict_types=1);

namespace App\Domain\Identity;

use Mmccook\JsonCanonicalizator\JsonCanonicalizatorFactory;

/**
 * What a verified person accepts when they add Investor access to the Party they already hold
 * (issue #380): their identity is not checked again, but the Investor terms are acknowledged afresh.
 * The grant binds this version and the digest of this exact wording, so a new wording is a new digest.
 */
final class InvestorAccessTerms
{
    public const string VERSION = 'investor-access-2026-10-26';

    public const string TEXT = 'I am adding Investor access to my verified Rozine identity. I accept the Rozine Investor Terms. I understand that I invest at my own risk, that a confirmed commitment is final, and that I acknowledge the investment risk statements before my first investment.';

    public static function sha256(): string
    {
        return hash('sha256', JsonCanonicalizatorFactory::getInstance()->canonicalize(
            (object) ['contract' => 'investor-access-terms-1', 'version' => self::VERSION, 'text' => self::TEXT], false));
    }

    /** @return array{version: string, text: string, sha256: string} */
    public static function toArray(): array
    {
        return ['version' => self::VERSION, 'text' => self::TEXT, 'sha256' => self::sha256()];
    }
}
