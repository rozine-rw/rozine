<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditorEarningsStore;
use Carbon\CarbonImmutable;

/** @phpstan-import-type Earnings from AuditorEarningsStore */
final class GetAuditorEarnings
{
    public function __construct(private AuditorEarningsStore $earnings) {}

    /**
     * The partner's earnings statement as of now.
     *
     * @return Earnings
     */
    public function handle(int $userId, int $contextRevision): array
    {
        return $this->earnings->statement($userId, $contextRevision, CarbonImmutable::now());
    }
}
