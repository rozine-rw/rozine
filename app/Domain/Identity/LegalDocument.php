<?php

declare(strict_types=1);

namespace App\Domain\Identity;

/**
 * The three public legal documents every person agrees to before acting on Rozine: the Terms, the
 * Privacy Notice and the Disclaimers. A consent binds the document's version and the SHA-256 digest
 * of its exact wording, so new wording is a new digest; changed wording must also carry a new
 * version, which `LegalConsentTest` pins.
 */
enum LegalDocument: string
{
    case Terms = 'terms';
    case Privacy = 'privacy';
    case Disclaimers = 'disclaimers';

    public function version(): string
    {
        return match ($this) {
            self::Terms => 'terms-2026-11-23',
            self::Privacy => 'privacy-2026-11-23',
            self::Disclaimers => 'disclaimers-2026-11-23',
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::Terms => 'Terms and Conditions',
            self::Privacy => 'Privacy Notice',
            self::Disclaimers => 'Disclaimers',
        };
    }
}
