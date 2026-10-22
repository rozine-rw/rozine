<?php

declare(strict_types=1);

namespace App\Application\Disbursement\Contracts;

/**
 * The raise and platform fee each disbursement was opened under (BRS v2.0 FEE-R01), for reads that
 * show how a fee share was worked out. A plain read; it decides nothing.
 *
 * @phpstan-type FeeFacts array{raise: string, platform_fee: string}
 */
interface DisbursementFeeFacts
{
    /**
     * The facts of each named disbursement, keyed by its ID, in whole francs.
     *
     * @param  list<string>  $disbursementIds
     * @return array<string, FeeFacts>
     */
    public function forDisbursements(array $disbursementIds): array;
}
