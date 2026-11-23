<?php

declare(strict_types=1);

namespace App\Application\Identity\Contracts;

/**
 * The permanent record of each person agreeing to a legal document: the Party, the document, its
 * version, the digest of its wording and the time. Records are never changed or deleted.
 *
 * @phpstan-type AgreedDocument array{key: string, version: string, sha256: string}
 */
interface LegalConsents
{
    /**
     * Records the Party's agreement to each document, once per exact wording.
     *
     * @param  list<AgreedDocument>  $documents
     * @param  'sign_up'|'review'  $source  Where the person agreed: on the sign-up form or on the review page.
     */
    public function record(string $partyId, array $documents, string $source): void;

    /** The Party the account acts as, or null for an account that has none. */
    public function partyFor(int $userId): ?string;

    /**
     * Whether the account's Party has agreed to every one of these exact wordings, in one read. An
     * account with no Party (staff) or acting as an organisation has nothing to agree to, so it is covered.
     *
     * @param  list<AgreedDocument>  $documents
     */
    public function covers(int $userId, array $documents): bool;
}
