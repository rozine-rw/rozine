<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\IdentityAccessStore;
use Closure;

final class VerifyInvestorIdentity
{
    public function __construct(private IdentityAccessStore $access) {}

    /**
     * @param  Closure(string): array<string, mixed>  $approve
     * @return array<string, mixed>
     */
    public function handle(int $actorId, int $userId, string $identityReference, string $evidenceReference, string $reason, string $requestId, Closure $approve): array
    {
        return $this->access->verifyInvestor($actorId, $userId, $identityReference, $evidenceReference, $reason, $requestId, $approve);
    }
}
