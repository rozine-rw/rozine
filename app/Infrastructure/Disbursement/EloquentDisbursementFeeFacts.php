<?php

declare(strict_types=1);

namespace App\Infrastructure\Disbursement;

use App\Application\Disbursement\Contracts\DisbursementFeeFacts;
use App\Models\Disbursement;

/**
 * Each disbursement's principal and the platform fee taken from it, as the disbursement records them.
 *
 * @phpstan-import-type FeeFacts from DisbursementFeeFacts
 */
final class EloquentDisbursementFeeFacts implements DisbursementFeeFacts
{
    /**
     * @param  list<string>  $disbursementIds
     * @return array<string, FeeFacts>
     */
    public function forDisbursements(array $disbursementIds): array
    {
        return Disbursement::query()->whereIn('id', $disbursementIds)->get(['id', 'amount', 'platform_fee'])
            ->mapWithKeys(fn (Disbursement $disbursement): array => [$disbursement->id => ['raise' => $disbursement->amount, 'platform_fee' => $disbursement->platform_fee]])
            ->all();
    }
}
