<?php

declare(strict_types=1);

namespace App\Application\Evidence\Contracts;

interface StatementExtractionQueue
{
    /** Process already-committed originals; this mechanical worker grants no audit approval. */
    public function processPending(int $limit): int;
}
