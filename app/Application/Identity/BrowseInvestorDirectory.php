<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\InvestorDirectoryStore;
use App\Domain\Operations\CommandRejection;

/**
 * Compliance's Investor directory (`investors.verify`): the people who invest or have started to,
 * and one person's figures, holdings and identity history. Every read checks the permission first.
 *
 * @phpstan-import-type Directory from InvestorDirectoryStore
 * @phpstan-import-type Detail from InvestorDirectoryStore
 */
final class BrowseInvestorDirectory
{
    public function __construct(private InvestorDirectoryStore $store, private AuthorizeStaffPermission $staff) {}

    /**
     * @param  'all'|'verified'|'pending'|'kyc_overdue'|'frozen'|'restricted'  $chip
     * @param  'portfolio'|'name'  $sort
     * @return Directory
     */
    public function directory(int $actorId, string $chip, string $sort, string $search, int $limit): array
    {
        $this->staff->check($actorId, 'investors.verify');

        return $this->store->directory($chip, $sort, $search, $limit);
    }

    /** @return Detail */
    public function party(int $actorId, string $partyId): array
    {
        $this->staff->check($actorId, 'investors.verify');

        return $this->store->party($partyId) ?? throw new CommandRejection('INVESTOR_NOT_FOUND', 404);
    }

    public function partyForVerification(int $actorId, string $verificationId): string
    {
        $this->staff->check($actorId, 'investors.verify');

        return $this->store->partyForVerification($verificationId) ?? throw new CommandRejection('VERIFICATION_NOT_FOUND', 404);
    }
}
