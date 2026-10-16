<?php

declare(strict_types=1);

namespace App\Domain\Primary;

use Mmccook\JsonCanonicalizator\JsonCanonicalizatorFactory;

/**
 * The five statements an Investor ticks before a first investment (BRS v2.0 KYC-R05). Each
 * acknowledgement binds this version and the digest of these exact statements, so changing the
 * wording changes the digest and the Investor acknowledges again before the next investment.
 */
final class RiskStatements
{
    public const string VERSION = 'brs-2.0-kyc-r05';

    /** @var array<string, string> */
    public const array STATEMENTS = [
        'total_loss' => 'I understand I can lose all the money I invest.',
        'no_guarantee' => 'I understand there is no guarantee, collateral or reserve fund.',
        'business_pays' => 'I understand returns are paid only if the business pays.',
        'no_buyer' => 'I understand I may not find a buyer if I want to sell early.',
        'sandbox' => 'I understand Rozine is operating in the CMA sandbox under limits.',
    ];

    public static function sha256(): string
    {
        return hash('sha256', JsonCanonicalizatorFactory::getInstance()->canonicalize(
            (object) ['contract' => 'investor-risk-statements-1', 'version' => self::VERSION, 'statements' => (object) self::STATEMENTS], false));
    }
}
