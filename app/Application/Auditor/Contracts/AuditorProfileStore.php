<?php

declare(strict_types=1);

namespace App\Application\Auditor\Contracts;

/**
 * @phpstan-import-type State from \App\Domain\Auditor\AccreditationProfile
 *
 * @phpstan-type Profile array{id: string|null, party_id: string, revision: int, state: State}
 * @phpstan-type Download array{filename: string, media_type: string, content: string, sha256: string}
 */
interface AuditorProfileStore
{
    /** @return array<string, mixed> */
    public function submit(int $userId, int $contextRevision, int $expectedRevision, string $licence, string $expiresOn, string $filename, string $content, string $requestId, bool $renew = false): array;

    /** @return array<string, mixed> */
    public function withdraw(int $userId, int $contextRevision, int $expectedRevision, string $submissionId, string $requestId): array;

    /** @return array<string, mixed> */
    public function availability(int $userId, int $contextRevision, int $expectedRevision, bool $accepting, string $requestId): array;

    /** @return array<string, mixed> */
    public function review(int $actorId, string $partyId, int $expectedRevision, string $decision, ?string $submissionId, string $checkedAt, string $reference, string $reason, string $requestId): array;

    /** @return Profile */
    public function get(int $userId, int $contextRevision): array;

    /** @return array<string, mixed> */
    public function findOperation(int $userId, int $contextRevision, string $command, string $requestId): array;

    /** @return Download */
    public function readCertificate(int $userId, ?int $contextRevision, string $partyId, string $certificateId, bool $staff): array;
}
