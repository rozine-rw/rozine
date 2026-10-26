<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\IdentityAccessStore;
use App\Domain\Identity\InvestorAccessTerms;

final class AddInvestorAccess
{
    public function __construct(private IdentityAccessStore $access, private GetIdentityContext $identity) {}

    /**
     * Adds Investor access to the person's own verified Party and answers their identity context, in
     * which the Investor role is now available to select.
     *
     * @return array<string, mixed>
     */
    public function handle(int $userId, int $expectedRevision, string $termsVersion, string $termsSha256, string $requestId): array
    {
        $this->access->addOwnInvestorAccess($userId, $expectedRevision, $termsVersion, $termsSha256, $requestId);

        return $this->identity->handle($userId);
    }

    /**
     * The terms a person accepts to add Investor access, bound by version and digest.
     *
     * @return array{version: string, text: string, sha256: string}
     */
    public function terms(): array
    {
        return InvestorAccessTerms::toArray();
    }
}
