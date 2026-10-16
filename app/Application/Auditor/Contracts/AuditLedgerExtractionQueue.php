<?php

declare(strict_types=1);

namespace App\Application\Auditor\Contracts;

interface AuditLedgerExtractionQueue
{
    public function processPending(int $limit): int;
}
