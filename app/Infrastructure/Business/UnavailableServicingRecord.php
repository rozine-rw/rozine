<?php

declare(strict_types=1);

namespace App\Infrastructure\Business;

use App\Application\Business\Contracts\ServicingRecord;

/** No servicing domain is bound yet (S4-B): no note has been repaid and no instalment paid. */
final class UnavailableServicingRecord implements ServicingRecord
{
    public function forBusiness(string $businessId): array
    {
        return ['repaid' => [], 'instalments_paid' => 0, 'paid_on_time' => 0];
    }
}
