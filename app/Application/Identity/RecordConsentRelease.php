<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\ConsentCatalog;

/**
 * @phpstan-import-type DocumentInput from \App\Domain\Identity\ConsentDocuments
 * @phpstan-import-type DisclosureInput from \App\Domain\Identity\ConsentDocuments
 */
final class RecordConsentRelease
{
    public function __construct(private ConsentCatalog $catalog) {}

    /**
     * Records an externally approved version; this action is not legal approval.
     *
     * @param  list<DocumentInput>  $documents
     * @param  list<DisclosureInput>  $disclosures
     * @return array<string, mixed>
     */
    public function handle(int $actorId, int $expectedRevision, string $status, array $documents, array $disclosures, bool $synthetic, string $approvalReference, string $reason, string $requestId): array
    {
        return $this->catalog->record($actorId, $expectedRevision, $status, $documents, $disclosures, $synthetic, $approvalReference, $reason, $requestId);
    }
}
