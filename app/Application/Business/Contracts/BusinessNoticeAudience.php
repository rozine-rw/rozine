<?php

declare(strict_types=1);

namespace App\Application\Business\Contracts;

/**
 * Who a Business's notices belong to and reach (BRS v2.0 NTF-R01): the Business's own entity
 * Party holds the notices, and its current mandate's people who may view the Business receive
 * the e-mailed ones. It grants nothing; reading the notices still needs current Business authority.
 */
interface BusinessNoticeAudience
{
    /** The Business's entity Party. */
    public function entityParty(string $businessId): string;

    /**
     * The accounts of the current mandate's people holding `business.view`, in a stable order.
     *
     * @return list<int>
     */
    public function recipients(string $businessId): array;
}
