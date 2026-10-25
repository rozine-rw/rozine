<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\IdentityRepository;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Identity\SandboxNotice;

final class RegisterIdentity
{
    public function __construct(private IdentityRepository $identities) {}

    /** Registers a person who acknowledged exactly the current sandbox notice (BRS v2.0 KYC-R01). */
    public function handle(string $name, string $email, string $password, string $noticeVersion, string $noticeSha256): int
    {
        if ($noticeVersion !== SandboxNotice::VERSION || ! hash_equals(SandboxNotice::sha256(), $noticeSha256)) {
            throw new IdentityViolation('SANDBOX_NOTICE_REQUIRED', 422);
        }

        return $this->identities->register($name, $email, $password);
    }
}
