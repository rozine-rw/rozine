<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\LegalConsents;
use App\Domain\Identity\IdentityViolation;

/**
 * A signed-in person's agreement to the current Terms, Privacy Notice and Disclaimers, and the
 * check that guards every action those documents cover: nobody acts in the Investor, Business or
 * Auditor app until their Party has agreed to the wording in force now.
 */
final class AcceptLegalDocuments
{
    public function __construct(private LegalConsents $consents, private LegalDocuments $documents) {}

    /**
     * Records the agreement. Digests of any other wording are refused, so a person who read an
     * older version is shown the new one first.
     *
     * @param  array<mixed>  $digests
     */
    public function handle(int $userId, array $digests): void
    {
        $partyId = $this->consents->partyFor($userId);
        if ($partyId === null) {
            throw new IdentityViolation('PARTY_REQUIRED', 403);
        }
        if (! $this->documents->allCurrent($digests)) {
            throw new IdentityViolation('LEGAL_DOCUMENTS_STALE', 422);
        }
        $this->consents->record($partyId, $this->agreed(), 'review');
    }

    /** Whether the account may act: it has agreed to the current wording, or it has no Party to act as. */
    public function satisfied(int $userId): bool
    {
        return $this->consents->covers($userId, $this->agreed());
    }

    /** @return list<array{key: string, version: string, sha256: string}> */
    public function agreed(): array
    {
        return array_map(fn (array $document): array => ['key' => $document['key'], 'version' => $document['version'],
            'sha256' => $document['sha256']], $this->documents->current());
    }
}
