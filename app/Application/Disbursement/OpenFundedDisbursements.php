<?php

declare(strict_types=1);

namespace App\Application\Disbursement;

use App\Application\Disbursement\Contracts\DisbursementStore;

/**
 * Records one disbursement for each fully funded campaign the funding source lists, once. Under
 * the unavailable funding source nothing is listed and nothing is opened.
 */
final class OpenFundedDisbursements
{
    public function __construct(private DisbursementStore $store) {}

    /** @return array{opened: int, known: int} */
    public function handle(int $limit = 50): array
    {
        return $this->store->openFunded($limit);
    }
}
