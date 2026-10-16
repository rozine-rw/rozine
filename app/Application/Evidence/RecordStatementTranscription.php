<?php

declare(strict_types=1);

namespace App\Application\Evidence;

use App\Application\Evidence\Contracts\StatementStore;

/**
 * @phpstan-import-type Rail from \App\Domain\Evidence\StatementReconciliation
 * @phpstan-import-type Statement from \App\Domain\Evidence\StatementReconciliation
 */
final class RecordStatementTranscription
{
    public function __construct(private StatementStore $store) {}

    /**
     * @param  list<Rail>  $rails
     * @param  list<string>  $months
     * @param  list<Statement>  $statements
     * @return array<string, mixed>
     */
    public function handle(int $userId, int $contextRevision, string $businessId, int $expectedRevision, array $rails, array $months, array $statements, string $requestId): array
    {
        return $this->store->recordTranscription($userId, $contextRevision, $businessId, $expectedRevision, $rails, $months, $statements, $requestId);
    }
}
