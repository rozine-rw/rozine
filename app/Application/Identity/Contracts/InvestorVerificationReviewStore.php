<?php

declare(strict_types=1);

namespace App\Application\Identity\Contracts;

/**
 * Compliance's review of a person's own Investor identity submission (`investors.verify`). Reads are
 * preflight-checked and never lock; approve and reject are journaled staff commands. Approval goes
 * through the verified-person writer, which alone sets Party verification.
 *
 * @phpstan-import-type State from \App\Domain\Identity\InvestorVerificationCase
 *
 * @phpstan-type QueueEntry array{
 *     id: string, revision: int, status: string, submitted_at: string,
 *     decided_at: string|null, name: string, email: string, id_type: string
 * }
 * @phpstan-type Queue array{
 *     tab: 'submitted'|'decided', search: string, limit: int, before: string|null, entries: list<QueueEntry>,
 *     next_cursor: string|null, counts: array{submitted: int, decided: int}
 * }
 * @phpstan-type ReviewDocument array{
 *     id: string, slot: 'front'|'back'|'selfie', filename: string, media_type: string, size_bytes: int,
 *     sha256: string, uploaded_at: string, current: bool
 * }
 * @phpstan-type Review array{
 *     id: string, revision: int, status: 'draft'|'submitted'|'approved'|'rejected', submitted_at: string|null,
 *     account: array{name: string, email: string}, state: State, documents: list<ReviewDocument>,
 *     history: list<array{revision: int, status: string, command: string, reason: string|null, at: string}>,
 *     allowed_actions: list<'approve'|'reject'>
 * }
 */
interface InvestorVerificationReviewStore
{
    /** @return Queue */
    public function queue(int $actorId, string $tab, string $search, ?string $before, int $limit): array;

    /** @return Review */
    public function show(int $actorId, string $verificationId): array;

    /** @return array{filename: string, media_type: string, content: string} */
    public function document(int $actorId, string $verificationId, string $documentId): array;

    /** @return array<string, mixed> */
    public function approve(int $actorId, string $verificationId, int $expectedRevision, string $reason, string $requestId): array;

    /** @return array<string, mixed> */
    public function reject(int $actorId, string $verificationId, int $expectedRevision, string $reason, string $requestId): array;
}
