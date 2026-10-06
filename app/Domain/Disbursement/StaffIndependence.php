<?php

declare(strict_types=1);

namespace App\Domain\Disbursement;

/**
 * The independence statement a maker or checker signs with their authorize or approve command
 * (owner decision, #96 5956161592). Its digest is retained with each declaration, so a later
 * wording change never counts an earlier signature as agreement to new text.
 */
final class StaffIndependence
{
    public const string VERSION = 'staff-independence-v1';

    public const string STATEMENT = 'I confirm that I have no personal, family, financial or employment relationship with this '
        .'Business, its signatories or beneficial owners, or any Investor in this campaign.';

    public static function statementSha256(): string
    {
        return hash('sha256', self::VERSION."\n".self::STATEMENT);
    }
}
