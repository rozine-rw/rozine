<?php

declare(strict_types=1);

namespace App\Domain\Identity;

use Mmccook\JsonCanonicalizator\JsonCanonicalizatorFactory;

/**
 * The CMA sandbox notice every person acknowledges before the sign-up form (BRS KYC-R01). Until the
 * CMA admits Rozine, it says the application is pending rather than that testing is under way.
 * Each acknowledgement binds this version and the digest of this exact wording, so a new wording
 * is a new digest and is acknowledged afresh.
 */
final class SandboxNotice
{
    public const string VERSION = 'kyc-r01-2026-11-23';

    public const string TEXT = "Rozine has applied to test this service in the Capital Market Authority's Fintech Regulatory Sandbox. Once admitted, it will run under limits set by the CMA. Admission to the sandbox is not a licence or an endorsement.";

    public static function sha256(): string
    {
        return hash('sha256', JsonCanonicalizatorFactory::getInstance()->canonicalize(
            (object) ['contract' => 'sandbox-notice-1', 'version' => self::VERSION, 'text' => self::TEXT], false));
    }

    /** @return array{version: string, text: string, sha256: string} */
    public static function toArray(): array
    {
        return ['version' => self::VERSION, 'text' => self::TEXT, 'sha256' => self::sha256()];
    }
}
