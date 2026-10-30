<?php

declare(strict_types=1);

namespace App\Application\Evidence;

use App\Application\Evidence\Contracts\StatementStore;

/** @phpstan-import-type TranscriptionDraft from StatementStore */
final class GetStatementTranscriptionDraft
{
    public function __construct(private StatementStore $store) {}

    /** @return TranscriptionDraft */
    public function handle(int $userId, int $contextRevision, string $businessId): array
    {
        return $this->store->transcriptionDraft($userId, $contextRevision, $businessId);
    }
}
