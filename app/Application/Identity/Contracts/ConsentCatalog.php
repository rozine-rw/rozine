<?php

declare(strict_types=1);

namespace App\Application\Identity\Contracts;

use Closure;

/**
 * @phpstan-import-type DocumentInput from \App\Domain\Identity\ConsentDocuments
 * @phpstan-import-type DisclosureInput from \App\Domain\Identity\ConsentDocuments
 * @phpstan-import-type Document from \App\Domain\Identity\ConsentDocuments
 * @phpstan-import-type Disclosure from \App\Domain\Identity\ConsentDocuments
 *
 * @phpstan-type Release array{id: string, revision: int, documents: list<Document>, disclosures: list<Disclosure>, synthetic: bool}
 */
interface ConsentCatalog
{
    /**
     * @param  list<DocumentInput>  $documents
     * @param  list<DisclosureInput>  $disclosures
     * @return array<string, mixed>
     */
    public function record(int $actorId, int $expectedRevision, string $status, array $documents, array $disclosures, bool $synthetic, string $approvalReference, string $reason, string $requestId): array;

    /**
     * Holds the current release stable through the protected operation. A missing,
     * withdrawn or environment-ineligible release provides no acceptable documents.
     *
     * @template TResult
     *
     * @param  Closure(Release|null): TResult  $operation
     * @return TResult
     */
    public function withCurrent(Closure $operation): mixed;
}
