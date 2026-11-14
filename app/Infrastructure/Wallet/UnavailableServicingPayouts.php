<?php

declare(strict_types=1);

namespace App\Infrastructure\Wallet;

use App\Application\Business\Contracts\ServicingPayouts;

/** No servicing domain pays Investors yet (S4-C): nothing is paid on and nothing waits. */
final class UnavailableServicingPayouts implements ServicingPayouts
{
    public function payOut(string $repaymentId): int
    {
        return 0;
    }

    public function unpaid(int $limit): array
    {
        return [];
    }
}
