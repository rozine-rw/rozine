<?php

declare(strict_types=1);

namespace App\Application\Evidence;

use App\Application\Evidence\Contracts\StatementStore;

/** @phpstan-import-type Transcription from StatementStore */
final class GetAuditTranscription
{
    public function __construct(private StatementStore $store) {}

    /** @return Transcription|null */
    public function handle(int $userId, int $contextRevision, string $assignmentId, ?string $transcriptionId = null): ?array
    {
        return $this->store->auditTranscription($userId, $contextRevision, $assignmentId, $transcriptionId);
    }
}
