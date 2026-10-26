<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\IdentityAccessStore;

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
}
